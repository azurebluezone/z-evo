@extends('backEnd.layouts.app')
@section('title', 'Sales & Profit Overview')

@section('content')
{{--
    Sales & Profit Overview

    Components taken from the admin template (work/materialize-html-admin-template):
      - .card / .card-body / .card-header            (every template page)
      - .avatar + .bg-label-* + .icon-base ri-*      (KPI cards)
      - .card-datatable .table-responsive + .table   (breakdown table)
      - .row.g-3 + .form-control / .form-select      (filter card)
      - .badge.bg-label-*                            (status chips)
      - #chart div + ApexCharts from scripts.blade.php

    No inline chart script: the payload is a JSON data attribute and the
    initialiser lives in public/admin/assets/js/admin-report-overview.js.
--}}

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Sales &amp; Profit Overview</h4>
        <p class="text-muted mb-0 small">Delivered orders only. Two profit views, one authoritative revenue.</p>
    </div>
    <div class="d-flex gap-2">
        <x-admin.button variant="outline-secondary" size="sm" href="{{ route('admin.order_report') }}">Order Report</x-admin.button>
        <x-admin.button variant="outline-secondary" size="sm" href="{{ route('admin.stock_report') }}">Stock Report</x-admin.button>
    </div>
</div>

{{-- ============================ FILTER ============================ --}}
<div class="card mb-4 no-print">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Filters</h5>
    </div>
    <div class="card-body">
        <form method="get" action="{{ route('admin.reports.overview') }}">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="start_date" class="form-label">Start date</label>
                    <input type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] }}" class="form-control">
                </div>
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="end_date" class="form-label">End date</label>
                    <input type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] }}" class="form-control">
                </div>
                <div class="col-12 col-md-4 col-lg-6 d-flex gap-2">
                    <x-admin.button type="submit" size="sm">Apply</x-admin.button>
                    <x-admin.button variant="outline-secondary" size="sm" href="{{ route('admin.reports.overview') }}">Reset</x-admin.button>
                </div>
            </div>
            <p class="text-muted small mb-0 mt-3">
                Date filter applies to order creation for the ordered series and to <code>delivered_at</code> for the delivered series.
                Empty filter means the whole development baseline.
            </p>
        </form>
    </div>
</div>

@if ($metrics['delivered_count'] === 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <h5 class="mb-2">No data in this report yet</h5>
            <p class="text-muted mb-0">The selected range has no delivered orders, so profit cannot be computed. Clear the filter or deliver an order.</p>
        </div>
    </div>
@else
    {{-- ============================ KPI CARDS ============================ --}}
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Ordered revenue</p>
                            <h4 class="mb-1">BDT {{ number_format($metrics['ordered_revenue'], 0) }}</h4>
                            <span class="badge bg-label-secondary">{{ number_format($metrics['ordered_count'], 0) }} orders</span>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded">
                                <i class="icon-base ri ri-shopping-cart-2-line icon-24px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Delivered revenue</p>
                            <h4 class="mb-1">BDT {{ number_format($metrics['collected'], 0) }}</h4>
                            <span class="badge bg-label-success">{{ number_format($metrics['delivered_count'], 0) }} delivered</span>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded">
                                <i class="icon-base ri ri-truck-line icon-24px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @can('report-profit-view')
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Net profit A — delivery inclusive, after operating expenses</p>
                            <h4 class="mb-1">BDT {{ number_format($metrics['view_a'], 0) }}</h4>
                            <span class="badge bg-label-primary">margin {{ number_format($metrics['margin_a'], 1) }}%</span>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded">
                                <i class="icon-base ri ri-line-chart-line icon-24px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Net profit B — product only</p>
                            <h4 class="mb-1">BDT {{ number_format($metrics['view_b'], 0) }}</h4>
                            <span class="badge bg-label-info">margin {{ number_format($metrics['margin_b'], 1) }}%</span>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded">
                                <i class="icon-base ri ri-money-dollar-circle-line icon-24px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>

    {{-- ============================ ARITHMETIC ============================ --}}
    @can('report-profit-view')
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">How these numbers are built</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Line</th>
                            <th>Source</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Collected from customers</td>
                            <td><code>orders.amount</code> (discount already deducted)</td>
                            <td class="text-end">+ BDT {{ number_format($metrics['collected'], 0) }}</td>
                        </tr>
                        <tr>
                            <td>Courier real cost</td>
                            <td><code>courier_shipments.shipping_cost</code></td>
                            <td class="text-end text-danger">− BDT {{ number_format($metrics['shipping_cost'], 0) }}</td>
                        </tr>
                        <tr>
                            <td>COGS</td>
                            <td><code>order_details.purchase_price × qty</code></td>
                            <td class="text-end text-danger">− BDT {{ number_format($metrics['cogs'], 0) }}</td>
                        </tr>
                        <tr>
                            <td>Refunds already paid</td>
                            <td><code>order_returns.refund_amount</code> where <code>refunded = 1</code></td>
                            <td class="text-end text-danger">− BDT {{ number_format($metrics['refund_realised'], 0) }}</td>
                        </tr>
                        <tr>
                            <td>Business expenses</td>
                            <td><code>business_expenses.amount</code> by <code>spent_at</code></td>
                            <td class="text-end text-danger">− BDT {{ number_format($metrics['business_expenses'], 0) }}</td>
                        </tr>
                        <tr class="table-primary">
                            <td class="fw-bold">View A — delivery inclusive</td>
                            <td class="fw-bold">collected − courier cost − COGS − refunds − business expenses</td>
                            <td class="text-end fw-bold">BDT {{ number_format($metrics['view_a'], 0) }}</td>
                        </tr>
                        <tr>
                            <td>Product revenue net</td>
                            <td><code>orders.amount − orders.shipping_charge</code></td>
                            <td class="text-end">BDT {{ number_format($metrics['product_revenue_net'], 0) }}</td>
                        </tr>
                        <tr class="table-success">
                            <td class="fw-bold">Gross Profit</td>
                            <td class="fw-bold">Net Sales − COGS</td>
                            <td class="text-end fw-bold" data-metric="gross-profit">BDT {{ number_format($metrics['gross_profit'], 0) }}</td>
                        </tr>
                        <tr class="table-info">
                            <td class="fw-bold">View B — product only</td>
                            <td class="fw-bold">product revenue net − COGS − refunds</td>
                            <td class="text-end fw-bold">BDT {{ number_format($metrics['view_b'], 0) }}</td>
                        </tr>
                        <tr>
                            <td>Pending refunds</td>
                            <td><code>refund_required = 1</code> and <code>refunded = 0</code></td>
                            <td class="text-end text-warning">BDT {{ number_format($metrics['refund_pending'], 0) }}</td>
                        </tr>
                        <tr>
                            <td>After pending refunds</td>
                            <td>View A − pending refunds</td>
                            <td class="text-end">BDT {{ number_format($metrics['after_pending_refunds'], 0) }}</td>
                        </tr>
                        <tr>
                            <td>Shipping margin</td>
                            <td>shipping charge − courier cost</td>
                            <td class="text-end">BDT {{ number_format($metrics['shipping_margin'], 0) }}</td>
                        </tr>
                        <tr>
                            <td>Free shipping value given</td>
                            <td><code>orders.shipping_list_price</code> where free</td>
                            <td class="text-end">BDT {{ number_format($metrics['free_shipping_value'], 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endcan

    {{-- ============================ CHARTS ============================ --}}
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Ordered vs delivered revenue</h5>
                    <p class="text-muted small mb-0">Ordered uses <code>created_at</code>; delivered uses <code>delivered_at</code>.</p>
                </div>
                <div class="card-body">
                    <div id="salesTrendChart"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Paid by method</h5>
                    <p class="text-muted small mb-0">Only <code>payment_status = paid</code>.</p>
                </div>
                <div class="card-body">
                    <div id="paymentMixChart"></div>
                    @forelse ($paymentMix['labels'] as $index => $label)
                        <div class="d-flex justify-content-between border-top py-2 small">
                            <span>{{ $label }}</span>
                            <span class="fw-semibold">BDT {{ number_format($paymentMix['totals'][$index], 0) }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No paid payments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="d-none" data-report-overview="{{ json_encode(['trend' => $trend, 'payments' => $paymentMix]) }}"></div>
@endif
@endsection

@push('script')
    <script src="{{ asset('admin/assets/js/admin-report-overview.js') }}"></script>
@endpush
