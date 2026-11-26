{{--
    backEnd/audit_log - the trail of everything that happened.

    House style: card rounded-0, header bg-white border-bottom py-3, h5
    text-uppercase fw-bold, x-admin.button for actions.
--}}
@extends('backEnd.layouts.app')
@section('title', 'Audit Log')
@section('content')
<div class="row">
    <div class="col-12">

        <div class="card rounded-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 gap-2">
                <h5 class="mb-0 text-uppercase fw-bold">
                    <i class="ri ri-file-history-line me-2"></i>Audit Log
                </h5>
                <div class="text-muted small">{{ number_format($total) }} entr{{ $total === 1 ? 'y' : 'ies' }} recorded</div>
            </div>

            {{-- Filters: the way an admin asks - who, what, about what, when. --}}
            <div class="card-body border-bottom">
                <form method="get" action="{{ route('admin.audit-log.index') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label small mb-1" for="filterAction">Action</label>
                        <select id="filterAction" name="action" class="form-select form-select-sm">
                            <option value="">All actions</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label small mb-1" for="filterActor">Actor</label>
                        <input type="text" id="filterActor" name="actor" value="{{ $filters['actor'] }}" class="form-control form-control-sm" placeholder="name or id">
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label small mb-1" for="filterSubjectType">Subject</label>
                        <select id="filterSubjectType" name="subject_type" class="form-select form-select-sm">
                            <option value="">All subjects</option>
                            @foreach($subjectTypes as $type)
                                <option value="{{ $type }}" @selected($filters['subject_type'] === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label small mb-1" for="filterSubjectId">Subject ID</label>
                        <input type="text" id="filterSubjectId" name="subject_id" value="{{ $filters['subject_id'] }}" class="form-control form-control-sm" placeholder="e.g. 512">
                    </div>
                    <div class="col-6 col-md-3 col-lg-1">
                        <label class="form-label small mb-1" for="filterFrom">From</label>
                        <input type="date" id="filterFrom" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3 col-lg-1">
                        <label class="form-label small mb-1" for="filterTo">To</label>
                        <input type="date" id="filterTo" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-lg-2 d-flex gap-2">
                        <x-admin.button variant="primary" size="sm" shape="square" type="submit">Filter</x-admin.button>
                        <a href="{{ route('admin.audit-log.index') }}" class="btn btn-sm btn-outline-secondary btn-square waves-effect">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>When</th>
                            <th>Action</th>
                            <th>Who</th>
                            <th>Subject</th>
                            <th>What happened</th>
                            <th class="text-end">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap">
                                {{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : '—' }}
                            </td>
                            <td>
                                <code class="small">{{ $log->action }}</code>
                            </td>
                            <td>
                                {{ $log->actor_name ?: '—' }}
                                @if($log->source && $log->source !== 'web')
                                    <span class="badge bg-label-secondary ms-1">{{ $log->source }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($log->subject_type)
                                    <span class="badge bg-label-primary">{{ $log->subject_type }}</span>
                                    <span class="small">{{ $log->subject_id }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small">{{ $log->description ?: '—' }}</td>
                            <td class="text-end">
                                <x-admin.button variant="outline-primary" size="sm" shape="square"
                                                href="{{ route('admin.audit-log.show', $log->id) }}">Open</x-admin.button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Nothing has been recorded yet.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="card-footer bg-white border-top py-3">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
