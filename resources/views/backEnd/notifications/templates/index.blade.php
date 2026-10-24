@extends('backEnd.layouts.app')
@section('title','Notification Templates')

@section('content')
<div class="card rounded-0">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3">
    <h5 class="mb-0 text-uppercase fw-bold">Notification Templates</h5>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <a href="{{ route('admin.notification-templates.create') }}" class="btn btn-primary btn-square waves-effect">
        <i class="icon-base ri ri-add-line me-1"></i> Add New
      </a>
    </div>
  </div>

  <div class="card-body p-0">
    <div class="card-datatable table-responsive">
      <table class="table table-hover align-middle mb-0 w-100">
        <thead class="table-light">
          <tr>
            <th style="width:60px" class="text-center">#</th>
            <th style="width:22%">Name</th>
            <th>Title</th>
            <th style="width:90px" class="text-center">Active</th>
            <th style="width:56px" class="text-end"></th>
          </tr>
        </thead>
        <tbody>
          @forelse($templates as $tpl)
            <tr>
              <td class="text-center">{{ ($templates->firstItem() ?? 0) + $loop->index }}</td>
              <td>
                <div class="fw-medium" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $tpl->name }}</div>
              </td>
              <td>
                <div style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;">{{ $tpl->title }}</div>
              </td>
              <td class="text-center">
                <form method="post" action="{{ route('admin.notification-templates.toggle', $tpl->id) }}" class="d-inline">
                  @csrf
                  <x-admin.button variant="primary" size="sm" shape="square" ripple="true" class="{{ $tpl->is_active ? 'btn-success' : 'btn-outline-secondary' }}" type="submit">{{ $tpl->is_active ? 'Yes' : 'No' }}</x-admin.button>
                </form>
              </td>
              <td class="text-end">
                <div class="dropdown">
                  <x-admin.button variant="text-secondary" size="sm" shape="icon square" ripple="true" class="dropdown-toggle hide-arrow" type="button" style="padding: .25rem; width: 32px; height: 32px;" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                    <i class="icon-base ri ri-more-2-fill"></i>
                  </x-admin.button>
                  <div class="dropdown-menu dropdown-menu-end rounded-0">
                    <a class="dropdown-item" href="{{ route('admin.notification-templates.edit', $tpl->id) }}">
                      <i class="ri ri-edit-box-line me-2"></i>Edit
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="post" action="{{ route('admin.notification-templates.destroy', $tpl->id) }}" class="d-inline">
                      @csrf
                      <button type="submit" class="dropdown-item text-danger delete-confirm">
                        <i class="ri ri-delete-bin-7-line me-2"></i>Delete
                      </button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">No templates found</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer border-top bg-white py-3">
      <div class="d-flex justify-content-end">
        {{ $templates->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
