@php
  $used = (int) ($loc->used ?? 0);
  $pct = $loc->capacity ? min(100, round($used / $loc->capacity * 100)) : null;
  $color = $pct === null ? 'secondary' : ($pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success'));
@endphp
@if ($pct === null)
  <small class="text-muted">{{ $used }} unit (tanpa batas)</small>
@else
  <div class="d-flex justify-content-between"><small>{{ $used }} / {{ $loc->capacity }} unit</small><small>{{ $pct }}%</small></div>
  <div class="progress" style="height:6px"><div class="progress-bar bg-{{ $color }}" style="width: {{ $pct }}%"></div></div>
@endif
