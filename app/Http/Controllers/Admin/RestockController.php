<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;

class RestockController extends Controller
{
    /** Tahap 1 — Identifikasi kebutuhan stok. */
    public function index()
    {
        $incoming = Product::incomingMap();

        $groups = Product::withTotals()->with('vendor')->orderBy('name')->get()
            ->filter(fn ($p) => $p->isLow())
            ->each(function ($p) use ($incoming) {
                $p->incoming = (int) ($incoming[$p->id] ?? 0);
                $p->suggested = $p->suggestedQty($p->incoming);
            })
            ->groupBy('vendor_id');

        return view('admin.restock.index', compact('groups'));
    }
}
