{{--
    backEnd/audit_log/show - one entry, with the values around the change.
--}}
@extends('backEnd.layouts.app')
@section('title', 'Audit Entry')
@section('content')
<div class="row">
    <div class="col-12 col-xl-8">

        <div class="card rounded-0 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">{{ $log->action }}</h5>
                <a href="{{ route('admin.audit-log.index') }}" class="btn btn-sm btn-outline-secondary btn-square waves-effect">
                    <i class="ri ri-arrow-left-line me-1"></i>Back
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <tbody>
                            <tr><th class="w-25 fw-medium">When</th><td>{{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '—' }}</td></tr>
                            <tr><th class="fw-medium">Who</th><td>{{ $log->actor_name ?: 'System' }}</td></tr>
                            <tr><th class="fw-medium">Source</th><td>{{ $log->source }}</td></tr>
                            <tr><th class="fw-medium">IP</th><td>{{ $log->ip ?: '—' }}</td></tr>
                            <tr><th class="fw-medium">Subject</th><td>{{ $log->subject_type ?: '—' }} {{ $log->subject_id }}</td></tr>
                            <tr><th class="fw-medium">What happened</th><td>{{ $log->description ?: '—' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @foreach(['before' => 'Before', 'after' => 'After'] as $key => $label)
            <div class="card rounded-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="mb-0 text-uppercase fw-bold">{{ $label }}</h5>
                </div>
                <div class="card-body">
                    @if(! empty($log->{$key}))
                        <pre class="mb-0 small text-break" style="white-space: pre-wrap;">{{ json_encode($log->{$key}, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @else
                        <span class="text-muted">Nothing recorded.</span>
                    @endif
                </div>
            </div>
        @endforeach

    </div>
</div>
@endsection
