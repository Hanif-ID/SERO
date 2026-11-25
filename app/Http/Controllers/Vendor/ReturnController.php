<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    private function own(Request $request, ReturnRequest $retur): ReturnRequest
    {
        abort_unless($retur->vendor_id === $request->user()->id, 403);

        return $retur;
    }

    public function index(Request $request)
    {
        $returns = ReturnRequest::with('order')->withCount('items')
            ->where('vendor_id', $request->user()->id)
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(15)->withQueryString();

        return view('vendor-area.returns.index', compact('returns'));
    }

    public function show(Request $request, ReturnRequest $returnRequest)
    {
        $this->own($request, $returnRequest)->load(['order', 'items.product', 'creator']);

        return view('vendor-area.returns.show', ['retur' => $returnRequest]);
    }

    public function respond(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        $this->own($request, $returnRequest);
        abort_unless($returnRequest->status === 'diajukan', 422, 'Retur sudah direspons.');

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'vendor_note' => ['required_if:decision,reject', 'nullable', 'string', 'max:500'],
        ], [], ['vendor_note' => 'alasan penolakan']);

        $returnRequest->update([
            'status' => $data['decision'] === 'approve' ? 'disetujui' : 'ditolak',
            'vendor_note' => $data['vendor_note'] ?? null,
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Respons retur tersimpan.');
    }
}
