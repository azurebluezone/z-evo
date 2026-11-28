@extends('backEnd.layouts.app')
@section('title', 'Marketing Report')

@section('content')
{{--
    Marketing report, Phase 3 item 3 of docs/report-completion-plan.md.

    Every figure comes from MarketingMetricsService, the same service the
    dashboard blocks read. Definitions are fixed there and repeated in the
    footer: ROAS = attributed delivered revenue / spend, CPA = spend / delivered
    orders, CAC = spend / new customers (first delivered order in range).

    Two date bases meet here by nature: spend is counted on spent_at, revenue on
    orders.delivered_at. The report says so instead of hiding it.
--}}

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3" data-marketing-report>
    <div>
        <h4 class="mb-0">Marketing Report</h4>
        <p class="text-muted mb-0 small">Spend against the delivered revenue it produced, per channel.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.marketing-spends.index') }}" class="btn btn-sm btn-outline-secondary">Enter spend</a>
        <a href="{{ route('admin.reports.dashboard', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-secondary">Report Dashboard</a>
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">Sales &amp; Profit</a>
    </div>
</div>

<div class="card mb-4 no-print">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Filter</h5>
    </div>
    <div class="card-body">
        <form method="get" action="{{ route('admin.reports.marketing') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label for="from" class="form-label small mb-1">From</label>
                <input type="date" id="from" name="from" value="{{ $from }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label for="to" class="form-label small mb-1">To</label>
                <input type="date" id="to" name="to" value="{{ $to }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                <a href="{{ route('admin.reports.marketing') }}" class="btn btn-sm btn-outline-secondary">This month</a>
            </div>
            <div class="col-12">
                <span class="badge bg-label-primary" data-marketing-range="{{ $label }}">{{ $label }}</span>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Marketing spend</p>
                <h4 class="mb-1" data-marketing-report-spend="{{ number_format($totals['spend'], 2, '.', '') }}">BDT {{ number_format($totals['spend'], 0) }}</h4>
                <span class="badge bg-label-secondary"><code>spent_at</code> in range</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Attributed delivered revenue</p>
                <h4 class="mb-1" data-marketing-report-revenue="{{ number_format($totals['revenue'], 2, '.', '') }}">BDT {{ number_format($totals['revenue'], 0) }}</h4>
                <span class="badge bg-label-info">{{ number_format($totals['orders'], 0) }} orders, {{ number_format($totals['new_customers'], 0) }} new customers</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">ROAS</p>
                <h4 class="mb-1" data-marketing-report-roas="{{ $totals['roas'] === null ? '' : number_format($totals['roas'], 2, '.', '') }}">
                    {{ $totals['roas'] === null ? '-' : number_format($totals['roas'], 2).'x' }}
                </h4>
                <span class="badge bg-label-secondary">attributed revenue / spend</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">CPA / CAC</p>
                <h4 class="mb-1" data-marketing-report-cpa="{{ $totals['cpa'] === null ? '' : number_format($totals['cpa'], 2, '.', '') }}">
                    {{ $totals['cpa'] === null ? '-' : 'BDT '.number_format($totals['cpa'], 0) }}
                </h4>
                <span class="badge bg-label-secondary" data-marketing-report-cac="{{ $totals['cac'] === null ? '' : number_format($totals['cac'], 2, '.', '') }}">
                    CAC {{ $totals['cac'] === null ? '-' : 'BDT '.number_format($totals['cac'], 0) }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">By channel</h5>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Channel</th>
                    <th class="text-end">Spend</th>
                    <th class="text-end">Entries</th>
                    <th class="text-end">Delivered orders</th>
                    <th class="text-end">Attributed revenue</th>
                    <th class="text-end">ROAS</th>
                    <th class="text-end">CPA</th>
                    <th class="text-end">New customers</th>
                    <th class="text-end">CAC</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr data-channel-row="{{ $row['channel'] }}"
                        data-channel-spend="{{ number_format($row['spend'], 2, '.', '') }}"
                        data-channel-revenue="{{ number_format($row['revenue'], 2, '.', '') }}"
                        data-channel-roas="{{ $row['roas'] === null ? '' : number_format($row['roas'], 2, '.', '') }}">
                        <td>{{ ucfirst($row['channel']) }}</td>
                        <td class="text-end">BDT {{ number_format($row['spend'], 2) }}</td>
                        <td class="text-end">{{ number_format($row['entries'], 0) }}</td>
                        <td class="text-end">{{ number_format($row['orders'], 0) }}</td>
                        <td class="text-end">BDT {{ number_format($row['revenue'], 2) }}</td>
                        <td class="text-end">{{ $row['roas'] === null ? '-' : number_format($row['roas'], 2).'x' }}</td>
                        <td class="text-end">{{ $row['cpa'] === null ? '-' : 'BDT '.number_format($row['cpa'], 2) }}</td>
                        <td class="text-end">{{ number_format($row['new_customers'], 0) }}</td>
                        <td class="text-end">{{ $row['cac'] === null ? '-' : 'BDT '.number_format($row['cac'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No spend and no attributed order in this range.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th class="text-end">BDT {{ number_format($totals['spend'], 2) }}</th>
                    <th class="text-end">{{ number_format(array_sum(array_column($rows, 'entries')), 0) }}</th>
                    <th class="text-end">{{ number_format($totals['orders'], 0) }}</th>
                    <th class="text-end">BDT {{ number_format($totals['revenue'], 2) }}</th>
                    <th class="text-end">{{ $totals['roas'] === null ? '-' : number_format($totals['roas'], 2).'x' }}</th>
                    <th class="text-end">{{ $totals['cpa'] === null ? '-' : 'BDT '.number_format($totals['cpa'], 2) }}</th>
                    <th class="text-end">{{ number_format($totals['new_customers'], 0) }}</th>
                    <th class="text-end">{{ $totals['cac'] === null ? '-' : 'BDT '.number_format($totals['cac'], 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="mb-2">How these numbers are built</h6>
        <ul class="text-muted small mb-0">
            <li>ROAS = attributed delivered revenue / marketing spend. Both sides use this range; spend is counted on <code>spent_at</code>, revenue on <code>orders.delivered_at</code>.</li>
            <li>CPA = spend / delivered orders. CAC = spend / customers whose first delivered order falls in this range.</li>
            <li>An order with no attribution row is real revenue with no known source. It is reported below and deliberately kept out of ROAS.</li>
            <li>Spend on a channel with no delivered order shows ROAS and CAC as "-", not as zero or infinity.</li>
        </ul>
        <p class="small mb-0 mt-3">
            Unattributed delivered revenue in this range:
            <strong data-marketing-report-unattributed="{{ number_format($totals['unattributed_revenue'], 2, '.', '') }}">BDT {{ number_format($totals['unattributed_revenue'], 2) }}</strong>
        </p>
    </div>
</div>
@endsection
