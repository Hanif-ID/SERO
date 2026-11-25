<div class="card mb-4">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><small class="text-muted d-block">Vendor</small><strong>{{ $retur->vendor->name }}</strong></div>
      <div class="col-md-3"><small class="text-muted d-block">Dari pesanan</small>
        <a href="{{ auth()->user()->isAdmin() ? route('admin.orders.show', $retur->order) : route('vendor.orders.show', $retur->order) }}">{{ $retur->order->code }}</a></div>
      <div class="col-md-3"><small class="text-muted d-block">Diajukan</small>{{ $retur->created_at->format('d/m/Y H:i') }}</div>
      <div class="col-md-3"><small class="text-muted d-block">Respons vendor</small>{{ $retur->responded_at?->format('d/m/Y H:i') ?? 'Belum' }}</div>
      @if ($retur->note)<div class="col-12"><small class="text-muted d-block">Catatan toko</small>{{ $retur->note }}</div>@endif
      @if ($retur->vendor_note)<div class="col-12"><small class="text-muted d-block">Catatan vendor</small>{{ $retur->vendor_note }}</div>@endif
      @if ($retur->completed_at)<div class="col-md-3"><small class="text-muted d-block">Selesai</small>{{ $retur->completed_at->format('d/m/Y H:i') }}</div>@endif
    </div>
  </div>
</div>
<div class="card mb-4">
  <h5 class="card-header">Barang yang diretur</h5>
  <div class="table-responsive text-nowrap">
    <table class="table">
      <thead><tr><th>Produk</th><th class="text-end">Jumlah</th><th>Alasan</th><th>Foto bukti</th></tr></thead>
      <tbody>
        @foreach ($retur->items as $i)
          <tr>
            <td>@include('shared._product-cell', ['product' => $i->product])</td>
            <td class="text-end">{{ $i->qty }} {{ $i->product->unit }}</td>
            <td>{{ $i->reason }}</td>
            <td>@if ($i->photo)<a href="{{ $i->photoUrl() }}" target="_blank" rel="noopener"><img src="{{ $i->photoUrl() }}" alt="Bukti {{ $i->product->name }}" width="56" height="56" class="rounded border" style="object-fit:cover"></a>@else<span class="text-muted">-</span>@endif</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
