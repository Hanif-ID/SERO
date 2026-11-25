<div class="card mb-4">
  <h5 class="card-header">Rincian Barang</h5>
  <div class="table-responsive text-nowrap">
    <table class="table">
      <thead>
        <tr><th>Produk</th><th class="text-end">Dipesan</th><th class="text-end">Disiapkan (nota)</th><th class="text-end">Diterima</th><th class="text-end">Rusak/Tidak sesuai</th><th>Keterangan</th></tr>
      </thead>
      <tbody>
        @foreach ($order->items as $item)
          @php
            $ref = $item->qty_prepared ?? $item->qty_ordered;
            $short = $item->qty_received !== null && $item->qty_received < $ref;
            $over = $item->qty_received !== null && $item->qty_received > $ref;
          @endphp
          <tr>
            <td>@include('shared._product-cell', ['product' => $item->product, 'sub' => $item->product->sku])</td>
            <td class="text-end">{{ $item->qty_ordered }} {{ $item->product->unit }}</td>
            <td class="text-end">
              {{ $item->qty_prepared ?? '-' }}
              @if ($item->qty_prepared !== null && $item->qty_prepared !== $item->qty_ordered)
                <span class="badge bg-label-warning">selisih {{ $item->qty_prepared - $item->qty_ordered }}</span>
              @endif
            </td>
            <td class="text-end">
              {{ $item->qty_received ?? '-' }}
              @if ($short)<span class="badge bg-label-danger">kurang {{ $ref - $item->qty_received }}</span>@endif
              @if ($over)<span class="badge bg-label-warning">lebih {{ $item->qty_received - $ref }}</span>@endif
            </td>
            <td class="text-end">{{ $item->qty_rejected ?: '-' }}</td>
            <td>{{ $item->reject_reason }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
