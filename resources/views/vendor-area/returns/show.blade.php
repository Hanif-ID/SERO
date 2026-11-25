@extends('layouts.app')
@section('title', $retur->code)
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  @include('layouts.errors')
  <div class="mb-3">
    <a href="{{ route('vendor.returns.index') }}" class="text-muted small">&larr; Semua retur</a>
    <h4 class="mb-0">{{ $retur->code }} <span class="badge bg-label-{{ $retur->statusColor() }} ms-2">{{ $retur->statusLabel() }}</span></h4>
  </div>

  @include('shared._return-detail')

  @if ($retur->status === 'diajukan')
    <div class="card mb-4">
      <h5 class="card-header">Respons retur</h5>
      <div class="card-body">
        <form method="POST" action="{{ route('vendor.returns.respond', $retur) }}" class="row g-2 align-items-end">
          @csrf
          <div class="col-md-6"><label class="form-label" for="vendor_note">Catatan (wajib jika menolak)</label>
            <input type="text" id="vendor_note" name="vendor_note" class="form-control" value="{{ old('vendor_note') }}"></div>
          <div class="col-md-6 d-flex gap-2">
            <button name="decision" value="approve" class="btn btn-success">Setujui</button>
            <button name="decision" value="reject" class="btn btn-outline-danger">Tolak</button>
          </div>
        </form>
      </div>
    </div>
  @endif
</div>
@endsection
