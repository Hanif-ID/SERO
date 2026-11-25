@extends('layouts.app')
@section('title', 'Dashboard Vendor')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  <h4 class="mb-4">Halo, {{ auth()->user()->name }} 👋</h4>

  <div class="row g-4 mb-4">
    @php
      $cards = [
        ['Perlu dikonfirmasi', $needResponse, 'bx-time', $needResponse ? 'warning' : 'success', route('vendor.orders.index', ['status' => 'menunggu'])],
        ['Perlu disiapkan', $needPrepare, 'bx-package', $needPrepare ? 'info' : 'success', route('vendor.orders.index', ['status' => 'dikonfirmasi'])],
        ['Siap dikirim', $needShip, 'bx-box', $needShip ? 'primary' : 'success', route('vendor.orders.index', ['status' => 'disiapkan'])],
        ['Dalam pengiriman', $shipping, 'bx-truck', 'primary', route('vendor.orders.index', ['status' => 'dikirim'])],
        ['Retur perlu respons', $returnsPending, 'bx-undo', $returnsPending ? 'danger' : 'success', route('vendor.returns.index', ['status' => 'diajukan'])],
      ];
    @endphp
    @foreach ($cards as [$label, $value, $icon, $color, $url])
      <div class="col-6 col-lg-4 col-xl">
        <a href="{{ $url }}" class="card h-100 text-decoration-none"><div class="card-body">
          <span class="badge bg-label-{{ $color }} p-2 mb-2"><i class="bx {{ $icon }} fs-4"></i></span>
          <div class="text-muted small">{{ $label }}</div><h3 class="mb-0">{{ $value }}</h3>
        </div></a>
      </div>
    @endforeach
  </div>

  <div class="card">
    <h5 class="card-header">Pesanan yang sedang berjalan</h5>
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead><tr><th>Kode</th><th>Status</th><th>Dibutuhkan</th><th class="text-end">Item</th><th></th></tr></thead>
        <tbody>
          @forelse ($orders as $o)
            <tr>
              <td><strong>{{ $o->code }}</strong></td>
              <td><span class="badge bg-label-{{ $o->statusColor() }}">{{ $o->statusLabel() }}</span>
                @if ($o->isShipmentLate())<span class="badge bg-label-danger">terlambat</span>@endif</td>
              <td>{{ $o->needed_date->format('d/m/Y') }}</td>
              <td class="text-end">{{ $o->items_count }}</td>
              <td><a href="{{ route('vendor.orders.show', $o) }}" class="btn btn-sm btn-outline-primary">Buka</a></td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada pesanan berjalan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
