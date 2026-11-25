@extends('layouts.app')
@section('title', 'Riwayat Stok')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card">
    <div class="card-header">
      <h5 class="mb-3">Riwayat Stok</h5>
      <form method="GET" class="row g-2">
        <div class="col-md-4"><select name="product_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua produk</option>
          @foreach ($products as $p)<option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><select name="location_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua lokasi</option>
          @foreach ($locations as $l)<option value="{{ $l->id }}" @selected(request('location_id') == $l->id)>{{ $l->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua jenis</option>
          @foreach (\App\Models\StockMovement::TYPES as $k => [$label])<option value="{{ $k }}" @selected(request('type') === $k)>{{ $label }}</option>@endforeach</select></div>
      </form>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead><tr><th>Waktu</th><th>Produk</th><th>Lokasi</th><th>Jenis</th><th class="text-end">Jumlah</th><th class="text-end">Saldo</th><th>Oleh</th><th>Catatan</th></tr></thead>
        <tbody>
          @forelse ($movements as $m)
            <tr>
              <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
              <td>{{ $m->product->name }}</td>
              <td>{{ $m->location->name }}</td>
              <td><span class="badge bg-label-{{ $m->typeColor() }}">{{ $m->typeLabel() }}</span></td>
              <td class="text-end {{ $m->qty < 0 ? 'text-danger' : 'text-success' }}">{{ $m->qty > 0 ? '+' : '' }}{{ $m->qty }}</td>
              <td class="text-end">{{ $m->balance_after }}</td>
              <td>{{ $m->user?->name ?? 'Sistem' }}</td>
              <td class="text-wrap" style="max-width:280px">{{ $m->note }}</td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada pergerakan stok.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($movements->hasPages())<div class="card-footer">{{ $movements->links() }}</div>@endif
  </div>
</div>
@endsection
