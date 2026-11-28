{{--
    Percentage change of one KPI against the previous period of the same length.

    $key must be a key of $summary['previous']['delta']. A KPI whose previous
    value was zero has no percentage, so the badge says "no previous period"
    instead of printing infinity or a made-up number.
--}}
@php($delta = $summary['previous']['delta'][$key] ?? null)
@if ($delta === null)
    <span class="badge bg-label-secondary" data-kpi-delta="{{ $key }}">no previous period</span>
@else
    <span class="badge bg-label-{{ $delta > 0 ? 'success' : ($delta < 0 ? 'danger' : 'secondary') }}"
          data-kpi-delta="{{ $key }}">{{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 1) }}% vs previous period</span>
@endif
