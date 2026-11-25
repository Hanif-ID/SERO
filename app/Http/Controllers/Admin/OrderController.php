<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\ImageStore;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = PurchaseOrder::with('vendor')->withCount('items')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->vendor_id, fn ($q, $v) => $q->where('vendor_id', $v))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'vendors' => User::where('role', User::ROLE_VENDOR)->orderBy('name')->get(),
        ]);
    }

    /** Tahap 2 — Form pesanan digital (pengganti chat WhatsApp). */
    public function create(Request $request)
    {
        $vendors = User::where('role', User::ROLE_VENDOR)->orderBy('name')->get();
        $vendor = $vendors->firstWhere('id', (int) $request->vendor_id);
        $products = collect();
        $incoming = collect();

        if ($vendor) {
            $products = Product::withTotals()->where('vendor_id', $vendor->id)->orderBy('name')->get();
            $incoming = Product::incomingMap();
        }

        return view('admin.orders.create', [
            'vendors' => $vendors,
            'vendor' => $vendor,
            'products' => $products,
            'incoming' => $incoming,
            'prefill' => $request->query('from') === 'restock',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_VENDOR)],
            'needed_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);

        $items = collect($data['items'])->map(fn ($q) => (int) $q)->filter(fn ($q) => $q > 0);

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Isi jumlah pesan minimal untuk satu produk.']);
        }

        $valid = Product::where('vendor_id', $data['vendor_id'])->whereIn('id', $items->keys())->pluck('id');
        if ($valid->count() !== $items->count()) {
            throw ValidationException::withMessages(['items' => 'Ada produk yang bukan milik vendor terpilih.']);
        }

        $order = DB::transaction(function () use ($data, $items, $request) {
            $order = PurchaseOrder::create([
                'code' => PurchaseOrder::generateCode(),
                'vendor_id' => $data['vendor_id'],
                'created_by' => $request->user()->id,
                'status' => 'menunggu',
                'needed_date' => $data['needed_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $productId => $qty) {
                $order->items()->create(['product_id' => $productId, 'qty_ordered' => $qty]);
            }

            $order->addEvent('Pesanan dibuat dan dikirim ke vendor.', $request->user(), 'status', 'menunggu');

            return $order;
        });

        return redirect()->route('admin.orders.show', $order)->with('success', 'Pesanan terkirim ke vendor.');
    }

    public function show(PurchaseOrder $order)
    {
        $order->load(['vendor', 'creator', 'items.product', 'events.user', 'receiveLocation', 'returnRequest']);

        return view('admin.orders.show', [
            'order' => $order,
            'freezers' => Location::where('type', 'freezer')->withSum('stocks as used', 'qty')->orderBy('name')->get(),
        ]);
    }

    public function cancel(Request $request, PurchaseOrder $order): RedirectResponse
    {
        abort_unless(in_array($order->status, ['menunggu', 'dikonfirmasi'], true), 422, 'Pesanan tidak bisa dibatalkan pada status ini.');

        $order->update(['status' => 'dibatalkan']);
        $order->addEvent('Pesanan dibatalkan oleh toko.', $request->user(), 'status', 'dibatalkan');

        return back()->with('success', 'Pesanan dibatalkan.');
    }

    /** Tahap 4 — Barang tiba, mulai pemeriksaan (jam tiba dicatat untuk memantau risiko mencair). */
    public function inspect(Request $request, PurchaseOrder $order): RedirectResponse
    {
        abort_unless($order->status === 'dikirim', 422, 'Pesanan belum berstatus dalam pengiriman.');

        $order->update(['status' => 'diperiksa', 'arrived_at' => now()]);
        $order->addEvent('Barang tiba, pemeriksaan dimulai.', $request->user(), 'status', 'diperiksa');

        return back()->with('success', 'Pemeriksaan dimulai. Segera selesaikan agar barang tidak mencair.');
    }

    /** Tahap 5 + 6 — Konfirmasi hasil pemeriksaan, buat retur otomatis, update stok. */
    public function confirm(Request $request, PurchaseOrder $order, StockService $stock): RedirectResponse
    {
        abort_unless($order->status === 'diperiksa', 422, 'Pesanan belum dalam tahap pemeriksaan.');
        $order->load('items.product');

        $rules = [
            'receive_location_id' => ['required', Rule::exists('locations', 'id')->where('type', 'freezer')],
            'arrival_temp' => ['nullable', 'numeric', 'between:-60,40'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
        $attributes = ['receive_location_id' => 'freezer tujuan', 'arrival_temp' => 'suhu saat tiba'];

        foreach ($order->items as $item) {
            $k = "items.{$item->id}";
            $rules["$k.qty_received"] = ['required', 'integer', 'min:0', 'max:1000000'];
            $rules["$k.qty_rejected"] = ['nullable', 'integer', 'min:0', "lte:$k.qty_received"];
            $rules["$k.photo"] = ImageStore::RULES;
            $rules["$k.reject_reason"] = [
                Rule::requiredIf(fn () => (int) $request->input("$k.qty_rejected") > 0),
                'nullable', 'string', 'max:255',
            ];
            $attributes["$k.qty_received"] = "jumlah diterima {$item->product->name}";
            $attributes["$k.qty_rejected"] = "jumlah rusak/tidak sesuai {$item->product->name}";
            $attributes["$k.reject_reason"] = "alasan {$item->product->name}";
            $attributes["$k.photo"] = "foto bukti {$item->product->name}";
        }

        $request->validate($rules, [], $attributes);

        $location = Location::findOrFail($request->receive_location_id);
        $storedPhotos = []; // dibersihkan jika transaksi gagal, agar tidak ada file yatim

        try {
        DB::transaction(function () use ($request, $order, $stock, $location, &$storedPhotos) {
            $summary = [];
            $returnItems = [];

            foreach ($order->items as $item) {
                $received = (int) $request->input("items.{$item->id}.qty_received");
                $rejected = (int) $request->input("items.{$item->id}.qty_rejected");
                $reason = $rejected > 0 ? $request->input("items.{$item->id}.reject_reason") : null;

                $item->update(['qty_received' => $received, 'qty_rejected' => $rejected, 'reject_reason' => $reason]);

                $good = $item->qtyGood();
                if ($good > 0) {
                    $stock->change($item->product, $location, $good, 'in', $request->user(), $order, "Penerimaan {$order->code}", 'receive_location_id');
                }

                if ($rejected > 0) {
                    $photo = null;
                    if ($request->hasFile("items.{$item->id}.photo")) {
                        $photo = $storedPhotos[] = ImageStore::put($request->file("items.{$item->id}.photo"), 'returns');
                    }
                    $returnItems[] = ['product_id' => $item->product_id, 'qty' => $rejected, 'reason' => $reason, 'photo' => $photo];
                }

                $ref = $item->qty_prepared ?? $item->qty_ordered;
                if ($received !== $ref || $rejected > 0) {
                    $summary[] = "{$item->product->name}: dipesan {$item->qty_ordered}, nota {$ref}, diterima {$received}, rusak {$rejected}";
                }
            }

            $order->update([
                'status' => 'selesai',
                'checked_at' => now(),
                'confirmed_at' => now(),
                'arrival_temp' => $request->arrival_temp,
                'receive_location_id' => $location->id,
            ]);

            $note = 'Penerimaan dikonfirmasi, stok diperbarui di '.$location->name.'.';
            $note .= $summary ? ' Ketidaksesuaian: '.implode('; ', $summary).'.' : ' Semua barang sesuai.';
            if ($request->note) {
                $note .= ' Catatan: '.$request->note;
            }
            $order->addEvent($note, $request->user(), 'status', 'selesai');

            if ($returnItems) {
                $retur = ReturnRequest::create([
                    'code' => ReturnRequest::generateCode(),
                    'purchase_order_id' => $order->id,
                    'vendor_id' => $order->vendor_id,
                    'created_by' => $request->user()->id,
                    'status' => 'diajukan',
                    'note' => "Barang rusak/tidak sesuai dari {$order->code}.",
                ]);
                $retur->items()->createMany($returnItems);
                $order->addEvent("Retur {$retur->code} otomatis diajukan ke vendor.", $request->user(), 'note');
            }
        });
        } catch (\Throwable $e) {
            foreach ($storedPhotos as $path) {
                ImageStore::delete($path);
            }
            throw $e;
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Penerimaan dikonfirmasi dan stok diperbarui.');
    }
}
