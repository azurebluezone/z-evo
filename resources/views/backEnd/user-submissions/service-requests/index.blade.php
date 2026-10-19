@extends('backEnd.layouts.app')
@section('title','Service Requests')

@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Service Requests</h5>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary btn-square waves-effect" href="{{ route('admin.user-submissions.service-requests.index') }}">All</a>
            <a class="btn btn-outline-secondary btn-square waves-effect" href="{{ route('admin.user-submissions.service-requests.index', ['status' => 'pending']) }}">Pending</a>
            <a class="btn btn-outline-secondary btn-square waves-effect" href="{{ route('admin.user-submissions.service-requests.index', ['status' => 'approved']) }}">Approved</a>
        </div>
    </div>

    <div class="card-body">
                <form method="post" action="{{ route('admin.user-submissions.bulk-notify') }}" class="mb-3">
                    @csrf
                    <input type="hidden" name="type" value="service_requests">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-5 col-lg-4">
                            <label class="form-label">Template</label>
                            <select name="template_id" class="form-select form-select-sm">
                                <option value="" selected disabled>Select template</option>
                                @foreach(($templates ?? []) as $tpl)
                                    <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-3 col-lg-2">
                            <x-admin.button variant="primary" size="sm" class="w-100" type="submit">Send</x-admin.button>
                        </div>
                        <div class="col-12 col-md-4 col-lg-6 d-flex justify-content-md-end">
                            <x-admin.button variant="danger" size="sm" type="button" id="service-requests-bulk-delete-btn">Delete Selected</x-admin.button>
                        </div>
                    </div>
                </form>

                    <form method="post" action="{{ route('admin.user-submissions.bulk-delete') }}" id="service-requests-bulk-delete-form" class="d-none">
                        @csrf
                        <input type="hidden" name="type" value="service_requests">
                    </form>

                    <div class="card-datatable table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100">
                        <thead>
                            <tr>
                                <th style="width:40px" class="text-center">
                                    <input class="form-check-input" type="checkbox" id="service-requests-check-all" aria-label="Select all">
                                </th>
                                <th style="width:24%">Name</th>
                                <th style="width:12%" class="d-none d-md-table-cell">Phone</th>
                                <th style="width:14%" class="d-none d-md-table-cell">Service Type</th>
                                <th>Message</th>
                                <th style="width:9%" class="d-none d-md-table-cell">Status</th>
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
                                        <div class="fw-medium">{{ $item->first_name }} {{ $item->last_name }}</div>
                                        <div class="d-md-none text-muted small" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $item->phone }} · {{ $item->service_type }}
                                        </div>
                                        <div class="d-md-none mt-1">
                                            <div class="dropdown d-inline-block">
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
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $item->phone }}</div>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $item->service_type }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="text-muted flex-grow-1" style="min-width: 0; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                                {{ $item->message }}
                                            </div>
                                            <x-admin.button variant="text-secondary" size="sm" shape="icon" type="button" data-bs-toggle="modal" data-bs-target="#serviceRequestMessageModal{{ $item->id }}" title="View" data-bs-toggle="tooltip" data-bs-title="View">
                                                <i class="icon-base ri ri-eye-line"></i>
                                            </x-admin.button>
                                        </div>

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
                                    <td class="d-none d-md-table-cell">
                                        <div class="dropdown d-inline-block">
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
                                            <x-admin.button variant="text-secondary" size="sm" shape="icon square" ripple="true" class="dropdown-toggle hide-arrow" type="button" style="padding: .25rem; width: 32px; height: 32px;" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="icon-base ri ri-more-2-fill"></i>
                                            </x-admin.button>
                                            <div class="dropdown-menu dropdown-menu-end rounded-0">
                                                <a class="dropdown-item" href="{{ route('admin.user-submissions.service-requests.show', $item->id) }}">
                                                    <i class="ri-information-line me-2"></i>Details
                                                </a>
                                                <a class="dropdown-item" href="{{ route('admin.user-submissions.service-requests.edit', $item->id) }}">
                                                    <i class="ri-edit-box-line me-2"></i>Edit
                                                </a>

                                                <div class="dropdown-divider"></div>

                                                <form action="{{ route('admin.user-submissions.service-requests.destroy', $item->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-danger delete-confirm">
                                                        <i class="ri-delete-bin-7-line me-2"></i>Delete
                                                    </button>
                                                </form>
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

                    <div class="mt-3">
                        {{ $requests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
  (function () {
    const checkAll = document.getElementById('service-requests-check-all');
    const bulkDeleteBtn = document.getElementById('service-requests-bulk-delete-btn');
    const bulkDeleteForm = document.getElementById('service-requests-bulk-delete-form');
    if (!checkAll || !bulkDeleteBtn || !bulkDeleteForm) return;

    const items = () => Array.from(document.querySelectorAll('.service-requests-check'));

    checkAll.addEventListener('change', function () {
      items().forEach(cb => { cb.checked = checkAll.checked; });
    });

    bulkDeleteBtn.addEventListener('click', function () {
      const selected = items().filter(cb => cb.checked).map(cb => cb.value);
      if (!selected.length) return;
      bulkDeleteForm.querySelectorAll('input[name="ids[]"]').forEach(n => n.remove());
      selected.forEach(id => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'ids[]';
        inp.value = id;
        bulkDeleteForm.appendChild(inp);
      });
      bulkDeleteForm.submit();
    });

    const notifyForm = document.querySelector('form[action="{{ route('admin.user-submissions.bulk-notify') }}"]');
    if (notifyForm) {
      notifyForm.addEventListener('submit', function (e) {
        const selected = items().filter(cb => cb.checked).map(cb => cb.value);
        if (!selected.length) {
          e.preventDefault();
          return;
        }
        notifyForm.querySelectorAll('input[name="ids[]"]').forEach(n => n.remove());
        selected.forEach(id => {
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'ids[]';
          inp.value = id;
          notifyForm.appendChild(inp);
        });
      });
    }
  })();
</script>
@endsection
