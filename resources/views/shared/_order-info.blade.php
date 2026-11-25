<div class="card mb-4">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><small class="text-muted d-block">Vendor</small><strong>{{ $order->vendor->name }}</strong></div>
      <div class="col-md-3"><small class="text-muted d-block">Dibuat</small>{{ $order->created_at->format('d/m/Y H:i') }} oleh {{ $order->creator->name }}</div>
      <div class="col-md-3"><small class="text-muted d-block">Dibutuhkan tanggal</small>{{ $order->needed_date->format('d/m/Y') }}</div>
      <div class="col-md-3"><small class="text-muted d-block">Respons vendor</small>{{ $order->vendor_responded_at?->format('d/m/Y H:i') ?? 'Belum' }}</div>
      @if ($order->shipped_at)
        <div class="col-md-3"><small class="text-muted d-block">Dikirim</small>{{ $order->shipped_at->format('d/m/Y H:i') }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Perkiraan tiba</small>{{ $order->expected_arrival_at?->format('d/m/Y H:i') ?? '-' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Sopir / kendaraan</small>{{ $order->driver_info ?? '-' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Suhu berangkat</small>{{ $order->ship_temp !== null ? $order->ship_temp.'°C' : '-' }}</div>
        @if ($order->delivery_note_photo)
          <div class="col-md-3"><small class="text-muted d-block mb-1">Foto nota / surat jalan</small>
            <a href="{{ $order->deliveryNoteUrl() }}" target="_blank" rel="noopener"><img src="{{ $order->deliveryNoteUrl() }}" alt="Foto nota" width="64" height="64" class="rounded border" style="object-fit:cover"></a></div>
        @endif
      @endif
      @if ($order->arrived_at)
        <div class="col-md-3"><small class="text-muted d-block">Barang tiba</small>{{ $order->arrived_at->format('d/m/Y H:i') }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Suhu tiba</small>{{ $order->arrival_temp !== null ? $order->arrival_temp.'°C' : '-' }}</div>
        <div class="col-md-3"><small class="text-muted d-block">Lama pemeriksaan</small>{{ $order->inspectionMinutes() }} menit</div>
        <div class="col-md-3"><small class="text-muted d-block">Masuk ke</small>{{ $order->receiveLocation?->name ?? '-' }}</div>
      @endif
      @if ($order->notes)
        <div class="col-12"><small class="text-muted d-block">Catatan pesanan</small>{{ $order->notes }}</div>
      @endif
      @if ($order->vendor_note)
        <div class="col-12"><small class="text-muted d-block">Catatan vendor</small>{{ $order->vendor_note }}</div>
      @endif
    </div>
  </div>
</div>
