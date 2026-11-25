{{-- Peringatan otomatis: respons lambat, pengiriman telat, suhu, lama pemeriksaan --}}
@if ($order->isResponseLate())
  <div class="alert alert-warning mb-3"><i class="bx bx-time me-1"></i>
    Vendor belum merespons lebih dari {{ \App\Models\PurchaseOrder::RESPONSE_SLA_HOURS }} jam (sejak {{ $order->created_at->format('d/m/Y H:i') }}).</div>
@endif
@if ($order->isShipmentLate())
  <div class="alert alert-danger mb-3"><i class="bx bx-error me-1"></i>
    Pengiriman terlambat dari jadwal
    @if ($order->status === 'dikirim' && $order->expected_arrival_at) (perkiraan tiba {{ $order->expected_arrival_at->format('d/m/Y H:i') }})
    @else (dibutuhkan {{ $order->needed_date->format('d/m/Y') }}) @endif.</div>
@endif
@if ($order->isTempUnsafe($order->ship_temp))
  <div class="alert alert-danger mb-3"><i class="bx bx-thermometer me-1"></i>
    Suhu saat berangkat {{ $order->ship_temp }}°C, di atas batas aman {{ \App\Models\PurchaseOrder::FROZEN_MAX_TEMP }}°C.</div>
@endif
@if ($order->isTempUnsafe($order->arrival_temp))
  <div class="alert alert-danger mb-3"><i class="bx bx-thermometer me-1"></i>
    Suhu saat tiba {{ $order->arrival_temp }}°C, di atas batas aman {{ \App\Models\PurchaseOrder::FROZEN_MAX_TEMP }}°C.</div>
@endif
@if ($order->arrived_at && ($m = $order->inspectionMinutes()) > \App\Models\PurchaseOrder::INSPECTION_MAX_MINUTES)
  <div class="alert alert-warning mb-3"><i class="bx bx-timer me-1"></i>
    Pemeriksaan memakan {{ $m }} menit (batas {{ \App\Models\PurchaseOrder::INSPECTION_MAX_MINUTES }} menit). Barang berisiko mencair.</div>
@endif
