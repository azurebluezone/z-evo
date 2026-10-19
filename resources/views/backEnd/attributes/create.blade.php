@extends('backEnd.layouts.app')
@section('title','Create Attribute')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Create Attribute</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="adminForm">
              <i class="ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="row">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Create Attribute</h5>
                <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form method="POST" action="{{ route('admin.attributes.store') }}" class="row g-4" id="adminForm">
                    @csrf

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="name" id="name" class="form-control rounded-0 @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Attribute Name" />
                            <label for="name">Attribute Name *</label>
                        </div>
                        @error('name')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mb-1">
                            <input type="text" name="slug" id="slug" class="form-control rounded-0 @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="Attribute Slug (Optional)" />
                            <label for="slug">Slug (Optional)</label>
                        </div>
                        <div class="form-text text-muted"></div>
                        @error('slug')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block fw-semibold">Status</label>
                        <div class="form-check form-switch custom-switch-sharp">
                            <input type="checkbox" name="status" class="form-check-input rounded-0" value="1" {{ old('status', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                        @error('status')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <x-admin.button variant="success" shape="square" ripple="true" type="submit">
                            <i class="ri-save-line me-1"></i> Create Attribute
                        </x-admin.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    </div>
  </div>
</div>
@endsection
