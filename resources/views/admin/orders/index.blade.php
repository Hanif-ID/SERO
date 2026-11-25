@extends('layouts.app')
@section('title', 'Pesanan Vendor')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  <div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <h5 class="mb-0">Pesanan Vendor</h5>
      <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2">
          <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Semua status</option>
            @foreach (\App\Models\PurchaseOrder::STATUS as $k => [$label])
              <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
            @endforeach
          </select>
          <select name="vendor_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Semua vendor</option>
            @foreach ($vendors as $v)<option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->name }}</option>@endforeach
          </select>
        </form>
        <a href="{{ route('admin.orders.create') }}" class="btn btn-primary btn-sm">Buat Pesanan</a>
      </div>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead><tr><th>Kode</th><th>Vendor</th><th>Status</th><th>Dibutuhkan</th><th class="text-end">Item</th><th>Dibuat</th><th></th></tr></thead>
        <tbody>
          @forelse ($orders as $o)
            <tr>
              <td><strong>{{ $o->code }}</strong></td>
              <td>{{ $o->vendor->name }}</td>
              <td>
                <span class="badge bg-label-{{ $o->statusColor() }}">{{ $o->statusLabel() }}</span>
                @if ($o->isResponseLate())<span class="badge bg-label-warning" title="Vendor lambat merespons">lambat respons</span>@endif
                @if ($o->isShipmentLate())<span class="badge bg-label-danger">terlambat</span>@endif
              </td>
              <td>{{ $o->needed_date->format('d/m/Y') }}</td>
              <td class="text-end">{{ $o->items_count }}</td>
              <td>{{ $o->created_at->format('d/m/Y H:i') }}</td>
              <td><a href="{{ route('admin.orders.show', $o) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pesanan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
  </div>
</div>
@endsection
