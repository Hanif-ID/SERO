@extends('layouts.app')
@section('title', $retur->code)
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <a href="{{ route('admin.returns.index') }}" class="text-muted small">&larr; Semua retur</a>
      <h4 class="mb-0">{{ $retur->code }} <span class="badge bg-label-{{ $retur->statusColor() }} ms-2">{{ $retur->statusLabel() }}</span></h4>
    </div>
    @if ($retur->status === 'disetujui')
      <form method="POST" action="{{ route('admin.returns.complete', $retur) }}" onsubmit="return confirm('Tandai retur selesai?')">@csrf
        <button class="btn btn-success">Tandai Selesai (barang sudah diambil/diganti)</button></form>
    @endif
  </div>
  @include('shared._return-detail')
</div>
@endsection
