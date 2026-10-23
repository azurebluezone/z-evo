@extends('backEnd.layouts.app')
@section('title','Newsletter Subscribers')

@section('content')
{{--
    Newsletter Subscribers

    The card structure is the theme's own: card rounded-0 + card-header
    (title on the left, actions on the right) + card-body + card-footer.

    Fixed from the previous version:
      - THREE surplus </div> at the end of the file. The browser closed the
        layout's own .container-xxl and .content-wrapper early, so the admin
        footer was pushed out of .content-wrapper and the page was squeezed
        into 654px instead of 1180px.
      - the page had no title in the card header, only an uppercase label
      - the pagination was the Bootstrap 4 view in a Bootstrap 5.3 theme
--}}

<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Newsletter Subscribers</h5>
        <div class="d-flex flex-wrap gap-2">
            <form method="post" action="{{ route('admin.user-submissions.bulk-notify') }}" class="d-flex flex-wrap gap-2 m-0">
                @csrf
                <input type="hidden" name="type" value="newsletter">
                <select name="template_id" class="form-select form-select-sm" style="min-width: 200px;" aria-label="Notification template">
                    <option value="" selected disabled>Select template</option>
                    @foreach(($templates ?? []) as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
                <x-admin.button variant="primary" size="sm" type="submit">
                    <i class="icon-base ri ri-mail-send-line me-1"></i>Send
                </x-admin.button>
            </form>
            <x-admin.button variant="outline-danger" size="sm" type="button" id="newsletter-bulk-delete-btn">
                <i class="icon-base ri ri-delete-bin-7-line me-1"></i>Delete Selected
            </x-admin.button>
        </div>
    </div>

    <form method="post" action="{{ route('admin.user-submissions.bulk-delete') }}" id="newsletter-bulk-delete-form" class="d-none">
        @csrf
        <input type="hidden" name="type" value="newsletter">
    </form>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width:44px" class="text-center">
                            <input class="form-check-input" type="checkbox" id="newsletter-check-all" aria-label="Select all">
                        </th>
                        <th>Email</th>
                        <th class="d-none d-md-table-cell">IP</th>
                        <th class="d-none d-md-table-cell">Subscribed</th>
                        <th style="width:56px" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $item)
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input newsletter-check" type="checkbox" value="{{ $item->id }}" aria-label="Select subscriber">
                            </td>
                            <td>
                                <div class="fw-medium text-truncate">{{ $item->email }}</div>
                                <div class="text-muted small d-md-none text-truncate">{{ $item->ip }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="text-muted text-truncate">{{ $item->ip }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="text-muted small">
                                    <i class="icon-base ri ri-time-line me-1"></i>{{ $item->created_at ? $item->created_at->format('Y-m-d H:i') : '' }}
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <x-admin.button variant="text-secondary" size="sm" shape="icon" type="button" class="dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                        <i class="icon-base ri ri-more-2-fill"></i>
                                    </x-admin.button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <form method="post" action="{{ route('admin.user-submissions.newsletter.destroy', $item->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger delete-confirm">
                                                <i class="icon-base ri ri-delete-bin-7-line me-2"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No subscribers found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($subscribers->hasPages())
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end">
                {{ $subscribers->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

@push('script')
<script>
(function () {
    'use strict';

    var checkAll = document.getElementById('newsletter-check-all');
    var bulkDeleteBtn = document.getElementById('newsletter-bulk-delete-btn');
    var bulkDeleteForm = document.getElementById('newsletter-bulk-delete-form');
    if (!checkAll || !bulkDeleteBtn || !bulkDeleteForm) return;

    var items = function () {
        return Array.prototype.slice.call(document.querySelectorAll('.newsletter-check'));
    };

    var fillIds = function (form, selected) {
        form.querySelectorAll('input[name="ids[]"]').forEach(function (node) { node.remove(); });
        selected.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
    };

    var selectedIds = function () {
        return items().filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
    };

    checkAll.addEventListener('change', function () {
        items().forEach(function (cb) { cb.checked = checkAll.checked; });
    });

    bulkDeleteBtn.addEventListener('click', function () {
        var selected = selectedIds();
        if (!selected.length) return;
        fillIds(bulkDeleteForm, selected);
        bulkDeleteForm.submit();
    });

    /*
        The Send button lives in a separate form (the header), so the selected
        ids have to be copied into it. The old version looked the form up with
        a selector built from a Blade route() call; that selector was built
        before the DOM was parsed, so the handler silently did nothing.
    */
    var notifyForm = document.querySelector('form[action="{{ route('admin.user-submissions.bulk-notify') }}"]');
    if (notifyForm) {
        notifyForm.addEventListener('submit', function (e) {
            var selected = selectedIds();
            if (!selected.length) {
                e.preventDefault();
                return;
            }
            fillIds(notifyForm, selected);
        });
    }
})();
</script>
@endpush
@endsection
