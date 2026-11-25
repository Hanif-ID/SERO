@extends('layouts.app')
@section('title', 'Pindah & Opname Stok')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  @include('layouts.errors')

  <div class="row g-4">
    <div class="col-lg-6">
      <form method="POST" action="{{ route('admin.stock.transfer') }}" class="card h-100">
        @csrf
        <h5 class="card-header">Pindahkan stok (freezer ⇄ etalase)</h5>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Produk</label>
            <select name="product_id" class="form-select" required>
              <option value="">— pilih produk —</option>
              @foreach ($products as $p)<option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->name }} (total {{ $p->total() }})</option>@endforeach
            </select>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Dari</label>
              <select name="from_location_id" class="form-select" required><option value="">— asal —</option>
                @foreach ($locations as $l)<option value="{{ $l->id }}" @selected(old('from_location_id') == $l->id)>{{ $l->name }}</option>@endforeach</select></div>
            <div class="col-md-6 mb-3"><label class="form-label">Ke</label>
              <select name="to_location_id" class="form-select" required><option value="">— tujuan —</option>
                @foreach ($locations as $l)<option value="{{ $l->id }}" @selected(old('to_location_id') == $l->id)>{{ $l->name }}{{ $l->capacity ? ' (sisa '.max(0, $l->capacity - (int) $l->used).')' : '' }}</option>@endforeach</select></div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3"><label class="form-label">Jumlah</label><input type="number" min="1" name="qty" class="form-control" value="{{ old('qty') }}" required></div>
            <div class="col-md-8 mb-3"><label class="form-label">Catatan</label><input type="text" name="note" class="form-control" value="{{ old('note') }}"></div>
          </div>
          <button class="btn btn-primary">Pindahkan</button>
        </div>
      </form>
    </div>

    <div class="col-lg-6">
      <form method="POST" action="{{ route('admin.stock.adjust') }}" class="card h-100">
        @csrf
        <h5 class="card-header">Stok opname (samakan dengan hitung fisik)</h5>
        <div class="card-body">
          <p class="text-muted small">Masukkan jumlah hasil hitung langsung di lokasi. Selisih dengan sistem otomatis dicatat sebagai penyesuaian di riwayat stok.</p>
          <div class="mb-3">
            <label class="form-label">Produk</label>
            <select name="product_id" class="form-select" required>
              <option value="">— pilih produk —</option>
              @foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
            </select>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3"><label class="form-label">Lokasi</label>
              <select name="location_id" class="form-select" required><option value="">— lokasi —</option>
                @foreach ($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select></div>
            <div class="col-md-6 mb-3"><label class="form-label">Jumlah hasil hitung</label><input type="number" min="0" name="counted_qty" class="form-control" required></div>
          </div>
          <div class="mb-3"><label class="form-label">Alasan selisih</label><input type="text" name="note" class="form-control" placeholder="mis. opname mingguan / barang rusak" required></div>
          <button class="btn btn-warning">Sesuaikan Stok</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
