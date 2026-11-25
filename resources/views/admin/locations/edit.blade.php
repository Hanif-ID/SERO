@extends('layouts.app')
@section('title', 'Edit Lokasi')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card"><h5 class="card-header">Edit Lokasi</h5>
    <div class="card-body">
      @include('layouts.errors')
      <form action="{{ route('admin.locations.update', $location) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.locations._form')
        <button class="btn btn-primary me-2">Perbarui</button>
        <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary">Batal</a>
      </form>
    </div>
  </div>
</div>
@endsection
