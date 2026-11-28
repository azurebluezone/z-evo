@extends('backEnd.layouts.app')
@section('title', 'Stock Alerts')

@section('content')
<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Stock Alerts</h4>
        <p class="text-muted mb-0 small">Variant balances from variant_stocks. Warning at {{ $warning }} pcs, remaining badge at {{ $remainingThreshold }} pcs.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">Sales &amp; Profit</a>
        <a href="{{ route('admin.reports.payments') }}" class="btn btn-sm btn-outline-secondary">Payments</a>
        <a href="{{ route('admin.reports.couriers') }}" class="btn btn-sm btn-outline-secondary">Courier</a>
        <a href="{{ route('admin.reports.returns') }}" class="btn btn-sm btn-outline-secondary">Returns</a>
        <a href="{{ route('admin.reports.coupons') }}" class="btn btn-sm btn-outline-secondary">Coupons</a>
    </div>
</div>

<div class="card mb-4 no-print">
    <div class="card-body d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.stock-alerts') }}" class="btn btn-sm {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">All variants</a>
        <a href="{{ route('admin.reports.stock-alerts', ['status' => 'low']) }}" class="btn btn-sm {{ $statusFilter === 'low' ? 'btn-primary' : 'btn-outline-secondary' }}">Low stock</a>
        <a href="{{ route('admin.reports.stock-alerts', ['status' => 'remaining']) }}" class="btn btn-sm {{ $statusFilter === 'remaining' ? 'btn-primary' : 'btn-outline-secondary' }}">Remaining</a>
        <a href="{{ route('admin.reports.stock-alerts', ['status' => 'out']) }}" class="btn btn-sm {{ $statusFilter === 'out' ? 'btn-primary' : 'btn-outline-secondary' }}">Out of stock</a>
        <a href="{{ route('admin.reports.stock-alerts', ['status' => 'orphan']) }}" class="btn btn-sm {{ $statusFilter === 'orphan' ? 'btn-primary' : 'btn-outline-secondary' }}">Orphan rows</a>
    </div>
</div>

@if ($metrics['total'] === 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <h5 class="mb-2">No data in this report yet</h5>
            <p class="text-muted mb-0">variant_stocks has no rows, so there is no balance to alert on.</p>
        </div>
    </div>
@else
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Variants tracked</p>
                    <h4 class="mb-1">{{ number_format($metrics['total'], 0) }}</h4>
                    <span class="badge bg-label-primary">{{ number_format($metrics['on_hand'], 0) }} units on hand</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Out of stock</p>
                    <h4 class="mb-1">{{ number_format($metrics['out'], 0) }}</h4>
                    <span class="badge bg-label-danger">available &lt;= 0</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Low stock</p>
                    <h4 class="mb-1">{{ number_format($metrics['low'], 0) }}</h4>
                    <span class="badge bg-label-warning">at or below {{ $warning }} pcs</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Damaged units</p>
                    <h4 class="mb-1">{{ number_format($metrics['damaged'], 0) }}</h4>
                    <span class="badge bg-label-secondary">{{ number_format($metrics['reserved'], 0) }} reserved</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Stock status</h5>
                </div>
                <div class="card-body">
                    <div id="stockStatusChart"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Balance summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">On hand</span>
                        <span class="fw-semibold">{{ number_format($metrics['on_hand'], 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Reserved</span>
                        <span class="fw-semibold">{{ number_format($metrics['reserved'], 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Available</span>
                        <span class="fw-semibold">{{ number_format($metrics['available'], 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Damaged</span>
                        <span class="fw-semibold">{{ number_format($metrics['damaged'], 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Remaining badge count</span>
                        <span class="fw-semibold">{{ number_format($metrics['remaining'], 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Orphan rows</span>
                        <span class="fw-semibold">{{ number_format($metrics['orphans'], 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Variant balances</h5>
            <p class="text-muted small mb-0">{{ number_format(count($rows), 0) }} row(s) in the current filter.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Variant</th>
                            <th class="text-end">On hand</th>
                            <th class="text-end">Reserved</th>
                            <th class="text-end">Available</th>
                            <th class="text-end">Damaged</th>
                            <th>Last sold</th>
                            <th class="text-end" title="Delivered units in the last 30 days / 30">Avg / day</th>
                            <th class="text-end" title="Available / avg daily sold, floored; dash when there were no delivered sales in 30 days">Days left</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr data-variant-id="{{ $row['variant_id'] }}"
                                data-last-sold="{{ $row['last_sold_at'] ?? '' }}"
                                data-avg-daily="{{ number_format((float) $row['avg_daily'], 2) }}"
                                data-days-left="{{ $row['days_remaining'] === null ? '' : $row['days_remaining'] }}">
                                <td>{{ $row['product_name'] !== '' ? $row['product_name'] : 'missing product' }}</td>
                                <td class="small">{{ $row['sku'] !== '' ? $row['sku'] : 'missing variant' }}</td>
                                <td class="small text-muted">{{ $row['title'] !== '' ? $row['title'] : '—' }}</td>
                                <td class="text-end">{{ number_format($row['on_hand'], 0) }}</td>
                                <td class="text-end">{{ number_format($row['reserved'], 0) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($row['available'], 0) }}</td>
                                <td class="text-end">{{ number_format($row['damaged'], 0) }}</td>
                                <td class="small text-muted">{{ $row['last_sold_at'] ? \Illuminate\Support\Carbon::parse($row['last_sold_at'])->format('d M Y') : 'never' }}</td>
                                <td class="text-end">{{ number_format((float) $row['avg_daily'], 2) }}</td>
                                <td class="text-end">{{ $row['days_remaining'] === null ? '—' : number_format((float) $row['days_remaining'], 0) }}</td>
                                <td>
                                    @if ($row['status'] === 'out')
                                        <span class="badge bg-label-danger">out</span>
                                    @elseif ($row['status'] === 'low')
                                        <span class="badge bg-label-warning">low</span>
                                    @elseif ($row['status'] === 'remaining')
                                        <span class="badge bg-label-info">only {{ number_format($row['available'], 0) }} left</span>
                                    @elseif ($row['status'] === 'orphan')
                                        <span class="badge bg-label-secondary">orphan</span>
                                    @else
                                        <span class="badge bg-label-success">healthy</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-muted">No variant matches this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Movements by type</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th class="text-end">Rows</th>
                                    <th class="text-end">Net qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($movementSummary as $summary)
                                    <tr>
                                        <td class="small">{{ $summary->type }}</td>
                                        <td class="text-end">{{ number_format((int) $summary->rows_count, 0) }}</td>
                                        <td class="text-end {{ (int) $summary->net_qty < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ (int) $summary->net_qty > 0 ? '+' : '' }}{{ number_format((int) $summary->net_qty, 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Latest movements</h5>
                    <p class="text-muted small mb-0">Last 20 rows of inventory_movements.</p>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Bucket</th>
                                    <th class="text-end">Qty</th>
                                    <th>Reference</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movements as $movement)
                                    <tr>
                                        <td class="small">{{ $movement->type }}</td>
                                        <td class="small">{{ $movement->product_name ?? '—' }}</td>
                                        <td class="small text-muted">{{ $movement->sku ?? '—' }}</td>
                                        <td class="small">{{ $movement->bucket ?? '—' }}</td>
                                        <td class="text-end {{ (int) $movement->qty_delta < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ (int) $movement->qty_delta > 0 ? '+' : '' }}{{ number_format((int) $movement->qty_delta, 0) }}
                                        </td>
                                        <td class="small text-muted">
                                            {{ $movement->ref_type ?? '—' }}{{ $movement->ref_id ? ' #'.$movement->ref_id : '' }}
                                        </td>
                                        <td class="small text-muted">{{ $movement->created_at ? \Illuminate\Support\Carbon::parse($movement->created_at)->format('d M Y H:i') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-muted">No movement recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-none" data-report-analytics="{{ json_encode(['charts' => $charts]) }}"></div>
@endif
@endsection

@push('script')
    <script src="{{ asset('admin/assets/js/admin-report-analytics.js') }}"></script>
@endpush
