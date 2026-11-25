<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = ReturnRequest::with(['vendor', 'order'])->withCount('items')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.returns.index', compact('returns'));
    }

    public function show(ReturnRequest $returnRequest)
    {
        $returnRequest->load(['vendor', 'order', 'items.product', 'creator']);

        return view('admin.returns.show', ['retur' => $returnRequest]);
    }

    /** Tandai retur selesai (barang sudah diambil/diganti vendor). */
    public function complete(ReturnRequest $returnRequest): RedirectResponse
    {
        abort_unless($returnRequest->status === 'disetujui', 422, 'Retur belum disetujui vendor.');

        $returnRequest->update(['status' => 'selesai', 'completed_at' => now()]);

        return back()->with('success', 'Retur ditandai selesai.');
    }
}
