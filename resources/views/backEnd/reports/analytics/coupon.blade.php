@extends('backEnd.layouts.app')
@section('title', 'Coupon Impact Report')

@section('content')
{{--
    Coupon impact.

    Sources: coupon_usages (coupon_code, discount_amount, source, order_id) and
    coupons (type, value, usage_limit, used_count, status). Revenue is the sum
    of the linked orders.amount, which is already net of the discount, so the
    discount is shown as a cost and never subtracted a second time.
--}}

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Coupon Impact Report</h4>
        <p class="text-muted mb-0 small">Usage, discount given, revenue from coupon orders and limit health.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">Sales &amp; Profit</a>
        <a href="{{ route('admin.reports.payments') }}" class="btn btn-sm btn-outline-secondary">Payments</a>
        <a href="{{ route('admin.reports.couriers') }}" class="btn btn-sm btn-outline-secondary">Courier</a>
        <a href="{{ route('admin.reports.returns') }}" class="btn btn-sm btn-outline-secondary">Returns</a>
    </div>
</div>

@include('backEnd.reports.analytics._filters', ['action' => route('admin.reports.coupons'), 'viewKey' => 'coupons'])

@if ($metrics['usage_count'] === 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <h5 class="mb-2">No data in this report yet</h5>
            <p class="text-muted mb-0">No coupon usage matches the selected range.</p>
        </div>
    </div>
@else
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Coupon usages</p>
                    <h4 class="mb-1">{{ number_format($metrics['usage_count'], 0) }}</h4>
                    <span class="badge bg-label-primary">{{ number_format($metrics['coupon_count'], 0) }} coupons</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Discount given</p>
                    <h4 class="mb-1">BDT {{ number_format($metrics['total_discount'], 0) }}</h4>
                    <span class="badge bg-label-warning">avg BDT {{ number_format($metrics['avg_discount'], 0) }} per use</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Revenue on coupon orders</p>
                    <h4 class="mb-1">BDT {{ number_format($metrics['revenue'], 0) }}</h4>
                    <span class="badge bg-label-success">{{ number_format($metrics['linked_orders'], 0) }} orders</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Average order value</p>
                    <h4 class="mb-1">BDT {{ number_format($metrics['avg_order_value'], 0) }}</h4>
                    <span class="badge bg-label-info">{{ number_format($metrics['checkout_usages'], 0) }} checkout · {{ number_format($metrics['pos_usages'], 0) }} POS</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Discount by coupon</h5>
            <p class="text-muted small mb-0">Sum of <code>coupon_usages.discount_amount</code> per code.</p>
        </div>
        <div class="card-body">
            <div id="couponChart" class="mb-4"></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Rule</th>
                            <th class="text-end">Usages</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Orders</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">AOV</th>
                            <th class="text-end">Limit / used</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['code'] }}</td>
                                <td class="small text-muted">
                                    @if ($row['type'] === 'percent')
                                        {{ rtrim(rtrim(number_format((float) $row['value'], 2, '.', ''), '0'), '.') }}%
                                    @elseif ($row['type'] === 'fixed')
                                        BDT {{ number_format((float) $row['value'], 0) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($row['usages'], 0) }}</td>
                                <td class="text-end fw-semibold">BDT {{ number_format($row['discount'], 0) }}</td>
                                <td class="text-end">{{ number_format($row['orders'], 0) }}</td>
                                <td class="text-end">BDT {{ number_format($row['revenue'], 0) }}</td>
                                <td class="text-end">BDT {{ number_format($row['orders'] > 0 ? $row['revenue'] / $row['orders'] : 0, 0) }}</td>
                                <td class="text-end small">
                                    {{ $row['usage_limit'] !== null ? number_format($row['usage_limit'], 0) : 'unlimited' }}
                                    /
                                    {{ $row['used_count'] !== null ? number_format($row['used_count'], 0) : '—' }}
                                </td>
                                <td>
                                    @if ($row['status'] === 1)
                                        <span class="badge bg-label-success">active</span>
                                    @elseif ($row['status'] === 0)
                                        <span class="badge bg-label-secondary">inactive</span>
                                    @else
                                        <span class="badge bg-label-secondary">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total</th>
                            <th></th>
                            <th class="text-end">{{ number_format($metrics['usage_count'], 0) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['total_discount'], 0) }}</th>
                            <th class="text-end">{{ number_format($metrics['linked_orders'], 0) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['revenue'], 0) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['avg_order_value'], 0) }}</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
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
