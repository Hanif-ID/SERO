@extends('layouts.app')
@section('title', 'Lokasi Penyimpanan')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @include('layouts.flash')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">Lokasi Penyimpanan</h5>
      <a href="{{ route('admin.locations.create') }}" class="btn btn-primary btn-sm">Tambah Lokasi</a>
    </div>
    <div class="table-responsive text-nowrap">
      <table class="table">
        <thead><tr><th>Nama</th><th>Jenis</th><th style="min-width:200px">Isi / Kapasitas</th><th>Aksi</th></tr></thead>
        <tbody>
          @forelse ($locations as $loc)
            <tr>
              <td><strong>{{ $loc->name }}</strong></td>
              <td><span class="badge bg-label-{{ $loc->type === 'freezer' ? 'info' : 'primary' }}">{{ $loc->typeLabel() }}</span></td>
              <td>@include('shared._capacity', ['loc' => $loc])</td>
              <td>
                <a href="{{ route('admin.locations.edit', $loc) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                <form action="{{ route('admin.locations.destroy', $loc) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus lokasi ini?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada lokasi.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
