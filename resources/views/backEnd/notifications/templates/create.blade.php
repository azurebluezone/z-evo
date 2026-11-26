@extends('backEnd.layouts.app')
@push('css')
<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-page-notification-templates.css') }}">
@endpush
@section('title','Create Notification Template')

@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Create Notification Template</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.notification-templates.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="adminForm">
              <i class="ri ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="w-100">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form method="post" action="{{ route('admin.notification-templates.store') }}" id="adminForm">
            @csrf

            @include('backEnd.notifications.templates._form')

            <x-admin.button variant="primary" type="submit">Save</x-admin.button>
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
