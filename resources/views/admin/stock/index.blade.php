@extends('layouts.app')
@section('title', 'Stok & Lokasi')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')

  <div class="row g-3 mb-4">
    @foreach ($locations as $loc)
      <div class="col-6 col-md-4 col-xl-3">
        <div class="card h-100"><div class="card-body">
          <div class="d-flex justify-content-between mb-1"><strong>{{ $loc->name }}</strong>
            <span class="badge bg-label-{{ $loc->type === 'freezer' ? 'info' : 'primary' }}">{{ $loc->type === 'freezer' ? 'Freezer' : 'Etalase' }}</span></div>
          @include('shared._capacity', ['loc' => $loc])
        </div></div>
      </div>
    @endforeach
  </div>

  <div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <h5 class="mb-0">Barang ada di mana?</h5>
      <form method="GET" class="d-flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari nama / SKU">
        <button class="btn btn-sm btn-outline-secondary">Cari</button>
      </form>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead><tr><th>Produk</th><th class="text-end">Total</th><th>Lokasi</th></tr></thead>
        <tbody>
          @forelse ($products as $p)
            <tr>
              <td>@include('shared._product-cell', ['product' => $p, 'sub' => $p->sku])</td>
              <td class="text-end"><span class="badge bg-label-{{ $p->isLow() ? 'danger' : 'success' }}">{{ $p->total() }} {{ $p->unit }}</span></td>
              <td>
                @forelse ($p->stocks->where('qty', '>', 0) as $s)
                  <span class="badge bg-label-{{ $s->location->type === 'freezer' ? 'info' : 'primary' }} me-1">{{ $s->location->name }}: {{ $s->qty }}</span>
                @empty
                  <span class="text-muted">Belum ada stok</span>
                @endforelse
                @if ($p->stocks->where('qty', '>', 0)->where('location.type', 'display')->isEmpty() && $p->total() > 0)
                  <span class="badge bg-label-warning">belum di etalase</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-center text-muted py-4">Produk tidak ditemukan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($products->hasPages())<div class="card-footer">{{ $products->links() }}</div>@endif
  </div>
</div>
@endsection
