<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /** Tahap 7 — Stok per lokasi + pencarian cepat "barang ada di mana". */
    public function index(Request $request)
    {
        $products = Product::withTotals()->with(['vendor', 'stocks.location'])
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%")))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.stock.index', [
            'products' => $products,
            'locations' => Location::withSum('stocks as used', 'qty')->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    /** Tahap 6 — Riwayat/buku besar stok. */
    public function movements(Request $request)
    {
        $movements = StockMovement::with(['product', 'location', 'user'])
            ->when($request->product_id, fn ($q, $v) => $q->where('product_id', $v))
            ->when($request->location_id, fn ($q, $v) => $q->where('location_id', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.stock.movements', [
            'movements' => $movements,
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'locations' => Location::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function manage()
    {
        return view('admin.stock.manage', [
            'products' => Product::withTotals()->orderBy('name')->get(),
            'locations' => Location::withSum('stocks as used', 'qty')->orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function transfer(Request $request, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'from_location_id' => ['required', 'exists:locations,id'],
            'to_location_id' => ['required', 'exists:locations,id', 'different:from_location_id'],
            'qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [], ['from_location_id' => 'lokasi asal', 'to_location_id' => 'lokasi tujuan', 'qty' => 'jumlah']);

        $stock->transfer(
            Product::findOrFail($data['product_id']),
            Location::findOrFail($data['from_location_id']),
            Location::findOrFail($data['to_location_id']),
            (int) $data['qty'],
            $request->user(),
            $data['note'] ?? null,
        );

        return back()->with('success', 'Stok berhasil dipindahkan.');
    }

    public function adjust(Request $request, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'counted_qty' => ['required', 'integer', 'min:0', 'max:10000000'],
            'note' => ['required', 'string', 'max:200'],
        ], [], ['counted_qty' => 'jumlah hasil hitung', 'location_id' => 'lokasi', 'note' => 'alasan']);

        $movement = $stock->adjustTo(
            Product::findOrFail($data['product_id']),
            Location::findOrFail($data['location_id']),
            (int) $data['counted_qty'],
            $request->user(),
            $data['note'],
        );

        return back()->with('success', $movement ? 'Stok disesuaikan dengan hasil hitung fisik.' : 'Stok sistem sudah sama dengan hasil hitung.');
    }
}
