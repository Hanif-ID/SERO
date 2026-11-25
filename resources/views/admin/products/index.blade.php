@extends('layouts.app')
@section('title', 'Produk')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  <div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <h5 class="mb-0">Produk</h5>
      <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2">
          <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari nama / SKU">
          <button class="btn btn-sm btn-outline-secondary">Cari</button>
        </form>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">Tambah Produk</a>
      </div>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead><tr><th>SKU</th><th>Nama</th><th>Vendor</th><th class="text-end">Stok</th><th class="text-end">Min</th><th class="text-end">Target</th><th>Aksi</th></tr></thead>
        <tbody>
          @forelse ($products as $p)
            <tr>
              <td>{{ $p->sku }}</td>
              <td>@include('shared._product-cell', ['product' => $p])</td>
              <td>{{ $p->vendor->name }}</td>
              <td class="text-end"><span class="badge bg-label-{{ $p->isLow() ? 'danger' : 'success' }}">{{ $p->total() }} {{ $p->unit }}</span></td>
              <td class="text-end">{{ $p->min_stock }}</td>
              <td class="text-end">{{ $p->target_stock }}</td>
              <td>
                <a href="{{ route('admin.products.edit', $p) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                <form action="{{ route('admin.products.destroy', $p) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada produk.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($products->hasPages())<div class="card-footer">{{ $products->links() }}</div>@endif
  </div>
</div>
@endsection
