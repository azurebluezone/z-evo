@extends('backEnd.layouts.app')
@section('title', 'Payment Method Report')

@section('content')
{{--
    Payment Method Report

    Source: payments (payment_method, payment_status, amount, order_id).
    Every number is an aggregate over those columns; the page adds nothing.

    Template components (work/materialize-html-admin-template):
      .card / .card-header / .card-body / .card-title, .avatar + .bg-label-*,
      .table-responsive + .table, .badge.bg-label-*, .row.g-3 form grid.
--}}

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Payment Method Report</h4>
        <p class="text-muted mb-0 small">Method-wise collected, pending, refunded and cancelled amounts.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">Sales &amp; Profit</a>
        <a href="{{ route('admin.reports.couriers') }}" class="btn btn-sm btn-outline-secondary">Courier</a>
        <a href="{{ route('admin.reports.returns') }}" class="btn btn-sm btn-outline-secondary">Returns</a>
        <a href="{{ route('admin.reports.coupons') }}" class="btn btn-sm btn-outline-secondary">Coupons</a>
    </div>
</div>

@include('backEnd.reports.analytics._filters', ['action' => route('admin.reports.payments')])

@if ($metrics['total_count'] === 0)
    <div class="card">
        <div class="card-body text-center py-5">
            <h5 class="mb-2">No data in this report yet</h5>
            <p class="text-muted mb-0">No payment rows match the selected range, so there is nothing to aggregate.</p>
        </div>
    </div>
@else
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Collected (paid)</p>
                    <h4 class="mb-1">BDT {{ number_format($metrics['paid_amount'], 0) }}</h4>
                    <span class="badge bg-label-success">{{ number_format($metrics['paid_count'], 0) }} payments</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Pending</p>
                    <h4 class="mb-1">BDT {{ number_format($metrics['pending_amount'], 0) }}</h4>
                    <span class="badge bg-label-warning">{{ number_format($metrics['pending_count'], 0) }} payments</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Refunded</p>
                    <h4 class="mb-1">BDT {{ number_format($metrics['refunded_amount'], 0) }}</h4>
                    <span class="badge bg-label-info">{{ number_format($metrics['refunded_count'], 0) }} payments</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Cancelled</p>
                    <h4 class="mb-1">BDT {{ number_format($metrics['cancelled_amount'], 0) }}</h4>
                    <span class="badge bg-label-danger">{{ number_format($metrics['cancelled_count'], 0) }} payments</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Settlement gap</p>
                    <h4 class="mb-1" data-metric="settlement-gap">BDT {{ number_format($metrics['settlement_gap'], 2) }}</h4>
                    <span class="badge bg-label-secondary">fee BDT {{ number_format($metrics['fee'], 2) }}, settled BDT {{ number_format($metrics['settled'], 2) }}</span>
                    <p class="text-muted small mb-0 mt-1">collected - gateway fee - settled, paid payments in range</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Paid amount by method</h5>
                    <p class="text-muted small mb-0">Only <code>payment_status = paid</code>.</p>
                </div>
                <div class="card-body">
                    <div id="paymentMethodChart"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="card h-100">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">Collection health</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Payments in range</span>
                        <span class="fw-semibold">{{ number_format($metrics['total_count'], 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Value in range</span>
                        <span class="fw-semibold">BDT {{ number_format($metrics['total_amount'], 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Success rate (paid / all)</span>
                        <span class="fw-semibold">{{ number_format($metrics['success_rate'], 1) }}%</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Average paid ticket</span>
                        <span class="fw-semibold">BDT {{ number_format($metrics['avg_paid'], 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Method breakdown</h5>
            <p class="text-muted small mb-0">One row per <code>payments.payment_method</code>.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th class="text-end">Payments</th>
                            <th class="text-end">Paid count</th>
                            <th class="text-end">Paid amount</th>
                            <th class="text-end">Fee</th>
                            <th class="text-end">Settled</th>
                            <th class="text-end">Gap</th>
                            <th class="text-end">Pending</th>
                            <th class="text-end">Refunded</th>
                            <th class="text-end">Cancelled</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row['method'] }}</td>
                                <td class="text-end">{{ number_format($row['total_count'], 0) }}</td>
                                <td class="text-end">{{ number_format($row['paid_count'], 0) }}</td>
                                <td class="text-end fw-semibold">BDT {{ number_format($row['paid_amount'], 0) }}</td>
                                <td class="text-end">BDT {{ number_format($row['fee'], 2) }}</td>
                                <td class="text-end">BDT {{ number_format($row['settled'], 2) }}</td>
                                <td class="text-end" data-method-gap="{{ $row['method'] }}">BDT {{ number_format($row['gap'], 2) }}</td>
                                <td class="text-end text-warning">BDT {{ number_format($row['pending_amount'], 0) }}</td>
                                <td class="text-end text-info">BDT {{ number_format($row['refunded_amount'], 0) }}</td>
                                <td class="text-end text-danger">BDT {{ number_format($row['cancelled_amount'], 0) }}</td>
                                <td class="text-end">BDT {{ number_format($row['total_amount'], 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th>Total</th>
                            <th class="text-end">{{ number_format($metrics['total_count'], 0) }}</th>
                            <th class="text-end">{{ number_format($metrics['paid_count'], 0) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['paid_amount'], 0) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['fee'], 2) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['settled'], 2) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['settlement_gap'], 2) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['pending_amount'], 0) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['refunded_amount'], 0) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['cancelled_amount'], 0) }}</th>
                            <th class="text-end">BDT {{ number_format($metrics['total_amount'], 0) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Record a payment settlement</h5>
            <p class="text-muted small mb-0">Writes one <code>payment_settlements</code> row, then recomputes the payment's settled amount and gateway fee from all of its settlement rows.</p>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('admin.reports.payments.settlement') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label" for="settlementPayment">Payment</label>
                    <select class="form-select" id="settlementPayment" name="payment_id" required>
                        <option value="">Choose a paid payment</option>
                        @foreach ($settlementTargets as $target)
                            <option value="{{ $target->id }}">#{{ $target->id }} order {{ $target->order_id }} {{ $target->payment_method }} BDT {{ number_format((float) $target->amount, 0) }} ({{ $target->settlement_status }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="settlementAmount">Settled amount</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="settlementAmount" name="settled_amount" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="settlementFee">Gateway fee</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="settlementFee" name="gateway_fee" value="0">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="settlementDate">Settled at</label>
                    <input type="date" class="form-control" id="settlementDate" name="settled_at" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="settlementReference">Reference</label>
                    <input type="text" maxlength="64" class="form-control" id="settlementReference" name="reference">
                </div>
                <div class="col-12">
                    <label class="form-label" for="settlementNote">Note</label>
                    <input type="text" maxlength="191" class="form-control" id="settlementNote" name="note">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Record settlement</button>
                </div>
            </form>
        </div>
    </div>

    <div class="d-none" data-report-analytics="{{ json_encode(['charts' => $charts]) }}"></div>
@endif
@endsection

@push('script')
    <script src="{{ asset('admin/assets/js/admin-report-analytics.js') }}"></script>
@endpush
