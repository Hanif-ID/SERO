@extends('layouts.app')
@section('title', 'Tambah Produk')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="card"><h5 class="card-header">Tambah Produk</h5>
    <div class="card-body">
      @include('layouts.errors')
      <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        @include('admin.products._form')
        <button class="btn btn-primary me-2">Simpan</button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Batal</a>
      </form>
    </div>
  </div>
</div>
@endsection
