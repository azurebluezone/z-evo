@extends('backEnd.layouts.app')
@section('title','Edit Notification Template')

@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Edit Notification Template</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.notification-templates.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="edit-notification-templateForm">
              <i class="ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="row">
    <div class="col-12">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h4 class="mb-0">Edit Template</h4>
        <a href="{{ route('admin.notification-templates.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
      </div>

      <div class="card">
        <div class="card-body">
          <form method="post" action="{{ route('admin.notification-templates.update', $template->id) }}">
            @csrf

            <div class="mb-3">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}">
            </div>

            <div class="mb-3">
              <label class="form-label">Title</label>
              <input type="text" name="title" class="form-control" value="{{ old('title', $template->title) }}">
            </div>

            <div class="mb-3">
              <label class="form-label">Body</label>
              <textarea name="body" class="form-control" rows="6">{{ old('body', $template->body) }}</textarea>
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="is_active" id="is_active" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_active">Active</label>
            </div>

            <x-admin.button variant="primary" type="submit">Update</x-admin.button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

    </div>
  </div>
</div>
@endsection
