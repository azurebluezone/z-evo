@extends('backEnd.layouts.app')
@section('title','Category Create')
@section('content')


<div class="row">
  <div class="col-12">
    <div class="card rounded-0 mb-4">
      <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Category Create</h5>
        <div class="d-flex align-items-center gap-2">
          <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
            <i class="ri-arrow-left-line me-1"></i> Manage
          </a>
          <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="categoryForm">
            <i class="ri-save-line me-1"></i> Submit
          </x-admin.button>
        </div>
      </div>
    </div>

    <div class="app-ecommerce">
      <div class="card rounded-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Create New Category</h5>
        </div>
        <div class="card-body mt-4">
          <form action="{{ route('categories.store') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="categoryForm">
            @csrf

            <div class="col-12 col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{ old('name') ?? '' }}" id="name" placeholder="Enter Category Name">
                    <label for="name">Name *</label>
                </div>
                @error('name')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="image" class="form-label fw-semibold">Category Image *</label>
                <input type="file" class="form-control js-dropify rounded-0 @error('image') is-invalid @enderror" name="image" id="image">
                @error('image')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-12">
                @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
                    @php
                        $model = new App\Models\Category();
                    @endphp
                    @include('backEnd.layouts.partials.seo-meta-form')
                @endcomponent
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label d-block fw-semibold flex-shrink-0">Status</label>
                <div class="form-check form-switch custom-switch-sharp">
                    <input type="checkbox" class="form-check-input rounded-0" value="1" name="status" {{ old('status', 1) ? 'checked' : '' }}>
                    <label class="form-check-label">Active</label>
                </div>
                @error('status')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label d-block fw-semibold">Front View</label>
                <div class="form-check form-switch custom-switch-sharp">
                    <input type="checkbox" class="form-check-input rounded-0" value="1" name="front_view" {{ old('front_view') ? 'checked' : '' }}>
                    <label class="form-check-label">Visible</label>
                </div>
                @error('front_view')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
