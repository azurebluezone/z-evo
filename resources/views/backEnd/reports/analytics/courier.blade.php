@extends('backEnd.layouts.app')
@section('title', 'Courier Performance Report')

@section('content')
{{--
    Courier Performance Report

    Source: courier_shipments (provider, current_status, shipping_cost,
    last_synced_at) and courier_events (courier_status, order_status_slug).
    shipping_cost is a real column; the raw_payload JSON is never parsed here.
--}}

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Courier Performance Report</h4>
        <p class="text-muted mb-0 small">Shipments, delivery rate and real courier cost per provider.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">Sales &amp; Profit</a>
        <a href="{{ route('admin.reports.payments') }}" class="btn btn-sm btn-outline-secondary">Payments</a>
        <a href="{{ route('admin.reports.returns') }}" class="btn btn-sm btn-outline-secondary">Returns</a>
        <a href="{{ route('admin.reports.coupons') }}" class="btn btn-sm btn-outline-secondary">Coupons</a>
    </div>
</div>

@include('backEnd.reports.analytics._filters', ['action' => route('admin.reports.couriers')])

@if ($metrics['shipments'] === 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <h5 class="mb-2">No data in this report yet</h5>
            <p class="text-muted mb-0">No courier shipment matches the selected range.</p>
        </div>
    </div>
@else
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Shipments</p>
                    <h4 class="mb-1">{{ number_format($metrics['shipments'], 0) }}</h4>
                    <span class="badge bg-label-primary">all providers</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Delivered</p>
                    <h4 class="mb-1">{{ number_format($metrics['delivered'], 0) }}</h4>
                    <span class="badge bg-label-success">{{ number_format($metrics['delivery_rate'], 1) }}% delivery rate</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">In transit</p>
                    <h4 class="mb-1" data-metric="in-transit">{{ number_format($metrics['in_transit'], 0) }}</h4>
                    <span class="badge bg-label-warning" data-metric="returned">{{ number_format($metrics['returned'], 0) }} returned</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Courier cost</p>
                    <h4 class="mb-1">BDT {{ number_format($metrics['cost'], 0) }}</h4>
                    <span class="badge bg-label-secondary">avg BDT {{ number_format($metrics['avg_cost'], 0) }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">COD outstanding</p>
                    <h4 class="mb-1" data-metric="cod-outstanding">BDT {{ number_format($metrics['cod_outstanding'], 2) }}</h4>
                    @if ($metrics['settlement_unknown'] > 0)
                        <span class="badge bg-label-danger" data-metric="settlement-unknown">{{ number_format($metrics['settlement_unknown'], 0) }} unknown settlement state</span>
                    @else
                        <span class="badge bg-label-secondary" data-metric="settlement-unknown">no unknown settlement state</span>
                    @endif
                    <p class="text-muted small mb-0 mt-1">cod_amount - remitted_amount, date basis shipment created_at</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Shipments by provider</h5>
            <p class="text-muted small mb-0">Status is read from <code>status_normalized</code>, so an unknown provider word is visible instead of being counted as in transit.</p>
        </div>
        <div class="card-body">
            <div id="courierChart" class="mb-4"></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th class="text-end">Shipments</th>
                            <th class="text-end">Delivered</th>
                            <th class="text-end">In transit</th>
                            <th class="text-end">Returned</th>
                            <th class="text-end">Delivery rate</th>
                            <th class="text-end">Total cost</th>
                            <th class="text-end">Avg cost</th>
                            <th class="text-end">COD outstanding</th>
                            <th>Last sync</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['provider'] }}</td>
                                <td class="text-end">{{ number_format($row['shipments'], 0) }}</td>
                                <td class="text-end text-success">{{ number_format($row['delivered'], 0) }}</td>
                                <td class="text-end text-warning">{{ number_format($row['in_transit'], 0) }}</td>
                                <td class="text-end text-danger">{{ number_format($row['returned'], 0) }}</td>
                                <td class="text-end">{{ number_format($row['delivery_rate'], 1) }}%</td>
                                <td class="text-end fw-semibold">BDT {{ number_format($row['cost'], 0) }}</td>
                                <td class="text-end">BDT {{ number_format($row['avg_cost'], 0) }}</td>
                                <td class="text-end fw-semibold" data-cod-provider="{{ $row['provider'] }}">
                                    BDT {{ number_format($row['cod_outstanding'], 2) }}
                                    @if ($row['settlement_unknown'] > 0)
                                        <span class="d-block small text-danger">{{ number_format($row['settlement_unknown'], 0) }} unknown settlement state</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $row['last_synced_at'] ? \Illuminate\Support\Carbon::parse($row['last_synced_at'])->format('d M Y H:i') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total</th>
                            <th class="text-end">{{ number_format($metrics['shipments'], 0) }}</th>
                            <th class="text-end">{{ number_format($metrics['delivered'], 0) }}</th>
                            <th class="text-end">{{ number_format($metrics['in_transit'], 0) }}</th>
                            <th class="text-end">{{ number_format($metrics['returned'], 0) }}</th>
                            <th class="text-end">{{ number_format($metrics['delivery_rate'], 1) }}%</th>
                            <th class="text-end">BDT {{ number_format($metrics['cost'], 0) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['avg_cost'], 0) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['cod_outstanding'], 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Latest courier events</h5>
            <p class="text-muted small mb-0">Last 10 rows of <code>courier_events</code>.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Courier status</th>
                            <th>Order status</th>
                            <th>Message</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td>{{ $event->provider }}</td>
                                <td><span class="badge bg-label-secondary">{{ $event->courier_status }}</span></td>
                                <td>{{ $event->order_status_slug ?? '—' }}</td>
                                <td class="small text-muted">{{ $event->message ?? '—' }}</td>
                                <td class="small text-muted">{{ $event->created_at ? \Illuminate\Support\Carbon::parse($event->created_at)->format('d M Y H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No courier events recorded.</td></tr>
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
