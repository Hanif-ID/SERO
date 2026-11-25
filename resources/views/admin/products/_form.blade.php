@php($p = $product ?? null)
<div class="row">
  <div class="col-md-6 mb-3">
    <label class="form-label" for="vendor_id">Vendor / Pemasok</label>
    <select id="vendor_id" name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required>
      <option value="">— pilih vendor —</option>
      @foreach ($vendors as $v)
        <option value="{{ $v->id }}" @selected(old('vendor_id', $p->vendor_id ?? '') == $v->id)>{{ $v->name }}</option>
      @endforeach
    </select>
    @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6 mb-3">
    <label class="form-label" for="sku">SKU</label>
    <input type="text" id="sku" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $p->sku ?? '') }}" required>
    @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-8 mb-3">
    <label class="form-label" for="name">Nama Produk</label>
    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $p->name ?? '') }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4 mb-3">
    <label class="form-label" for="unit">Satuan</label>
    <input type="text" id="unit" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $p->unit ?? 'pcs') }}" required>
    @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6 mb-3">
    <label class="form-label" for="min_stock">Stok minimum</label>
    <input type="number" min="0" id="min_stock" name="min_stock" class="form-control @error('min_stock') is-invalid @enderror" value="{{ old('min_stock', $p->min_stock ?? 0) }}" required>
    <div class="form-text">Saat stok sama dengan atau di bawah angka ini, produk muncul di “Kebutuhan Stok”.</div>
    @error('min_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6 mb-4">
    <label class="form-label" for="target_stock">Stok target</label>
    <input type="number" min="0" id="target_stock" name="target_stock" class="form-control @error('target_stock') is-invalid @enderror" value="{{ old('target_stock', $p->target_stock ?? 0) }}" required>
    <div class="form-text">Dipakai untuk menghitung saran jumlah pesan.</div>
    @error('target_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-12">
    @include('shared._image-input', ['name' => 'image', 'label' => 'Gambar produk', 'currentUrl' => ($p && $p->image) ? $p->imageUrl() : null])
  </div>
</div>
