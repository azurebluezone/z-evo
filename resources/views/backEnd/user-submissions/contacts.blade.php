@extends('backEnd.layouts.app')
@section('title','Contact Messages')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="mb-0">Contact Messages</h4>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="post" action="{{ route('admin.user-submissions.bulk-notify') }}" class="mb-3">
                    @csrf
                    <input type="hidden" name="type" value="contacts">
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
                            <x-admin.button variant="danger" size="sm" type="button" id="contacts-bulk-delete-btn">Delete Selected</x-admin.button>
                        </div>
                    </div>
                </form>

                    <form method="post" action="{{ route('admin.user-submissions.bulk-delete') }}" id="contacts-bulk-delete-form" class="d-none">
                        @csrf
                        <input type="hidden" name="type" value="contacts">
                    </form>

                    <div class="card-datatable table-responsive">
                        <table class="table table-striped table-sm w-100 align-middle">
                            <thead>
                                <tr>
                                    <th style="width:40px" class="text-center">
                                        <input class="form-check-input" type="checkbox" id="contacts-check-all" aria-label="Select all">
                                    </th>
                                    <th style="width:18%">Name</th>
                                    <th style="width:20%" class="d-none d-md-table-cell">Email</th>
                                    <th>Subject</th>
                                    <th style="width:16%" class="d-none d-md-table-cell">Date</th>
                                    <th style="width:56px" class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($messages as $item)
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input contacts-check" type="checkbox" value="{{ $item->id }}" aria-label="Select message">
                                        </td>
                                        <td>
                                            <div class="fw-medium" style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $item->name }}</div>
                                            <div class="d-md-none text-muted small" style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $item->email }}</div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <div style="max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $item->email }}</div>
                                        </td>
                                        <td>
                                            <div style="min-width: 0; overflow: hidden;">
                                                <div class="fw-medium" style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical;">{{ $item->subject }}</div>
                                                <div class="text-muted small" style="overflow: hidden; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical;">{{ $item->message }}</div>
                                            </div>

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
                                        <td class="d-none d-md-table-cell">
                                            <div class="text-muted small"><i class="ri-time-line me-1"></i>{{ $item->created_at ? $item->created_at->format('Y-m-d H:i') : '' }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <x-admin.button variant="text-secondary" size="sm" shape="icon" class="dropdown-toggle hide-arrow" type="button" style="padding: .25rem; width: 32px; height: 32px;" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                    <i class="icon-base ri ri-more-2-fill"></i>
                                                </x-admin.button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#contactMessageModal{{ $item->id }}">
                                                        <i class="ri-eye-line me-2"></i>View
                                                    </button>
                                                    <div class="dropdown-divider"></div>
                                                    <form method="post" action="{{ route('admin.user-submissions.contacts.destroy', $item->id) }}" class="d-inline">
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
                                        <td colspan="6" class="text-center text-muted py-4">No messages found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $messages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
  (function () {
    const checkAll = document.getElementById('contacts-check-all');
    const bulkDeleteBtn = document.getElementById('contacts-bulk-delete-btn');
    const bulkDeleteForm = document.getElementById('contacts-bulk-delete-form');
    if (!checkAll || !bulkDeleteBtn || !bulkDeleteForm) return;

    const items = () => Array.from(document.querySelectorAll('.contacts-check'));

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
