@extends('layouts.app')
@section('title', 'Pesanan Masuk')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Pesanan Masuk</h5>
      <form method="GET">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua status</option>
          @foreach (\App\Models\PurchaseOrder::STATUS as $k => [$label])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>@endforeach
        </select>
      </form>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead><tr><th>Kode</th><th>Status</th><th>Dibutuhkan</th><th class="text-end">Item</th><th>Diterima</th><th></th></tr></thead>
        <tbody>
          @forelse ($orders as $o)
            <tr>
              <td><strong>{{ $o->code }}</strong></td>
              <td><span class="badge bg-label-{{ $o->statusColor() }}">{{ $o->statusLabel() }}</span>
                @if ($o->isShipmentLate())<span class="badge bg-label-danger">terlambat</span>@endif</td>
              <td>{{ $o->needed_date->format('d/m/Y') }}</td>
              <td class="text-end">{{ $o->items_count }}</td>
              <td>{{ $o->created_at->format('d/m/Y H:i') }}</td>
              <td><a href="{{ route('vendor.orders.show', $o) }}" class="btn btn-sm btn-outline-primary">Buka</a></td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pesanan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
  </div>
</div>
@endsection
