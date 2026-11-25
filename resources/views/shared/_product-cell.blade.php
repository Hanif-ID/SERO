{{-- Sel produk: thumbnail + nama (+ keterangan kecil). Param: $product, $sub (opsional), $size (opsional, default 36) --}}
<div class="d-flex align-items-center gap-2">
  <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="{{ $size ?? 36 }}" height="{{ $size ?? 36 }}"
       class="rounded border flex-shrink-0" style="object-fit:cover" loading="lazy">
  <div><strong>{{ $product->name }}</strong>@if (!empty($sub))<br><small class="text-muted">{{ $sub }}</small>@endif</div>
</div>
