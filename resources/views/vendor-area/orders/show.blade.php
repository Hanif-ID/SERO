@extends('layouts.app')
@section('title', $order->code)
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  @include('layouts.errors')

  <div class="mb-3">
    <a href="{{ route('vendor.orders.index') }}" class="text-muted small">&larr; Semua pesanan</a>
    <h4 class="mb-0">{{ $order->code }} <span class="badge bg-label-{{ $order->statusColor() }} ms-2">{{ $order->statusLabel() }}</span></h4>
  </div>

  @include('shared._order-flags')
  @include('shared._order-info')

  {{-- Konfirmasi / tolak --}}
  @if ($order->status === 'menunggu')
    <div class="card mb-4">
      <h5 class="card-header">Konfirmasi pesanan</h5>
      <div class="card-body">
        <form method="POST" action="{{ route('vendor.orders.respond', $order) }}" class="row g-2 align-items-end">
          @csrf
          <div class="col-md-6">
            <label class="form-label" for="vendor_note">Alasan (wajib jika menolak)</label>
            <input type="text" id="vendor_note" name="vendor_note" class="form-control" value="{{ old('vendor_note') }}">
          </div>
          <div class="col-md-6 d-flex gap-2">
            <button name="decision" value="confirm" class="btn btn-success">Konfirmasi</button>
            <button name="decision" value="reject" class="btn btn-outline-danger">Tolak</button>
          </div>
        </form>
      </div>
    </div>
  @endif

  {{-- Siapkan barang --}}
  @if ($order->status === 'dikonfirmasi')
    <form method="POST" action="{{ route('vendor.orders.prepare', $order) }}" class="card mb-4">
      @csrf
      <h5 class="card-header">Siapkan barang</h5>
      <div class="card-body">
        <p class="text-muted">Isi jumlah yang benar-benar disiapkan. Jika berbeda dari pesanan, tulis alasannya agar toko tidak kaget saat barang tiba.</p>
        <div class="table-responsive text-nowrap mb-3">
          <table class="table">
            <thead><tr><th>Produk</th><th class="text-end">Dipesan</th><th style="width:160px">Disiapkan</th></tr></thead>
            <tbody>
              @foreach ($order->items as $item)
                <tr>
                  <td>@include('shared._product-cell', ['product' => $item->product])</td>
                  <td class="text-end">{{ $item->qty_ordered }} {{ $item->product->unit }}</td>
                  <td><input type="number" min="0" name="items[{{ $item->id }}]" class="form-control form-control-sm" value="{{ old("items.{$item->id}", $item->qty_ordered) }}" required></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mb-3"><label class="form-label" for="prepare_note">Alasan selisih (jika ada)</label>
          <input type="text" id="prepare_note" name="prepare_note" class="form-control" value="{{ old('prepare_note') }}"></div>
        <button class="btn btn-primary">Tandai Sudah Disiapkan</button>
      </div>
    </form>
  @else
    @include('shared._order-items')
  @endif

  {{-- Kirim --}}
  @if ($order->status === 'disiapkan')
    <form method="POST" action="{{ route('vendor.orders.ship', $order) }}" enctype="multipart/form-data" class="card mb-4">
      @csrf
      <h5 class="card-header">Kirim pesanan</h5>
      <div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label" for="expected_arrival_at">Perkiraan tiba</label>
          <input type="datetime-local" id="expected_arrival_at" name="expected_arrival_at" class="form-control" value="{{ old('expected_arrival_at', now()->addDay()->format('Y-m-d\TH:i')) }}" required></div>
        <div class="col-md-5"><label class="form-label" for="driver_info">Sopir / kendaraan</label>
          <input type="text" id="driver_info" name="driver_info" class="form-control" value="{{ old('driver_info') }}" placeholder="mis. Budi — B 1234 XY (box pendingin)" required></div>
        <div class="col-md-3"><label class="form-label" for="ship_temp">Suhu berangkat (°C)</label>
          <input type="number" step="0.1" id="ship_temp" name="ship_temp" class="form-control" value="{{ old('ship_temp') }}" placeholder="-18"></div>
        <div class="col-md-6"><label class="form-label" for="delivery_note">Foto nota / surat jalan <small class="text-muted">(opsional, JPG/PNG/WEBP maks 2 MB)</small></label>
          <input type="file" id="delivery_note" name="delivery_note" class="form-control @error('delivery_note') is-invalid @enderror" accept="image/jpeg,image/png,image/webp" capture="environment">
          @error('delivery_note')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-12"><button class="btn btn-primary">Tandai Dikirim</button></div>
      </div>
    </form>
  @endif

  {{-- Update posisi --}}
  @if ($order->status === 'dikirim')
    <form method="POST" action="{{ route('vendor.orders.track', $order) }}" class="card mb-4">
      @csrf
      <h5 class="card-header">Update posisi pengiriman</h5>
      <div class="card-body d-flex gap-2">
        <input type="text" name="note" class="form-control" placeholder="mis. Melewati Tol Cikampek KM 40, estimasi 2 jam lagi" required>
        <button class="btn btn-outline-primary text-nowrap">Kirim Update</button>
      </div>
    </form>
  @endif

  @include('shared._order-timeline')
</div>
@endsection
