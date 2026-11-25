@extends('layouts.app')
@section('title', 'Tambah Lokasi')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card"><h5 class="card-header">Tambah Lokasi</h5>
    <div class="card-body">
      @include('layouts.errors')
      <form action="{{ route('admin.locations.store') }}" method="POST">
        @csrf
        
        @include('admin.locations._form')
        <button class="btn btn-primary me-2">Simpan</button>
        <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary">Batal</a>
      </form>
    </div>
  </div>
</div>
@endsection
