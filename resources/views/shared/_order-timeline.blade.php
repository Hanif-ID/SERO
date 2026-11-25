<div class="card mb-4">
  <h5 class="card-header">Riwayat &amp; Pelacakan</h5>
  <ul class="list-group list-group-flush">
    @forelse ($order->events as $e)
      <li class="list-group-item d-flex gap-3">
        <i class="bx {{ $e->type === 'tracking' ? 'bx-map-pin text-info' : ($e->type === 'note' ? 'bx-note text-secondary' : 'bx-check-circle text-primary') }} fs-4"></i>
        <div>
          <div>
            @if ($e->status)<span class="badge bg-label-{{ \App\Models\PurchaseOrder::STATUS[$e->status][1] ?? 'secondary' }} me-1">{{ \App\Models\PurchaseOrder::STATUS[$e->status][0] ?? $e->status }}</span>@endif
            @if ($e->type === 'tracking')<span class="badge bg-label-info me-1">Posisi</span>@endif
            {{ $e->note }}
          </div>
          <small class="text-muted">{{ $e->created_at->format('d/m/Y H:i') }} · {{ $e->user?->name ?? 'Sistem' }}</small>
        </div>
      </li>
    @empty
      <li class="list-group-item text-muted">Belum ada riwayat.</li>
    @endforelse
  </ul>
</div>
