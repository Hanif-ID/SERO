@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  <h4 class="mb-4">Halo, {{ auth()->user()->name }} 👋</h4>

  <div class="row g-4 mb-4">
    @php
      $cards = [
        ['Perlu restock', $low->count(), 'bx-error-circle', $low->count() ? 'danger' : 'success', route('admin.restock.index')],
        ['Pesanan berjalan', $active->count(), 'bx-receipt', 'primary', route('admin.orders.index')],
        ['Vendor lambat respons', $lateResponse->count(), 'bx-time', $lateResponse->count() ? 'warning' : 'success', route('admin.orders.index', ['status' => 'menunggu'])],
        ['Pengiriman terlambat', $lateShipment->count(), 'bx-truck', $lateShipment->count() ? 'danger' : 'success', route('admin.orders.index')],
        ['Sedang diperiksa', $inInspection->count(), 'bx-search-alt', $inInspection->count() ? 'warning' : 'secondary', route('admin.orders.index', ['status' => 'diperiksa'])],
        ['Retur terbuka', $openReturns, 'bx-undo', $openReturns ? 'warning' : 'success', route('admin.returns.index')],
      ];
    @endphp
    @foreach ($cards as [$label, $value, $icon, $color, $url])
      <div class="col-6 col-lg-4 col-xl-2">
        <a href="{{ $url }}" class="card h-100 text-decoration-none">
          <div class="card-body">
            <span class="badge bg-label-{{ $color }} p-2 mb-2"><i class="bx {{ $icon }} fs-4"></i></span>
            <div class="text-muted small">{{ $label }}</div>
            <h3 class="mb-0">{{ $value }}</h3>
          </div>
        </a>
      </div>
    @endforeach
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Stok yang perlu dipesan</h5>
          <a href="{{ route('admin.restock.index') }}" class="btn btn-sm btn-outline-primary">Lihat semua</a>
        </div>
        <div class="table-responsive text-nowrap">
          <table class="table">
            <thead><tr><th>Produk</th><th class="text-end">Stok</th><th class="text-end">Min</th><th class="text-end">Sudah dipesan</th></tr></thead>
            <tbody>
              @forelse ($low->take(8) as $p)
                <tr>
                  <td>@include('shared._product-cell', ['product' => $p, 'sub' => $p->vendor->name])</td>
                  <td class="text-end"><span class="badge bg-label-danger">{{ $p->total() }}</span></td>
                  <td class="text-end">{{ $p->min_stock }}</td>
                  <td class="text-end">{{ $p->incoming }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Semua stok di atas batas minimum 🎉</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card h-100">
        <h5 class="card-header">Kapasitas penyimpanan</h5>
        <div class="card-body">
          @forelse ($locations as $loc)
            <div class="mb-3">
              <div class="d-flex justify-content-between"><strong>{{ $loc->name }}</strong>
                <span class="badge bg-label-{{ $loc->type === 'freezer' ? 'info' : 'primary' }}">{{ $loc->type === 'freezer' ? 'Freezer' : 'Etalase' }}</span></div>
              @include('shared._capacity', ['loc' => $loc])
            </div>
          @empty
            <p class="text-muted mb-0">Belum ada lokasi. <a href="{{ route('admin.locations.create') }}">Tambah lokasi</a></p>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <h5 class="card-header">Aktivitas terbaru</h5>
        <ul class="list-group list-group-flush">
          @forelse ($events as $e)
            <li class="list-group-item">
              <a href="{{ route('admin.orders.show', $e->order) }}"><strong>{{ $e->order->code }}</strong></a>
              — {{ $e->note }}
              <small class="text-muted d-block">{{ $e->created_at->diffForHumans() }} · {{ $e->user?->name ?? 'Sistem' }}</small>
            </li>
          @empty
            <li class="list-group-item text-muted">Belum ada aktivitas.</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
