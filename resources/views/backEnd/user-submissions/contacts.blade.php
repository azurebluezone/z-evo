@extends('backEnd.layouts.app')
@section('title','Contact Messages')

@section('content')
{{--
    Contact Messages

    Same card structure as the Newsletter screen - the two are siblings under
    the same menu, so they read the same way.

    Fixed from the previous version:
      - ONE surplus </div> at the end of the file, which closed the layout's
        .content-wrapper early and ejected the admin footer from it
      - the title was a bare <h4> above the card instead of a card header
      - the pagination was the Bootstrap 4 view in a Bootstrap 5.3 theme
      - the message column was a <div> nested inside a <td>, and the whole
        row was clickable-by-div rather than by a real action
--}}

<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Contact Messages</h5>
        <div class="d-flex flex-wrap gap-2">
            <form method="post" action="{{ route('admin.user-submissions.bulk-notify') }}" data-bulk-notify-form="true" class="d-flex flex-wrap gap-2 m-0">
                @csrf
                <input type="hidden" name="type" value="contacts">
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
            <x-admin.button variant="outline-danger" size="sm" type="button" id="contacts-bulk-delete-btn">
                <i class="icon-base ri ri-delete-bin-7-line me-1"></i>Delete Selected
            </x-admin.button>
        </div>
    </div>

    <form method="post" action="{{ route('admin.user-submissions.bulk-delete') }}" id="contacts-bulk-delete-form" class="d-none">
        @csrf
        <input type="hidden" name="type" value="contacts">
    </form>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th style="width:44px" class="text-center">
                            <input class="form-check-input" type="checkbox" id="contacts-check-all" data-bulk-group="contacts" aria-label="Select all">
                        </th>
                        <th style="width:22%">Name</th>
                        <th style="width:22%" class="d-none d-md-table-cell">Email</th>
                        <th>Subject</th>
                        <th style="width:14%" class="d-none d-md-table-cell">Received</th>
                        <th style="width:56px" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($messages as $item)
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input contacts-check" type="checkbox" value="{{ $item->id }}" aria-label="Select message">
                            </td>
                            <td>
                                <div class="fw-medium text-truncate">{{ $item->name }}</div>
                                <div class="text-muted small d-md-none text-truncate">{{ $item->email }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="text-truncate">{{ $item->email }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-truncate">{{ $item->subject }}</div>
                                <div class="text-muted small text-truncate">{{ $item->message }}</div>
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
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#contactMessageModal{{ $item->id }}">
                                            <i class="icon-base ri ri-eye-line me-2"></i>View
                                        </button>
                                        <div class="dropdown-divider"></div>
                                        <form method="post" action="{{ route('admin.user-submissions.contacts.destroy', $item->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger delete-confirm">
                                                <i class="icon-base ri ri-delete-bin-7-line me-2"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{--
                                    The modal lives inside the cell, not in the
                                    table body: a <div> directly under <tbody>
                                    is invalid markup and the browser hoists it
                                    out of the table, which breaks the row
                                    count and the table's own layout.
                                --}}
                                <div class="modal fade" id="contactMessageModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Message</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-2 fw-medium">{{ $item->name }}</div>
                                                <div class="text-muted mb-3">{{ $item->email }}</div>
                                                <div class="fw-medium mb-2">Subject</div>
                                                <div class="mb-3" style="white-space: pre-wrap;">{{ $item->subject }}</div>
                                                <div class="fw-medium mb-2">Message</div>
                                                <div style="white-space: pre-wrap;">{{ $item->message }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No messages found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($messages->hasPages())
        <div class="card-footer border-top bg-white py-3">
            <div class="d-flex justify-content-end">
                {{ $messages->links('pagination::bootstrap-5') }}
            </div>
        </div>
    @endif
</div>

@push('script')
<script src="{{ asset('admin/assets/js/admin-page-bulk.js') }}"></script>
@endpush
@endsection
