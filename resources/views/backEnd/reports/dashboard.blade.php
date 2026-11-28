@extends('backEnd.layouts.app')
@section('title', 'Report Dashboard')

@section('content')
<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <h4 class="mb-0">Report Dashboard</h4>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.cohorts') }}" class="btn btn-sm btn-outline-secondary">Cohorts</a>
        <a href="{{ route('admin.reports.abandoned-carts') }}" class="btn btn-sm btn-outline-secondary">Abandoned carts</a>
        <a href="{{ route('admin.reports.final', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-secondary">Final report</a>
        <a href="{{ route('admin.reports.settings.edit') }}" class="btn btn-sm btn-outline-secondary">Settings</a>
    </div>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="get" action="{{ route('admin.reports.dashboard') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="from" class="form-label">From</label>
                <input type="date" id="from" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div class="col-12 col-md-3">
                <label for="to" class="form-label">To</label>
                <input type="date" id="to" name="to" value="{{ $to }}" class="form-control">
            </div>
            <div class="col-12 col-md-6 d-flex flex-wrap align-items-center gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                <a href="{{ route('admin.reports.dashboard', ['from' => now()->toDateString(), 'to' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">Today</a>
                <a href="{{ route('admin.reports.dashboard', ['from' => now()->subDays(6)->toDateString(), 'to' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">Last 7 days</a>
                <a href="{{ route('admin.reports.dashboard', ['from' => now()->subDays(29)->toDateString(), 'to' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">Last 30 days</a>
                <span class="badge bg-label-primary" data-report-range="{{ $label }}">{{ $label }}</span>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4" data-placed-block>
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Orders placed</h5>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <p class="text-muted mb-1 small">Orders</p>
                <h4 class="mb-1" data-placed-count>{{ number_format($summary['placed_count'], 0) }}</h4>
                <span class="badge bg-label-primary">created in range</span>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <p class="text-muted mb-1 small">Order value</p>
                <h4 class="mb-1" data-placed-amount>BDT {{ number_format($summary['placed_amount'], 0) }}</h4>
                <span class="badge bg-label-secondary">orders.amount</span>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <p class="text-muted mb-1 small">Awaiting payment</p>
                <h4 class="mb-1" data-placed-pending>BDT {{ number_format($summary['placed_pending_payment'], 0) }}</h4>
                <span class="badge bg-label-warning">pending payments</span>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <p class="text-muted mb-1 small">By status</p>
                @forelse ($summary['placed_by_status'] as $status)
                    <div class="d-flex justify-content-between small py-1 border-bottom">
                        <span>{{ $status['name'] }}</span>
                        <span>{{ number_format($status['orders'], 0) }} x BDT {{ number_format($status['total'], 0) }}</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No order placed in this range.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Delivered revenue</p>
                <h4 class="mb-1" data-kpi="revenue">BDT {{ number_format($summary['revenue'], 0) }}</h4>
                <span class="badge bg-label-success">{{ number_format($summary['delivered_count'], 0) }} delivered orders</span>
                @include('backEnd.reports.partials.kpi-delta', ['key' => 'revenue'])
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Expense</p>
                <h4 class="mb-1" data-kpi="expense">BDT {{ number_format($summary['expense'], 0) }}</h4>
                <span class="badge bg-label-warning">COGS + courier + refunds + operating expenses</span>
                @include('backEnd.reports.partials.kpi-delta', ['key' => 'expense'])
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Estimated Net Profit</p>
                <h4 class="mb-1 {{ $summary['profit'] < 0 ? 'text-danger' : '' }}" data-kpi="profit">BDT {{ number_format($summary['profit'], 0) }}</h4>
                <span class="badge bg-label-{{ $summary['profit'] < 0 ? 'danger' : 'primary' }}">
                    margin {{ number_format($summary['revenue'] > 0 ? $summary['profit'] / $summary['revenue'] * 100 : 0, 1) }}%
                </span>
                @include('backEnd.reports.partials.kpi-delta', ['key' => 'profit'])
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Payments collected</p>
                <h4 class="mb-1" data-kpi="paid">BDT {{ number_format($summary['paid'], 0) }}</h4>
                <span class="badge bg-label-secondary" data-kpi="pending">BDT {{ number_format($summary['pending'], 0) }} pending</span>
                @include('backEnd.reports.partials.kpi-delta', ['key' => 'paid'])
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Gross Product Sales</p>
                <h4 class="mb-1" data-kpi="product_sales">BDT {{ number_format($summary['product_sales'], 0) }}</h4>
                <span class="badge bg-label-secondary">order lines, parent <code>created_at</code></span>
                @include('backEnd.reports.partials.kpi-delta', ['key' => 'product_sales'])
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Average order value</p>
                <h4 class="mb-1" data-kpi="aov">BDT {{ number_format($summary['aov'], 0) }}</h4>
                <span class="badge bg-label-info">net sales / {{ number_format($summary['delivered_count'], 0) }} delivered</span>
                @include('backEnd.reports.partials.kpi-delta', ['key' => 'aov'])
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Net sales</p>
                <h4 class="mb-1" data-kpi="net_sales">BDT {{ number_format($summary['net_sales'], 0) }}</h4>
                <span class="badge bg-label-secondary">delivered revenue less shipping</span>
                @include('backEnd.reports.partials.kpi-delta', ['key' => 'net_sales'])
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Gross Profit</p>
                <h4 class="mb-1" data-kpi="gross_profit">BDT {{ number_format($summary['gross_profit'], 0) }}</h4>
                <span class="badge bg-label-secondary">Net Sales − COGS</span>
                @include('backEnd.reports.partials.kpi-delta', ['key' => 'gross_profit'])
            </div>
        </div>
    </div>
</div>

{{-- ==================== MARKETING EFFICIENCY ==================== --}}
{{-- Same MarketingMetricsService the Marketing report uses, so the two
     screens cannot disagree about ROAS for this range. --}}
<div class="row g-4 mb-4" data-marketing-block>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Marketing spend</p>
                <h4 class="mb-1" data-marketing-spend-total="{{ number_format($marketing['spend'], 2, '.', '') }}">BDT {{ number_format($marketing['spend'], 0) }}</h4>
                <span class="badge bg-label-secondary"><code>spent_at</code> in range</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Attributed delivered revenue</p>
                <h4 class="mb-1" data-marketing-revenue="{{ number_format($marketing['revenue'], 2, '.', '') }}">BDT {{ number_format($marketing['revenue'], 0) }}</h4>
                <span class="badge bg-label-info">{{ number_format($marketing['orders'], 0) }} attributed delivered orders</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">ROAS</p>
                <h4 class="mb-1" data-roas="{{ $marketing['roas'] === null ? '' : number_format($marketing['roas'], 2, '.', '') }}">
                    {{ $marketing['roas'] === null ? '-' : number_format($marketing['roas'], 2).'x' }}
                </h4>
                <span class="badge bg-label-secondary">revenue / spend</span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">CPA</p>
                <h4 class="mb-1" data-cpa="{{ $marketing['cpa'] === null ? '' : number_format($marketing['cpa'], 2, '.', '') }}">
                    {{ $marketing['cpa'] === null ? '-' : 'BDT '.number_format($marketing['cpa'], 0) }}
                </h4>
                <span class="badge bg-label-secondary" data-cac="{{ $marketing['cac'] === null ? '' : number_format($marketing['cac'], 2, '.', '') }}">
                    CAC {{ $marketing['cac'] === null ? '-' : 'BDT '.number_format($marketing['cac'], 0) }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- DASHBOARD_CHARTS_V1 --}}
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Top products</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" data-table="top-products">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Product</th>
                                <th scope="col" class="text-end">Units sold</th>
                                <th scope="col" class="text-end">Product sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summary['top_products'] as $index => $product)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td data-top-product="{{ $product['product_id'] }}">{{ $product['name'] }}</td>
                                    <td class="text-end" data-top-units="{{ $product['product_id'] }}">{{ number_format($product['units'], 0) }}</td>
                                    <td class="text-end" data-top-sales="{{ $product['product_id'] }}">BDT {{ number_format($product['sales'], 0) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">No order line in this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="text-muted small mt-2">Order lines by parent <code>created_at</code>, all statuses; top 5 by <code>sale_price * qty</code>, same basis as Gross Product Sales.</div>
            </div>
        </div>
    </div>
</div>
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Daily sales trend</h5>
            </div>
            <div class="card-body">
                <div id="salesTrendChart" data-chart="sales-trend"></div>
                <div class="text-muted small mt-2">Orders placed by <code>created_at</code>; amount is <code>orders.amount</code>.</div>
                @if (count($summary['sales_trend']) === 0)
                    <div class="text-muted small mt-2">No order placed in this range.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Order status</h5>
            </div>
            <div class="card-body">
                <div id="orderStatusChart" data-chart="status-donut"></div>
                <div class="text-muted small mt-2">Orders placed in the range, by status.</div>
                @if (count($summary['status_mix']) === 0)
                    <div class="text-muted small mt-2">No order placed in this range.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Payment distribution</h5>
            </div>
            <div class="card-body">
                <div id="paymentMixChart" data-chart="payment-mix"></div>
                <div class="text-muted small mt-2">Payment rows by <code>payment_status</code>.</div>
                @if (count($summary['payment_mix']) === 0)
                    <div class="text-muted small mt-2">No payment in this range.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="d-none" id="dashboard-chart-data"
     data-sales-trend="{{ json_encode($summary['sales_trend']) }}"
     data-status-mix="{{ json_encode($summary['status_mix']) }}"
     data-payment-mix="{{ json_encode($summary['payment_mix']) }}"></div>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Expense arithmetic</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Revenue (orders.amount)</span>
                    <span class="fw-semibold" data-metric="revenue-line">BDT {{ number_format($summary['revenue'], 0) }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">COGS (order_details.purchase_price x qty)</span>
                    <span class="text-danger" data-metric="cogs-line">- BDT {{ number_format($summary['cogs'], 0) }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Courier cost (shipping_cost)</span>
                    <span class="text-danger" data-metric="courier-cost-line">- BDT {{ number_format($summary['courier_cost'], 0) }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Refunds paid (return date)</span>
                    <span class="text-danger" data-metric="refund-paid-line">- BDT {{ number_format($summary['refund_paid'], 0) }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="fw-bold">Net profit</span>
                    <span class="fw-bold" data-metric="profit-line">BDT {{ number_format($summary['profit'], 0) }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Payment methods</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Collected</span>
                    <span class="fw-semibold">BDT {{ number_format($summary['paid'], 0) }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Pending</span>
                    <span class="fw-semibold">BDT {{ number_format($summary['pending'], 0) }}</span>
                </div>
                @forelse ($summary['payment_methods'] as $method)
                    <div class="d-flex justify-content-between border-bottom py-2 small">
                        <span>{{ $method->method }}</span>
                        <span>{{ number_format((int) $method->rows_count, 0) }} x BDT {{ number_format((float) $method->total, 0) }}</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0 pt-2">No paid payment in this range.</p>
                @endforelse
                <a href="{{ route('admin.reports.payments') }}" class="btn btn-sm btn-outline-primary mt-3">Open payment report</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Courier</p>
                <h4 class="mb-1" data-metric="courier-count">{{ number_format($summary['courier_count'], 0) }} shipments</h4>
                <span class="badge bg-label-success">{{ number_format($summary['courier_rate'], 1) }}% delivered</span>
                <div class="small text-muted mt-2" data-metric="courier-cost">Cost BDT {{ number_format($summary['courier_cost'], 0) }}</div>
                <a href="{{ route('admin.reports.couriers') }}" class="btn btn-sm btn-outline-primary mt-3">Open courier report</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Returns</p>
                <h4 class="mb-1" data-metric="return-count">{{ number_format($summary['return_count'], 0) }} returns</h4>
                <span class="badge bg-label-warning" data-metric="refund-pending">BDT {{ number_format($summary['refund_pending'], 0) }} pending refund</span>
                <div class="small text-muted mt-2" data-metric="refunded">Refunded BDT {{ number_format($summary['refunded'], 0) }}</div>
                <a href="{{ route('admin.reports.returns') }}" class="btn btn-sm btn-outline-primary mt-3">Open returns report</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Coupons</p>
                <h4 class="mb-1" data-metric="coupon-usages">{{ number_format($summary['coupon_usages'], 0) }} usages</h4>
                <span class="badge bg-label-info" data-metric="coupon-discount">BDT {{ number_format($summary['coupon_discount'], 0) }} discount</span>
                <div class="small text-muted mt-2" data-metric="coupon-revenue">Revenue BDT {{ number_format($summary['coupon_revenue'], 0) }}</div>
                <a href="{{ route('admin.reports.coupons') }}" class="btn btn-sm btn-outline-primary mt-3">Open coupon report</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Stock</p>
                <h4 class="mb-1" data-metric="stock-count">{{ number_format($summary['stock']['count'], 0) }} at warning</h4>
                <span class="badge bg-label-danger" data-metric="stock-out">{{ number_format($summary['stock']['out'], 0) }} out</span>
                <div class="small text-muted mt-2">
                    Warning at {{ $summary['stock']['warning'] }} pcs, remaining badge at {{ $summary['stock']['remaining'] }} pcs
                </div>
                <a href="{{ route('admin.reports.stock-alerts') }}" class="btn btn-sm btn-outline-primary mt-3">Open stock alerts</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Recent orders</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($summary['recent_orders'] as $order)
                        <tr data-recent-order="{{ $order->id }}">
                            <td class="fw-semibold">#{{ $order->id }}</td>
                            <td class="text-end">BDT {{ number_format((float) $order->amount, 0) }}</td>
                            <td><span class="badge bg-label-secondary">{{ $order->status_name }}</span></td>
                            <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($order->created_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No order recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    var payload = document.getElementById('dashboard-chart-data');
    if (!payload || typeof ApexCharts === 'undefined') {
        return;
    }

    function read(name) {
        try {
            return JSON.parse(payload.getAttribute(name) || '[]');
        } catch (error) {
            return [];
        }
    }

    var trend = read('data-sales-trend');
    var statusMix = read('data-status-mix');
    var paymentMix = read('data-payment-mix');
    var palette = ['#696cff', '#71dd37', '#ffab00', '#ff3e1d', '#03c3ec', '#8592a3', '#a5a3ae'];

    var trendNode = document.getElementById('salesTrendChart');
    if (trendNode && trend.length) {
        new ApexCharts(trendNode, {
            chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
            series: [{ name: 'Order amount', data: trend.map(function (row) { return Number(row.amount); }) }],
            xaxis: { categories: trend.map(function (row) { return row.label; }) },
            yaxis: { labels: { formatter: function (value) { return 'BDT ' + Number(value).toLocaleString(); } } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            colors: ['#696cff'],
            tooltip: {
                custom: function (opts) {
                    var row = trend[opts.dataPointIndex] || { label: '', orders: 0, amount: 0 };
                    return '<div class="px-2 py-1">'
                        + '<div class="fw-semibold">' + row.label + '</div>'
                        + '<div>BDT ' + Number(row.amount).toLocaleString() + '</div>'
                        + '<div>' + Number(row.orders).toLocaleString() + ' orders</div>'
                        + '</div>';
                }
            }
        }).render();
    }

    var statusNode = document.getElementById('orderStatusChart');
    if (statusNode && statusMix.length) {
        new ApexCharts(statusNode, {
            chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
            series: statusMix.map(function (row) { return Number(row.orders); }),
            labels: statusMix.map(function (row) { return row.label; }),
            colors: palette,
            legend: { position: 'bottom' },
            dataLabels: { enabled: true },
            tooltip: {
                y: {
                    formatter: function (value, opts) {
                        var row = statusMix[opts.seriesIndex] || { amount: 0 };
                        return Number(value).toLocaleString() + ' orders (BDT ' + Number(row.amount).toLocaleString() + ')';
                    }
                }
            }
        }).render();
    }

    var paymentNode = document.getElementById('paymentMixChart');
    if (paymentNode && paymentMix.length) {
        new ApexCharts(paymentNode, {
            chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
            series: paymentMix.map(function (row) { return Number(row.count); }),
            labels: paymentMix.map(function (row) { return row.label; }),
            colors: palette,
            legend: { position: 'bottom' },
            dataLabels: { enabled: true },
            tooltip: {
                y: {
                    formatter: function (value, opts) {
                        var row = paymentMix[opts.seriesIndex] || { amount: 0 };
                        return Number(value).toLocaleString() + ' payments (BDT ' + Number(row.amount).toLocaleString() + ')';
                    }
                }
            }
        }).render();
    }
})();
</script>
@endsection
