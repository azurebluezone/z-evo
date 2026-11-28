@extends('backEnd.layouts.app')
@section('title', 'Report Health')

@section('content')
<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Report Health</h4>
        <p class="text-muted mb-0 small">Data-quality and access audit for the report suite. Every number and every PASS below is computed at request time from the database, the route table or the report source files.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">Sales &amp; Profit</a>
        <a href="{{ route('admin.reports.stock-alerts') }}" class="btn btn-sm btn-outline-secondary">Stock Alerts</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Integrity checks passed</p>
                <h4 class="mb-1">{{ $passed }} / {{ count($checks) }}</h4>
                <span class="badge bg-label-{{ $passed === count($checks) ? 'success' : 'warning' }}">{{ $passed === count($checks) ? 'all green' : 'needs review' }}</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Report pages</p>
                <h4 class="mb-1">{{ count($reports) }}</h4>
                <span class="badge bg-label-primary">{{ count(array_filter($reports, fn ($r) => $r['guarded'])) }} permission-guarded</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Refund still pending</p>
                <h4 class="mb-1">BDT {{ number_format($refundPending, 0) }}</h4>
                <span class="badge bg-label-warning">required minus refunded</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Test assertions</p>
                <h4 class="mb-1">{{ number_format($assertions, 0) }}</h4>
                <span class="badge bg-label-info">AdminReport* tests</span>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Report surfaces and access</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Report</th>
                        <th>Permission</th>
                        <th>Roles</th>
                        <th>Route registered</th>
                        <th>Permission middleware</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        <tr>
                            <td class="fw-semibold">{{ $report['name'] }}</td>
                            <td class="small"><code>{{ $report['permission'] }}</code></td>
                            <td class="small">{{ count($report['roles']) > 0 ? implode(', ', $report['roles']) : 'nobody' }}</td>
                            <td>
                                @if ($report['exists'])
                                    <span class="badge bg-label-success">yes</span>
                                @else
                                    <span class="badge bg-label-danger">missing</span>
                                @endif
                            </td>
                            <td>
                                @if ($report['guarded'])
                                    <span class="badge bg-label-success">yes</span>
                                @else
                                    <span class="badge bg-label-danger">missing</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Data integrity checks</h5>
        <p class="text-muted small mb-0">Computed now, not copied from a document.</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Check</th>
                        <th class="text-end">Value</th>
                        <th>Expected</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($checks as $check)
                        <tr>
                            <td>{{ $check['check'] }}</td>
                            <td class="text-end fw-semibold">{{ $check['value'] }}</td>
                            <td class="small text-muted">{{ $check['expected'] }}</td>
                            <td>
                                @if ($check['pass'])
                                    <span class="badge bg-label-success">PASS</span>
                                @else
                                    <span class="badge bg-label-danger">FAIL</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Admin productivity gained</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Before</th>
                                <th>Now</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($productivity as $item)
                                <tr>
                                    <td class="fw-semibold">{{ $item['task'] }}</td>
                                    <td class="small text-muted">{{ $item['before'] }}</td>
                                    <td class="small">{{ $item['now'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Ethics and safety checks</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Evidence</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ethics as $item)
                                <tr>
                                    <td>{{ $item['item'] }}</td>
                                    <td class="small text-muted">{{ $item['evidence'] }}</td>
                                    <td>
                                        @if ($item['pass'])
                                            <span class="badge bg-label-success">PASS</span>
                                        @else
                                            <span class="badge bg-label-danger">FAIL</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Row volume per table</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th class="text-end">Rows</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($volume as $row)
                                <tr>
                                    <td class="small"><code>{{ $row['table'] }}</code></td>
                                    <td class="text-end fw-semibold">{{ number_format($row['rows'], 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Stock posture</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Variants at or below warning threshold</span>
                    <span class="fw-semibold">{{ number_format($lowStock, 0) }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Variants showing the remaining badge</span>
                    <span class="fw-semibold">{{ number_format($remainingStock, 0) }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Variants out of stock</span>
                    <span class="fw-semibold">{{ number_format($outOfStock, 0) }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Stock source of truth</span>
                    <span class="fw-semibold"><code>variant_stocks</code> + <code>inventory_movements</code></span>
                </div>
                <a href="{{ route('admin.reports.stock-alerts') }}" class="btn btn-sm btn-outline-primary mt-3">Open Stock Alerts</a>
            </div>
        </div>
    </div>
</div>
@endsection
