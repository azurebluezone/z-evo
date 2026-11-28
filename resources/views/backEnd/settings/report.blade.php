@extends('backEnd.layouts.app')
@section('title', 'Report Settings')

@section('content')
<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Report &amp; Stock Settings</h4>
        <p class="text-muted mb-0 small">Stock thresholds, the report email and the daily auto-send schedule.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.dashboard') }}" class="btn btn-sm btn-outline-secondary">Dashboard</a>
        <a href="{{ route('admin.reports.stock-alerts') }}" class="btn btn-sm btn-outline-secondary">Stock Alerts</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Stock logic</h5>
                <p class="text-muted small mb-0">Defaults: warning 0 pcs, remaining badge 2 pcs. A submitted 0 is kept as 0.</p>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('admin.reports.settings.update') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="stock_warning_threshold" class="form-label">Warning threshold (pcs)</label>
                            <input type="number" min="0" max="100000" step="1" id="stock_warning_threshold" name="stock_warning_threshold"
                                   value="{{ old('stock_warning_threshold', $settings->stock_warning_threshold) }}" class="form-control" required>
                            <div class="form-text">A product warns when available &lt;= this value. 0 means out-of-stock only.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="stock_remaining_threshold" class="form-label">Remaining badge threshold (pcs)</label>
                            <input type="number" min="0" max="100000" step="1" id="stock_remaining_threshold" name="stock_remaining_threshold"
                                   value="{{ old('stock_remaining_threshold', $settings->stock_remaining_threshold) }}" class="form-control" required>
                            <div class="form-text">Shows "only N left" when available &lt;= this value. Default 2; 0 is allowed.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="report_email" class="form-label">Report email</label>
                            <input type="email" id="report_email" name="report_email"
                                   value="{{ old('report_email', $settings->report_email) }}" class="form-control" placeholder="admin@example.com">
                            <div class="form-text">The A-to-Z report is sent to this address.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="send_time" class="form-label">Daily send time</label>
                            <input type="time" id="send_time" name="send_time"
                                   value="{{ old('send_time', substr((string) $settings->send_time, 0, 5)) }}" class="form-control" required>
                            <div class="form-text">The scheduler checks this clock every minute and sends once per day.</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto_send_enabled" name="auto_send_enabled" value="1"
                                       @checked(old('auto_send_enabled', $settings->auto_send_enabled))>
                                <label class="form-check-label" for="auto_send_enabled">Auto-send the daily report</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Save settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card mb-4">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Mail transport</h5>
                <p class="text-muted small mb-0">Read from config/mail.php, the same connection the app already uses.</p>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Mailer</span>
                    <span class="fw-semibold">{{ $mailer }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">SMTP host</span>
                    <span class="fw-semibold">{{ $mailHost !== '' ? $mailHost : '-' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">From address</span>
                    <span class="fw-semibold">{{ $mailFrom !== '' ? $mailFrom : '-' }}</span>
                </div>
                <form method="post" action="{{ route('admin.reports.settings.test') }}" class="mt-3">
                    @csrf
                    <label for="test_email" class="form-label">Send a test report to</label>
                    <div class="input-group">
                        <input type="email" id="test_email" name="report_email"
                               value="{{ old('report_email', $settings->report_email) }}" class="form-control" required>
                        <button type="submit" class="btn btn-outline-primary">Send test</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Current stock signal</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Products in the batch</span>
                    <span class="fw-semibold">{{ number_format($signal['count'], 0) }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Out of stock</span>
                    <span class="fw-semibold">{{ number_format($signal['out'], 0) }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">At or below warning</span>
                    <span class="fw-semibold">{{ number_format($signal['low'], 0) }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Remaining badge count</span>
                    <span class="fw-semibold">{{ number_format($signal['remaining_count'], 0) }}</span>
                </div>
                @if ($signal['active'])
                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        The navbar signal is active for this batch. It stays until dismissed.
                    </div>
                @else
                    <div class="alert alert-success mt-3 mb-0" role="alert">
                        No undismissed stock signal right now.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
