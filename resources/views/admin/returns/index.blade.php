@extends('layouts.app')
@section('title', 'Retur')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Retur ke Vendor</h5>
      <form method="GET">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua status</option>
          @foreach (\App\Models\ReturnRequest::STATUS as $k => [$label])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>@endforeach
        </select>
      </form>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead><tr><th>Kode</th><th>Pesanan</th><th>Vendor</th><th>Status</th><th class="text-end">Item</th><th>Dibuat</th><th></th></tr></thead>
        <tbody>
          @forelse ($returns as $r)
            <tr>
              <td><strong>{{ $r->code }}</strong></td>
              <td>{{ $r->order->code }}</td>
              <td>{{ $r->vendor->name }}</td>
              <td><span class="badge bg-label-{{ $r->statusColor() }}">{{ $r->statusLabel() }}</span></td>
              <td class="text-end">{{ $r->items_count }}</td>
              <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
              <td><a href="{{ route('admin.returns.show', $r) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada retur.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($returns->hasPages())<div class="card-footer">{{ $returns->links() }}</div>@endif
  </div>
</div>
@endsection
