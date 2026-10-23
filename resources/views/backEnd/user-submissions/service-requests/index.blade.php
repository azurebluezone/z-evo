@extends('backEnd.layouts.app')
@section('title','Service Requests')

@section('content')
{{--
    Service Requests

    Same card structure as the Newsletter and Contact screens - three
    siblings under one menu, so they read the same way.

    Fixed from the previous version:
      - THREE surplus </div> at the end of the file, which closed the layout's
        .container-xxl and .content-wrapper early, so the admin footer was
        pushed out and the page was squeezed into 654px instead of 1180px
      - the modal markup sat directly under <tbody>, which is invalid and the
        browser hoisted out of the table
      - the pagination used the Bootstrap 4 view in a Bootstrap 5.3 theme
--}}

<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Service Requests</h5>
        <div class="d-flex flex-wrap gap-2">
            <div class="btn-group" role="group" aria-label="Filter by status">
                <a href="{{ route('admin.user-submissions.service-requests.index') }}"
                   class="btn btn-sm btn-outline-secondary {{ request('status') === null ? 'active' : '' }}">All</a>
                <a href="{{ route('admin.user-submissions.service-requests.index', ['status' => 'pending']) }}"
                   class="btn btn-sm btn-outline-secondary {{ request('status') === 'pending' ? 'active' : '' }}">Pending</a>
                <a href="{{ route('admin.user-submissions.service-requests.index', ['status' => 'approved']) }}"
                   class="btn btn-sm btn-outline-secondary {{ request('status') === 'approved' ? 'active' : '' }}">Approved</a>
            </div>
        </div>
    </div>

    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <div class="d-flex flex-wrap gap-2">
            <form method="post" action="{{ route('admin.user-submissions.bulk-notify') }}" class="d-flex flex-wrap gap-2 m-0">
                @csrf
                <input type="hidden" name="type" value="service_requests">
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
            <x-admin.button variant="outline-danger" size="sm" type="button" id="service-requests-bulk-delete-btn">
                <i class="icon-base ri ri-delete-bin-7-line me-1"></i>Delete Selected
            </x-admin.button>
        </div>
    </div>

    <form method="post" action="{{ route('admin.user-submissions.bulk-delete') }}" id="service-requests-bulk-delete-form" class="d-none">
        @csrf
        <input type="hidden" name="type" value="service_requests">
    </form>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width:44px" class="text-center">
                            <input class="form-check-input" type="checkbox" id="service-requests-check-all" aria-label="Select all">
                        </th>
                        <th style="width:20%">Name</th>
                        <th style="width:14%" class="d-none d-md-table-cell">Phone</th>
                        <th style="width:14%" class="d-none d-md-table-cell">Service Type</th>
                        <th>Message</th>
                        <th style="width:10%" class="d-none d-md-table-cell">Status</th>
                        <th style="width:56px" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $item)
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input service-requests-check" type="checkbox" value="{{ $item->id }}" aria-label="Select request">
                            </td>
                            <td>
                                <div class="fw-medium text-truncate">{{ $item->first_name }} {{ $item->last_name }}</div>
                                <div class="d-md-none text-muted small text-truncate">{{ $item->phone }} · {{ $item->service_type }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="text-truncate">{{ $item->phone }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="text-truncate">{{ $item->service_type }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="text-muted flex-grow-1" style="min-width: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $item->message }}
                                    </div>
                                    <x-admin.button variant="text-secondary" size="sm" shape="icon" type="button" data-bs-toggle="modal" data-bs-target="#serviceRequestMessageModal{{ $item->id }}" title="View">
                                        <i class="icon-base ri ri-eye-line"></i>
                                    </x-admin.button>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="dropdown">
                                    <x-admin.button variant="outline-secondary" size="sm" class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ $item->status === 'approved' ? 'Approved' : 'Pending' }}
                                    </x-admin.button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form action="{{ route('admin.user-submissions.service-requests.status', $item->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="dropdown-item">Pending</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.user-submissions.service-requests.status', $item->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="dropdown-item">Approved</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <x-admin.button variant="text-secondary" size="sm" shape="icon" type="button" class="dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                        <i class="icon-base ri ri-more-2-fill"></i>
                                    </x-admin.button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('admin.user-submissions.service-requests.show', $item->id) }}">
                                            <i class="icon-base ri ri-information-line me-2"></i>Details
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.user-submissions.service-requests.edit', $item->id) }}">
                                            <i class="icon-base ri ri-edit-box-line me-2"></i>Edit
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('admin.user-submissions.service-requests.destroy', $item->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger delete-confirm">
                                                <i class="icon-base ri ri-delete-bin-7-line me-2"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- The modal stays inside the cell: a <div> directly
                                     under <tbody> is invalid and the browser
                                     lifts it out of the table. --}}
                                <div class="modal fade" id="serviceRequestMessageModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Message</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-2 fw-medium">{{ $item->first_name }} {{ $item->last_name }}</div>
                                                <div class="text-muted mb-3">{{ $item->created_at ? $item->created_at->format('Y-m-d H:i') : '' }}</div>
                                                <div style="white-space: pre-wrap;">{{ $item->message }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No service requests found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($requests->hasPages())
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end">
                {{ $requests->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

@push('script')
<script>
(function () {
    'use strict';

    var checkAll = document.getElementById('service-requests-check-all');
    var bulkDeleteBtn = document.getElementById('service-requests-bulk-delete-btn');
    var bulkDeleteForm = document.getElementById('service-requests-bulk-delete-form');
    if (!checkAll || !bulkDeleteBtn || !bulkDeleteForm) return;

    var items = function () {
        return Array.prototype.slice.call(document.querySelectorAll('.service-requests-check'));
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
