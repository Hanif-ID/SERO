@php($l = $location ?? null)
<div class="mb-3">
  <label class="form-label" for="name">Nama lokasi</label>
  <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $l->name ?? '') }}" placeholder="mis. Freezer A / Etalase Es Krim" required>
  @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
  <label class="form-label" for="type">Jenis</label>
  <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
    @foreach (\App\Models\Location::TYPES as $v => $label)
      <option value="{{ $v }}" @selected(old('type', $l->type ?? 'freezer') === $v)>{{ $label }}</option>
    @endforeach
  </select>
  @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-4">
  <label class="form-label" for="capacity">Kapasitas (unit)</label>
  <input type="number" min="1" id="capacity" name="capacity" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity', $l->capacity ?? '') }}" placeholder="kosongkan jika tanpa batas">
  <div class="form-text">Sistem menolak penambahan stok yang melebihi kapasitas, supaya freezer tidak kelebihan muatan.</div>
  @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
