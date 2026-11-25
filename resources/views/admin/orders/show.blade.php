@extends('layouts.app')
@section('title', $order->code)
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  @include('layouts.errors')

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <a href="{{ route('admin.orders.index') }}" class="text-muted small">&larr; Semua pesanan</a>
      <h4 class="mb-0">{{ $order->code }} <span class="badge bg-label-{{ $order->statusColor() }} ms-2">{{ $order->statusLabel() }}</span></h4>
    </div>
    <div class="d-flex gap-2">
      @if (in_array($order->status, ['menunggu', 'dikonfirmasi']))
        <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan ini?')">@csrf
          <button class="btn btn-outline-danger">Batalkan Pesanan</button></form>
      @endif
      @if ($order->status === 'dikirim')
        <form method="POST" action="{{ route('admin.orders.inspect', $order) }}">@csrf
          <button class="btn btn-primary"><i class="bx bx-package me-1"></i>Barang Tiba — Mulai Periksa</button></form>
      @endif
      @if ($order->returnRequest)
        <a href="{{ route('admin.returns.show', $order->returnRequest) }}" class="btn btn-outline-warning">Lihat Retur {{ $order->returnRequest->code }}</a>
      @endif
    </div>
  </div>

  @include('shared._order-flags')
  @include('shared._order-info')

  @if ($order->status === 'diperiksa')
    <form method="POST" action="{{ route('admin.orders.confirm', $order) }}" enctype="multipart/form-data" class="card mb-4">
      @csrf
      <h5 class="card-header">Pemeriksaan &amp; Konfirmasi Penerimaan</h5>
      <div class="card-body">
        <p class="text-muted">Cocokkan jumlah fisik dengan nota vendor. Barang rusak/tidak sesuai otomatis dibuatkan retur, dan hanya barang baik yang masuk stok.</p>
        <div class="table-responsive text-nowrap mb-3">
          <table class="table">
            <thead><tr><th>Produk</th><th class="text-end">Dipesan</th><th class="text-end">Nota vendor</th><th style="width:130px">Diterima</th><th style="width:130px">Rusak/Tidak sesuai</th><th style="min-width:220px">Alasan (jika ada)</th><th style="min-width:200px">Foto bukti (jika rusak)</th></tr></thead>
            <tbody>
              @foreach ($order->items as $item)
                @php($ref = $item->qty_prepared ?? $item->qty_ordered)
                <tr>
                  <td>@include('shared._product-cell', ['product' => $item->product])</td>
                  <td class="text-end">{{ $item->qty_ordered }}</td>
                  <td class="text-end">{{ $ref }}</td>
                  <td><input type="number" min="0" class="form-control form-control-sm" name="items[{{ $item->id }}][qty_received]" value="{{ old("items.{$item->id}.qty_received", $ref) }}" required></td>
                  <td><input type="number" min="0" class="form-control form-control-sm" name="items[{{ $item->id }}][qty_rejected]" value="{{ old("items.{$item->id}.qty_rejected", 0) }}"></td>
                  <td><input type="text" class="form-control form-control-sm" name="items[{{ $item->id }}][reject_reason]" value="{{ old("items.{$item->id}.reject_reason") }}" placeholder="mis. kemasan bocor / sudah mencair"></td>
                  <td><input type="file" class="form-control form-control-sm" name="items[{{ $item->id }}][photo]" accept="image/jpeg,image/png,image/webp" capture="environment"></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label" for="receive_location_id">Simpan ke freezer</label>
            <select id="receive_location_id" name="receive_location_id" class="form-select" required>
              <option value="">— pilih freezer —</option>
              @foreach ($freezers as $f)
                <option value="{{ $f->id }}" @selected(old('receive_location_id') == $f->id)>
                  {{ $f->name }} — {{ $f->capacity ? 'sisa '.max(0, $f->capacity - (int) $f->used).' dari '.$f->capacity : 'tanpa batas' }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label" for="arrival_temp">Suhu saat tiba (°C)</label>
            <input type="number" step="0.1" id="arrival_temp" name="arrival_temp" class="form-control" value="{{ old('arrival_temp') }}" placeholder="-18">
          </div>
          <div class="col-md-5 mb-3">
            <label class="form-label" for="note">Catatan</label>
            <input type="text" id="note" name="note" class="form-control" value="{{ old('note') }}">
          </div>
        </div>
        <button class="btn btn-success" onclick="return confirm('Konfirmasi penerimaan? Stok akan diperbarui.')">Konfirmasi Penerimaan &amp; Update Stok</button>
      </div>
    </form>
  @else
    @include('shared._order-items')
  @endif

  @include('shared._order-timeline')
</div>
@endsection
