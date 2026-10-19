@extends('backEnd.layouts.app')
@section('title','Newsletter Subscribers')

@section('content')
<div class="card rounded-0">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Newsletter Subscribers</h5>
    </div>

    <div class="card-body">
                <form method="post" action="{{ route('admin.user-submissions.bulk-notify') }}" class="mb-3">
                    @csrf
                    <input type="hidden" name="type" value="newsletter">
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
                            <x-admin.button variant="primary" shape="square" ripple="true" class="w-100" type="submit" style="white-space: nowrap;">Send</x-admin.button>
                        </div>
                        <div class="col-12 col-md-4 col-lg-6 d-flex justify-content-md-end">
                            <x-admin.button variant="danger" shape="square" ripple="true" type="button" id="newsletter-bulk-delete-btn">Delete Selected</x-admin.button>
                        </div>
                    </div>
                </form>

                    <form method="post" action="{{ route('admin.user-submissions.bulk-delete') }}" id="newsletter-bulk-delete-form" class="d-none">
                        @csrf
                        <input type="hidden" name="type" value="newsletter">
                    </form>

                    <div class="card-datatable table-responsive">
                        <table class="table table-hover align-middle mb-0 w-100">
                            <thead>
                                <tr>
                                    <th style="width:40px" class="text-center">
                                        <input class="form-check-input" type="checkbox" id="newsletter-check-all" aria-label="Select all">
                                    </th>
                                    <th>Email</th>
                                    <th class="d-none d-md-table-cell">IP</th>
                                    <th style="width:56px" class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscribers as $item)
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input newsletter-check" type="checkbox" value="{{ $item->id }}" aria-label="Select subscriber">
                                        </td>
                                        <td>
                                            <div class="fw-medium" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $item->email }}</div>
                                            <div class="text-muted small d-md-none" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $item->ip }}</div>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <div class="text-muted" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $item->ip }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <x-admin.button variant="text-secondary" size="sm" shape="icon square" ripple="true" class="dropdown-toggle hide-arrow" type="button" style="padding: .25rem; width: 32px; height: 32px;" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                    <i class="icon-base ri ri-more-2-fill"></i>
                                                </x-admin.button>
                                                <div class="dropdown-menu dropdown-menu-end rounded-0">
                                                    <form method="post" action="{{ route('admin.user-submissions.newsletter.destroy', $item->id) }}" class="d-inline">
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
                                        <td colspan="4" class="text-center text-muted py-4">No subscribers found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer border-top bg-white py-3">
                        <div class="d-flex justify-content-end">
                            {{ $subscribers->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
  (function () {
    const checkAll = document.getElementById('newsletter-check-all');
    const bulkDeleteBtn = document.getElementById('newsletter-bulk-delete-btn');
    const bulkDeleteForm = document.getElementById('newsletter-bulk-delete-form');
    if (!checkAll || !bulkDeleteBtn || !bulkDeleteForm) return;

    const items = () => Array.from(document.querySelectorAll('.newsletter-check'));

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
