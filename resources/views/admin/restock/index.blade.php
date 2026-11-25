@extends('layouts.app')
@section('title', 'Kebutuhan Stok')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  <div class="mb-4">
    <h4 class="mb-1">Kebutuhan Stok</h4>
    <p class="text-muted mb-0">Produk yang stoknya sudah di batas minimum. Saran jumlah = stok target − stok sekarang − yang sudah dalam pesanan, jadi tidak memesan dobel.</p>
  </div>

  @forelse ($groups as $vendorId => $items)
    @php($vendor = $items->first()->vendor)
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ $vendor->name }}</h5>
        @if ($items->sum('suggested') > 0)
          <a href="{{ route('admin.orders.create', ['vendor_id' => $vendorId, 'from' => 'restock']) }}" class="btn btn-primary btn-sm">Buat Pesanan</a>
        @else
          <span class="badge bg-label-info">Semua sudah dalam pesanan</span>
        @endif
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table">
          <thead><tr><th>Produk</th><th class="text-end">Stok</th><th class="text-end">Min</th><th class="text-end">Target</th><th class="text-end">Sudah dipesan</th><th class="text-end">Saran pesan</th></tr></thead>
          <tbody>
            @foreach ($items as $p)
              <tr>
                <td>@include('shared._product-cell', ['product' => $p, 'sub' => $p->sku])</td>
                <td class="text-end"><span class="badge bg-label-{{ $p->total() === 0 ? 'danger' : 'warning' }}">{{ $p->total() }}</span></td>
                <td class="text-end">{{ $p->min_stock }}</td>
                <td class="text-end">{{ $p->target_stock }}</td>
                <td class="text-end">{{ $p->incoming }}</td>
                <td class="text-end"><strong>{{ $p->suggested }}</strong> {{ $p->unit }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @empty
    <div class="card"><div class="card-body text-center text-muted py-5">Semua stok di atas batas minimum 🎉</div></div>
  @endforelse
</div>
@endsection
