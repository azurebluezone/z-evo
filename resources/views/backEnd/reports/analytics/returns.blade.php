@extends('backEnd.layouts.app')
@section('title', 'Returns & Cancellations Report')

@section('content')
{{--
    Returns and cancellations.

    Sources: order_returns + order_return_items for returns and refunds,
    orders.order_status / cancelled_at / amount for cancellations.
    Refund realised vs pending comes from refund_required and refunded; both
    are already stored, so nothing is inferred from free text.
--}}

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Returns &amp; Cancellations Report</h4>
        <p class="text-muted mb-0 small">Return rate, refund realised vs pending, restock condition and cancellations.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">Sales &amp; Profit</a>
        <a href="{{ route('admin.reports.payments') }}" class="btn btn-sm btn-outline-secondary">Payments</a>
        <a href="{{ route('admin.reports.couriers') }}" class="btn btn-sm btn-outline-secondary">Courier</a>
        <a href="{{ route('admin.reports.coupons') }}" class="btn btn-sm btn-outline-secondary">Coupons</a>
    </div>
</div>

@include('backEnd.reports.analytics._filters', ['action' => route('admin.reports.returns')])

@if ($metrics['order_count'] === 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <h5 class="mb-2">No data in this report yet</h5>
            <p class="text-muted mb-0">No order matches the selected range, so return and cancellation rates cannot be computed.</p>
        </div>
    </div>
@else
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Returned orders</p>
                    <h4 class="mb-1" data-metric="returned-orders">{{ number_format($metrics['return_count'], 0) }}</h4>
                    <span class="badge bg-label-warning" data-metric="return-rate">{{ number_format($metrics['return_rate'], 1) }}% of orders</span>
                    <span class="badge bg-label-info" data-metric="item-return-rate">{{ number_format($metrics['item_return_rate'], 1) }}% of units</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Refunded</p>
                    <h4 class="mb-1">BDT {{ number_format($metrics['refunded'], 0) }}</h4>
                    <span class="badge bg-label-success">{{ number_format($metrics['restocked'], 0) }} restocked</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Pending refund</p>
                    <h4 class="mb-1">BDT {{ number_format($metrics['refund_pending'], 0) }}</h4>
                    <span class="badge bg-label-danger">{{ number_format($metrics['damaged'], 0) }} damaged returns</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Cancelled orders</p>
                    <h4 class="mb-1">{{ number_format($metrics['cancelled_count'], 0) }}</h4>
                    <span class="badge bg-label-secondary">BDT {{ number_format($metrics['cancelled_amount'], 0) }} · {{ number_format($metrics['cancel_rate'], 1) }}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Refund state</h5>
                    <p class="text-muted small mb-0">Realised vs still pending.</p>
                </div>
                <div class="card-body">
                    <div id="refundChart"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Refund arithmetic</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr><th>Line</th><th>Source</th><th class="text-end">Value</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Refund required</td>
                                    <td><code>SUM(order_returns.refund_amount)</code> where <code>refund_required = 1</code></td>
                                    <td class="text-end">BDT {{ number_format($metrics['refund_required'], 0) }}</td>
                                </tr>
                                <tr>
                                    <td>Refunded</td>
                                    <td>same, where <code>refunded = 1</code></td>
                                    <td class="text-end text-danger">− BDT {{ number_format($metrics['refunded'], 0) }}</td>
                                </tr>
                                <tr class="table-primary">
                                    <td class="fw-bold">Still pending</td>
                                    <td class="fw-bold">required − refunded</td>
                                    <td class="text-end fw-bold">BDT {{ number_format($metrics['refund_pending'], 0) }}</td>
                                </tr>
                                <tr>
                                    <td>Return rate (order)</td>
                                    <td><code>DISTINCT returned orders / orders placed</code></td>
                                    <td class="text-end">{{ number_format($metrics['return_rate'], 1) }}%</td>
                                </tr>
                                <tr>
                                    <td>Return rate (item)</td>
                                    <td><code>returned units / units placed</code></td>
                                    <td class="text-end">{{ number_format($metrics['item_return_rate'], 1) }}%</td>
                                </tr>
                                <tr>
                                    <td>Cancellation rate</td>
                                    <td><code>cancelled orders / orders</code></td>
                                    <td class="text-end">{{ number_format($metrics['cancel_rate'], 1) }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Returns</h5>
            <p class="text-muted small mb-0">One row per <code>order_returns</code> record.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Status</th>
                            <th>Mode</th>
                            <th>Reason</th>
                            <th class="text-end">Refund</th>
                            <th>Refunded</th>
                            <th>Restocked</th>
                            <th>Condition</th>
                            <th>Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['invoice_id'] !== '' ? $row['invoice_id'] : '#'.$row['order_id'] }}</td>
                                <td><span class="badge bg-label-primary">{{ $row['status'] }}</span></td>
                                <td class="small">{{ $row['return_mode'] }}</td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($row['reason'], 70) }}</td>
                                <td class="text-end">BDT {{ number_format($row['refund_amount'], 0) }}</td>
                                <td>
                                    @if ($row['refunded'] === 1)
                                        <span class="badge bg-label-success">paid</span>
                                    @else
                                        <span class="badge bg-label-warning">pending</span>
                                    @endif
                                </td>
                                <td>{{ $row['restocked'] === 1 ? 'yes' : 'no' }}</td>
                                <td class="small">{{ $row['restock_condition'] !== '' ? $row['restock_condition'] : '—' }}</td>
                                <td class="small text-muted">{{ $row['created_at'] ? \Illuminate\Support\Carbon::parse($row['created_at'])->format('d M Y') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-muted">No returns in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Returned items</h5>
            <p class="text-muted small mb-0">Product-level rows from <code>order_return_items</code> joined to <code>order_details</code>.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Return</th>
                            <th>Product</th>
                            <th>Variant</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Refund</th>
                            <th>Restock bucket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>#{{ $item->return_id }}</td>
                                <td>{{ $item->product_name ?? '—' }}</td>
                                <td class="small text-muted">{{ trim(($item->product_size ?? '').' '.($item->product_color ?? '')) ?: '—' }}</td>
                                <td class="text-end">{{ number_format((int) $item->qty, 0) }}</td>
                                <td class="text-end">BDT {{ number_format((float) $item->refund_amount, 0) }}</td>
                                <td><span class="badge bg-label-secondary">{{ $item->restock_bucket ?? '—' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No returned items in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-none" data-report-analytics="{{ json_encode(['charts' => $charts]) }}"></div>
@endif
@endsection

@push('script')
    <script src="{{ asset('admin/assets/js/admin-report-analytics.js') }}"></script>
@endpush
