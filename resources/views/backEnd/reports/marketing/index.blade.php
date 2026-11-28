@extends('backEnd.layouts.app')
@section('title', 'Marketing Spend')

@section('content')
{{--
    Marketing spend entry, Phase 3 item 1 of docs/report-completion-plan.md.

    One row per channel per day. Nothing is derived here: the totals below are
    the same aggregates the ROAS and CPA reports will read, so entering a row
    and seeing the number change is the whole point of the screen.
--}}

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3" data-marketing-spend>
    <div>
        <h4 class="mb-0">Marketing Spend</h4>
        <p class="text-muted mb-0 small">
            Enter what was spent per channel and day. This is the denominator of ROAS, so it stays
            Admin-only and is never inferred from orders.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.reports.expenses.index') }}" class="btn btn-sm btn-outline-secondary">Expenses</a>
        <a href="{{ route('admin.reports.overview') }}" class="btn btn-sm btn-outline-secondary">Sales &amp; Profit</a>
        <a href="{{ route('admin.reports.dashboard') }}" class="btn btn-sm btn-outline-secondary">Report Dashboard</a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-4 no-print">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Filter</h5>
    </div>
    <div class="card-body">
        <form method="get" action="{{ route('admin.reports.marketing-spends.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label for="start_date" class="form-label small mb-1">From</label>
                <input type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-3">
                <label for="end_date" class="form-label small mb-1">To</label>
                <input type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                <a href="{{ route('admin.reports.marketing-spends.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-3">
            Filters match <code>spent_at</code>. Empty filter means every recorded spend.
        </p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Total spend</p>
                <h4 class="mb-0" data-marketing-total="{{ number_format($totals['spent'], 2, '.', '') }}">BDT {{ number_format($totals['spent'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Entries</p>
                <h4 class="mb-0">{{ number_format($totals['entries'], 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Impressions</p>
                <h4 class="mb-0">{{ number_format($totals['impressions'], 0) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1 small">Clicks / CTR</p>
                <h4 class="mb-0">{{ number_format($totals['clicks'], 0) }}</h4>
                <p class="text-muted small mb-0">
                    {{ $totals['impressions'] > 0 ? number_format($totals['clicks'] / $totals['impressions'] * 100, 2).'%' : 'no impressions' }}
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Add spend</h5>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('admin.reports.marketing-spends.store') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $filters['start_date'] }}">
                    <input type="hidden" name="end_date" value="{{ $filters['end_date'] }}">

                    <div class="col-12 col-md-4">
                        <label for="channel" class="form-label">Channel</label>
                        <select id="channel" name="channel" class="form-select" required>
                            @foreach ($channels as $channel)
                                <option value="{{ $channel }}" @selected(old('channel') === $channel)>{{ ucfirst($channel) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="spent_at" class="form-label">Spent on</label>
                        <input type="date" id="spent_at" name="spent_at" value="{{ old('spent_at', now()->toDateString()) }}" class="form-control" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="spent" class="form-label">Amount (BDT)</label>
                        <input type="number" step="0.01" min="0" id="spent" name="spent" value="{{ old('spent') }}" class="form-control" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="campaign_name" class="form-label">Campaign</label>
                        <input type="text" id="campaign_name" name="campaign_name" value="{{ old('campaign_name') }}" maxlength="120" class="form-control" placeholder="e.g. Eid retargeting">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="impressions" class="form-label">Impressions</label>
                        <input type="number" min="0" id="impressions" name="impressions" value="{{ old('impressions', 0) }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="clicks" class="form-label">Clicks</label>
                        <input type="number" min="0" id="clicks" name="clicks" value="{{ old('clicks', 0) }}" class="form-control">
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="source_reference" class="form-label">Source reference</label>
                        <input type="text" id="source_reference" name="source_reference" value="{{ old('source_reference') }}" maxlength="64" class="form-control" placeholder="invoice or ad account id">
                    </div>
                    <div class="col-12 col-md-8">
                        <label for="notes" class="form-label">Notes</label>
                        <input type="text" id="notes" name="notes" value="{{ old('notes') }}" maxlength="1000" class="form-control">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save spend</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-0">Spend by channel</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Channel</th>
                            <th class="text-end">Entries</th>
                            <th class="text-end">Spend</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byChannel as $row)
                            <tr>
                                <td>{{ ucfirst($row->channel) }}</td>
                                <td class="text-end">{{ number_format((int) $row->entries, 0) }}</td>
                                <td class="text-end">BDT {{ number_format((float) $row->spent, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No spend recorded for this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Recorded spends</h5>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Channel</th>
                    <th>Campaign</th>
                    <th class="text-end">Impr.</th>
                    <th class="text-end">Clicks</th>
                    <th class="text-end">Spend</th>
                    <th>Reference</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($spends as $spend)
                    <tr>
                        <td>{{ $spend->spent_at?->toDateString() }}</td>
                        <td>{{ ucfirst($spend->channel) }}</td>
                        <td>{{ $spend->campaign_name ?: '-' }}</td>
                        <td class="text-end">{{ number_format((int) $spend->impressions, 0) }}</td>
                        <td class="text-end">{{ number_format((int) $spend->clicks, 0) }}</td>
                        <td class="text-end">BDT {{ number_format((float) $spend->spent, 2) }}</td>
                        <td class="small text-muted">{{ $spend->source_reference ?: '-' }}</td>
                        <td class="text-end">
                            <form action="{{ route('admin.reports.marketing-spends.destroy') }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this spend?')">
                                @csrf
                                <input type="hidden" name="spend_id" value="{{ $spend->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No spend recorded yet. Add the first row above.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($spends->hasPages())
        <div class="card-body border-top">
            {{ $spends->links() }}
        </div>
    @endif
</div>
@endsection
