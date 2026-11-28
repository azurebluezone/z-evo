{{--
    Shared date filter for the four analytics reports.

    Both dates are required for a range; leaving them empty covers everything.
    The form always submits to the report's own route, so the page never
    changes subject while filtering.
--}}
<div class="card mb-4 no-print">
    <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Filters</h5>
    </div>
    <div class="card-body">
        <form method="get" action="{{ $action }}">
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
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    <a href="{{ $action }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>

        @include('backEnd.reports.partials._saved_views', [
            'viewKey' => $viewKey ?? '',
            'savedViews' => $savedViews ?? collect(),
        ])
    </div>
</div>
