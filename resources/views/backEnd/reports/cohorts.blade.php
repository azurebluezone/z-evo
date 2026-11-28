@extends('backEnd.layouts.app')
@section('title', 'Cohort Retention')

@section('content')
{{--
    Cohort retention by the month of the first delivered order, Phase 3 item 4.

    A cohort is fixed by history: the month the customer's first delivered
    order became real. M0 is that acquisition month; Mk is the k-th month after
    it. Every cell is a distinct-customer count, so an order with two lines or
    two orders in one month still counts once.

    Source: CohortRetentionService. Nothing here is computed in the view.
--}}

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3" data-cohort-retention>
    <div>
        <h4 class="mb-0">Cohort Retention</h4>
        <p class="text-muted mb-0 small">How many customers from each first-delivery month came back, month by month.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.dashboard') }}" class="btn btn-sm btn-outline-secondary">Report Dashboard</a>
        <a href="{{ route('admin.reports.marketing') }}" class="btn btn-sm btn-outline-secondary">Marketing</a>
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">Sales &amp; Profit</a>
    </div>
</div>

<div class="card mb-4 no-print">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Cohort months</h5>
    </div>
    <div class="card-body">
        <form method="get" action="{{ route('admin.reports.cohorts') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label for="from" class="form-label small mb-1">First delivery from</label>
                <input type="date" id="from" name="from" value="{{ $from }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label for="to" class="form-label small mb-1">First delivery to</label>
                <input type="date" id="to" name="to" value="{{ $to }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                <a href="{{ route('admin.reports.cohorts') }}" class="btn btn-sm btn-outline-secondary">Last 6 months</a>
            </div>
            <div class="col-12">
                <span class="badge bg-label-primary" data-cohort-range="{{ $label }}">{{ $label }}</span>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-3">
            The filter selects which cohort months are listed. A listed cohort still shows its later activity,
            even when that activity falls outside the filter.
        </p>
    </div>
</div>

@php
    $rowAttributes = function (array $row) use ($maxOffset): string {
        $out = 'data-cohort-row="'.$row['cohort'].'" data-cohort-size="'.$row['customers'].'"';

        for ($k = 0; $k <= $maxOffset; $k++) {
            $count = (int) ($row['cells'][$k] ?? 0);
            $rate = $row['customers'] > 0 ? $count / $row['customers'] * 100 : 0.0;
            $out .= ' data-cohort-cell-'.$k.'="'.$count.'"';
            $out .= ' data-cohort-rate-'.$k.'="'.number_format($rate, 2, '.', '').'"';
        }

        return $out;
    };
@endphp

<div class="card">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Retention by cohort</h5>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Cohort</th>
                    <th class="text-end">Customers</th>
                    @for ($k = 0; $k <= $maxOffset; $k++)
                        <th class="text-end">M{{ $k }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr {!! $rowAttributes($row) !!}>
                        <td>{{ \Illuminate\Support\Carbon::parse($row['cohort'])->format('M Y') }}</td>
                        <td class="text-end">{{ number_format($row['customers'], 0) }}</td>
                        @for ($k = 0; $k <= $maxOffset; $k++)
                            @php
                                $count = (int) ($row['cells'][$k] ?? 0);
                                $rate = $row['customers'] > 0 ? $count / $row['customers'] * 100 : 0.0;
                            @endphp
                            <td class="text-end">
                                {{ number_format($count, 0) }}
                                <span class="text-muted small">({{ number_format($rate, 1) }}%)</span>
                            </td>
                        @endfor
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $maxOffset + 3 }}" class="text-center text-muted py-4">
                            No customer had a first delivered order in this window.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($totals['customers'] > 0)
                <tfoot>
                    <tr>
                        <th>All cohorts</th>
                        <th class="text-end">{{ number_format($totals['customers'], 0) }}</th>
                        @for ($k = 0; $k <= $maxOffset; $k++)
                            @php
                                $count = (int) ($totals['cells'][$k] ?? 0);
                                $rate = $totals['customers'] > 0 ? $count / $totals['customers'] * 100 : 0.0;
                            @endphp
                            <th class="text-end">
                                {{ number_format($count, 0) }}
                                <span class="text-muted small">({{ number_format($rate, 1) }}%)</span>
                            </th>
                        @endfor
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    <div class="card-body border-top">
        <h6 class="mb-2">How this table is built</h6>
        <ul class="text-muted small mb-0">
            <li>A customer belongs to the month of their first delivered order, forever. It never moves.</li>
            <li>M0 is that first month. Mk is k whole months later.</li>
            <li>Each cell counts distinct customers with a delivered order in that month, so two orders in one month count once.</li>
            <li>Only delivered orders count, on <code>orders.delivered_at</code>. A placed order that never shipped is not retention.</li>
        </ul>
    </div>
</div>
@endsection
