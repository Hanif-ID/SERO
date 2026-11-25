@extends('layouts.app')
@section('title', 'Buat Pesanan')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card mb-4">
    <h5 class="card-header">Buat Pesanan ke Vendor</h5>
    <div class="card-body">
      <form method="GET" action="{{ route('admin.orders.create') }}">
        <label class="form-label" for="vendor_pick">1. Pilih vendor</label>
        <select id="vendor_pick" name="vendor_id" class="form-select" onchange="this.form.submit()">
          <option value="">— pilih vendor —</option>
          @foreach ($vendors as $v)<option value="{{ $v->id }}" @selected($vendor?->id === $v->id)>{{ $v->name }}</option>@endforeach
        </select>
        @if ($prefill)<input type="hidden" name="from" value="restock">@endif
      </form>
    </div>
  </div>

  @if ($vendor)
    <div class="card">
      <h5 class="card-header">2. Isi detail pesanan — {{ $vendor->name }}</h5>
      <div class="card-body">
        @include('layouts.errors')
        @if ($products->isEmpty())
          <p class="text-muted">Vendor ini belum punya produk. <a href="{{ route('admin.products.create') }}">Tambah produk</a></p>
        @else
        <form method="POST" action="{{ route('admin.orders.store') }}">
          @csrf
          <input type="hidden" name="vendor_id" value="{{ $vendor->id }}">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label" for="needed_date">Dibutuhkan tanggal</label>
              <input type="date" id="needed_date" name="needed_date" min="{{ today()->toDateString() }}" class="form-control" value="{{ old('needed_date', today()->addDays(2)->toDateString()) }}" required>
            </div>
            <div class="col-md-8 mb-3">
              <label class="form-label" for="notes">Catatan untuk vendor</label>
              <input type="text" id="notes" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="mis. kirim pagi hari, jaga suhu -18°C">
            </div>
          </div>

          <div class="table-responsive text-nowrap mb-3">
            <table class="table">
              <thead><tr><th>Produk</th><th class="text-end">Stok</th><th class="text-end">Min / Target</th><th class="text-end">Sudah dipesan</th><th style="width:140px">Jumlah pesan</th></tr></thead>
              <tbody>
                @foreach ($products as $p)
                  @php
                    $inc = (int) ($incoming[$p->id] ?? 0);
                    $default = $prefill && $p->isLow() ? $p->suggestedQty($inc) : 0;
                  @endphp
                  <tr>
                    <td>@include('shared._product-cell', ['product' => $p, 'sub' => $p->sku.' · '.$p->unit])</td>
                    <td class="text-end"><span class="badge bg-label-{{ $p->isLow() ? 'danger' : 'success' }}">{{ $p->total() }}</span></td>
                    <td class="text-end">{{ $p->min_stock }} / {{ $p->target_stock }}</td>
                    <td class="text-end">{{ $inc }}</td>
                    <td><input type="number" min="0" name="items[{{ $p->id }}]" class="form-control form-control-sm" value="{{ old("items.{$p->id}", $default) }}"></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <button class="btn btn-primary me-2">Kirim Pesanan</button>
          <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Batal</a>
        </form>
        @endif
      </div>
    </div>
  @endif
</div>
@endsection
