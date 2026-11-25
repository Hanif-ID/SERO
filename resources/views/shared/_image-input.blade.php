{{-- Input gambar + pratinjau. Param: $name, $label, $currentUrl (opsional: URL gambar saat ini) --}}
<div class="mb-3">
  <label class="form-label" for="{{ $name }}">{{ $label }} <small class="text-muted">(JPG/PNG/WEBP, maks 2 MB)</small></label>
  <div class="d-flex align-items-start gap-3">
    <div class="d-flex flex-column gap-2">
      @if (!empty($currentUrl))
        <img src="{{ $currentUrl }}" alt="Gambar saat ini" width="72" height="72" class="rounded border" style="object-fit:cover">
      @endif
      <img id="{{ $name }}-preview" alt="Pratinjau" width="72" height="72" class="rounded border d-none" style="object-fit:cover">
    </div>
    <div class="flex-grow-1">
      <input type="file" id="{{ $name }}" name="{{ $name }}" accept="image/jpeg,image/png,image/webp"
             class="form-control @error($name) is-invalid @enderror">
      @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
      @if (!empty($currentUrl))
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" id="remove_{{ $name }}" name="remove_{{ $name }}" value="1">
          <label class="form-check-label" for="remove_{{ $name }}">Hapus gambar saat ini</label>
        </div>
      @endif
    </div>
  </div>
</div>
<script>
  (function () {
    var input = document.getElementById(@json($name)), img = document.getElementById(@json($name . '-preview'));
    if (!input || !img) return;
    input.addEventListener('change', function () {
      var f = input.files[0];
      if (f) { img.src = URL.createObjectURL(f); img.classList.remove('d-none'); } else { img.classList.add('d-none'); }
    });
  })();
</script>
