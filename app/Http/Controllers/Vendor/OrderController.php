<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\ImageStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /** Vendor hanya boleh menyentuh pesanan miliknya. */
    private function own(Request $request, PurchaseOrder $order): PurchaseOrder
    {
        abort_unless($order->vendor_id === $request->user()->id, 403);

        return $order;
    }

    public function index(Request $request)
    {
        $orders = PurchaseOrder::where('vendor_id', $request->user()->id)->withCount('items')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(15)->withQueryString();

        return view('vendor-area.orders.index', compact('orders'));
    }

    public function show(Request $request, PurchaseOrder $order)
    {
        $this->own($request, $order)->load(['vendor', 'creator', 'items.product', 'events.user', 'receiveLocation', 'returnRequest']);

        return view('vendor-area.orders.show', compact('order'));
    }

    /** Tahap 2 — Konfirmasi / tolak pesanan. */
    public function respond(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->own($request, $order);
        abort_unless($order->status === 'menunggu', 422, 'Pesanan sudah direspons.');

        $data = $request->validate([
            'decision' => ['required', 'in:confirm,reject'],
            'vendor_note' => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
        ], [], ['vendor_note' => 'alasan penolakan']);

        $confirm = $data['decision'] === 'confirm';
        $status = $confirm ? 'dikonfirmasi' : 'ditolak';

        $order->update(['status' => $status, 'vendor_responded_at' => now(), 'vendor_note' => $data['vendor_note'] ?? null]);
        $order->addEvent(
            $confirm ? 'Vendor mengonfirmasi pesanan.' : 'Vendor menolak pesanan: '.$data['vendor_note'],
            $request->user(), 'status', $status
        );

        return back()->with('success', $confirm ? 'Pesanan dikonfirmasi.' : 'Pesanan ditolak.');
    }

    /** Tahap 3 — Isi jumlah yang benar-benar disiapkan; selisih wajib diberi alasan. */
    public function prepare(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->own($request, $order)->load('items.product');
        abort_unless($order->status === 'dikonfirmasi', 422, 'Pesanan belum dikonfirmasi.');

        $rules = ['prepare_note' => ['nullable', 'string', 'max:500']];
        $attrs = ['prepare_note' => 'alasan selisih'];
        foreach ($order->items as $item) {
            $rules["items.{$item->id}"] = ['required', 'integer', 'min:0', 'max:1000000'];
            $attrs["items.{$item->id}"] = "jumlah disiapkan {$item->product->name}";
        }
        $request->validate($rules, [], $attrs);

        $diff = [];
        foreach ($order->items as $item) {
            $qty = (int) $request->input("items.{$item->id}");
            if ($qty !== $item->qty_ordered) {
                $diff[] = "{$item->product->name}: dipesan {$item->qty_ordered}, disiapkan {$qty}";
            }
        }

        if ($diff && blank($request->prepare_note)) {
            throw ValidationException::withMessages(['prepare_note' => 'Ada selisih dari pesanan, mohon isi alasannya.']);
        }

        DB::transaction(function () use ($request, $order, $diff) {
            foreach ($order->items as $item) {
                $item->update(['qty_prepared' => (int) $request->input("items.{$item->id}")]);
            }
            $order->update(['status' => 'disiapkan']);
            $order->addEvent(
                'Barang disiapkan.'.($diff ? ' Selisih: '.implode('; ', $diff).'. Alasan: '.$request->prepare_note : ' Sesuai pesanan.'),
                $request->user(), 'status', 'disiapkan'
            );
        });

        return back()->with('success', 'Barang ditandai sudah disiapkan.');
    }

    /** Tahap 3 — Kirim: jadwal tiba, sopir/kendaraan, dan suhu saat berangkat. */
    public function ship(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->own($request, $order);
        abort_unless($order->status === 'disiapkan', 422, 'Barang belum disiapkan.');

        $data = $request->validate([
            'expected_arrival_at' => ['required', 'date', 'after:now'],
            'driver_info' => ['required', 'string', 'max:255'],
            'ship_temp' => ['nullable', 'numeric', 'between:-60,40'],
            'delivery_note' => ImageStore::RULES,
        ], [], ['expected_arrival_at' => 'perkiraan tiba', 'driver_info' => 'sopir/kendaraan', 'ship_temp' => 'suhu saat berangkat']);

        unset($data['delivery_note']);
        $data['delivery_note_photo'] = $request->hasFile('delivery_note') ? ImageStore::put($request->file('delivery_note'), 'notes') : null;

        $order->update($data + ['status' => 'dikirim', 'shipped_at' => now()]);
        $order->addEvent(
            "Barang dikirim ({$data['driver_info']}), perkiraan tiba ".\Illuminate\Support\Carbon::parse($data['expected_arrival_at'])->format('d/m/Y H:i').'.',
            $request->user(), 'status', 'dikirim'
        );

        return back()->with('success', 'Pesanan ditandai dikirim.');
    }

    /** Tahap 3 — Update posisi supaya toko bisa melacak. */
    public function track(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->own($request, $order);
        abort_unless($order->status === 'dikirim', 422, 'Pesanan tidak sedang dikirim.');

        $data = $request->validate(['note' => ['required', 'string', 'max:255']], [], ['note' => 'posisi/keterangan']);
        $order->addEvent($data['note'], $request->user(), 'tracking');

        return back()->with('success', 'Posisi pengiriman diperbarui.');
    }
}
