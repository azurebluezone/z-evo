{{--
    Saved filter sets for one report page (Phase 2 item 4).

    A view is a name plus the query string the page already understands, so
    restoring one is a plain link and the report keeps one source of truth: the
    URL. Shared views are visible to every report reader; a private view only
    to its owner, who is also the only one who can delete it.

    Expected variables: $viewKey (string), $savedViews (collection).
--}}
@php
    $savedViews = $savedViews ?? collect();
    $viewKey = $viewKey ?? '';
    $currentFilters = array_filter([
        'start_date' => request('start_date'),
        'end_date' => request('end_date'),
    ], fn ($value) => $value !== null && $value !== '');
@endphp

<div class="border-top mt-3 pt-3" data-saved-views="{{ $viewKey }}">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <span class="small text-muted">Saved views</span>
        @forelse ($savedViews as $savedView)
            <span class="badge bg-label-secondary d-inline-flex align-items-center gap-2">
                <a href="{{ route($savedView->routeName(), $savedView->filterQuery()) }}" class="text-reset">{{ $savedView->name }}</a>
                @if ($savedView->is_shared)
                    <span class="small">shared</span>
                @endif
                @if ((int) $savedView->user_id === (int) auth()->id())
                    <form method="post" action="{{ route('admin.reports.views.destroy', $savedView->id) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm p-0 text-danger" aria-label="Delete saved view {{ $savedView->name }}">delete</button>
                    </form>
                @endif
            </span>
        @empty
            <span class="small text-muted">None yet.</span>
        @endforelse
    </div>

    <form method="post" action="{{ route('admin.reports.views.store') }}" class="row g-2 align-items-end">
        @csrf
        <input type="hidden" name="view_key" value="{{ $viewKey }}">
        <input type="hidden" name="start_date" value="{{ $currentFilters['start_date'] ?? '' }}">
        <input type="hidden" name="end_date" value="{{ $currentFilters['end_date'] ?? '' }}">
        <div class="col-12 col-md-4">
            <label for="saved_view_name_{{ $viewKey }}" class="form-label small mb-1">Save this filter as</label>
            <input type="text" id="saved_view_name_{{ $viewKey }}" name="name" maxlength="120" required
                   class="form-control form-control-sm" placeholder="e.g. Dhaka this month">
        </div>
        <div class="col-auto">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_shared" value="1" id="saved_view_shared_{{ $viewKey }}">
                <label class="form-check-label small" for="saved_view_shared_{{ $viewKey }}">Shared</label>
            </div>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-outline-primary">Save view</button>
        </div>
    </form>
</div>
