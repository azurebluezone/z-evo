@extends('backEnd.layouts.app')
@section('title','Category Edit')
@section('content')


<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Category Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="categoriesForm">
              <i class="ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Edit Category</h5>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('categories.index') }}" class="btn btn-primary btn-square waves-effect">
                <i class="ri-list-check me-1"></i> Manage Categories
            </a>
        </div>
    </div>

    <div class="card-body mt-4">
        <form action="{{ route('categories.update') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="categoriesForm">
            @csrf
            <input type="hidden" value="{{ $edit_data->id ?? '' }}" name="id">

            <div class="col-12 col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{ old('name', $edit_data->name ?? '') }}" id="name" placeholder="Enter Category Name">
                    <label for="name">Name *</label>
                </div>
                @error('name')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-12 col-md-6">
                <label for="image" class="form-label fw-semibold">Category Image</label>
                @php
                    $catImg = $edit_data->image ?? null;
                    $catImgPath = $catImg ? preg_replace('~^public/~', '', $catImg) : null;
                    $catImgPath = $catImgPath ? ltrim($catImgPath, '/') : null;
                    $fallbackImg = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                    $fallbackImg = preg_replace('~^public/~', '', $fallbackImg);
                    $fallbackImg = ltrim($fallbackImg, '/');
                @endphp
                <input type="file" class="form-control js-dropify rounded-0 @error('image') is-invalid @enderror" name="image" id="image" 
                    data-default-file="{{ $catImgPath && file_exists(public_path($catImgPath)) ? asset($catImgPath) : asset($fallbackImg) }}">
                @error('image')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-12">
                @include('frontend.layouts.partials.seo-fields', ['edit_data' => $edit_data])
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label d-block fw-semibold flex-shrink-0">Status</label>
                <div class="form-check form-switch custom-switch-sharp">
                    <input type="checkbox" class="form-check-input rounded-0" value="1" name="status" {{ (old('status', $edit_data->status ?? 0) == 1) ? 'checked' : '' }}>
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
                    <input type="checkbox" class="form-check-input rounded-0" value="1" name="front_view" {{ (old('front_view', $edit_data->front_view ?? 0) == 1) ? 'checked' : '' }}>
                    <label class="form-check-label">Visible</label>
                </div>
                @error('front_view')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-12 text-end border-top pt-3">
                <x-admin.button variant="success" shape="square" class="px-5" type="submit">
                    <i class="ri-refresh-line me-1"></i> Update Category
                </x-admin.button>
            </div>
        </form>
    </div>
</div>

    </div>
  </div>
</div>
@endsection
