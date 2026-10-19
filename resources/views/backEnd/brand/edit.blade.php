@extends('backEnd.layouts.app')
@section('title','Brand Edit')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Brand Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="brandsForm">
              <i class="ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="row">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Edit Brand</h5>
                <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form action="{{ route('brands.update') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="brandsForm">
                    @csrf
                    <input type="hidden" value="{{ $edit_data->id }}" name="id">

                    <div class="col-12">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name ?? '' }}" id="name" placeholder="Enter Brand Name">
                            <label for="name">Brand Name *</label>
                        </div>
                        @error('name')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="image" class="form-label fw-semibold">Brand Image</label>
                        @php
                            $brandImg = $edit_data->image ?? null;
                            $brandImgPath = $brandImg ? preg_replace('~^public/~', '', $brandImg) : null;
                            $brandImgPath = $brandImgPath ? ltrim($brandImgPath, '/') : null;
                            $fallbackImg = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                            $fallbackImg = preg_replace('~^public/~', '', $fallbackImg);
                            $fallbackImg = ltrim($fallbackImg, '/');
                        @endphp
                        <input type="file" class="form-control rounded-0 js-dropify @error('image') is-invalid @enderror" name="image" id="image" 
                            data-default-file="{{ $brandImgPath && file_exists(public_path($brandImgPath)) ? asset($brandImgPath) : asset($fallbackImg) }}">
                        @error('image')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12">
                      <div class="card rounded-0 border mb-0">
                        <div class="card-header border-bottom">
                            <h6 class="mb-0 fw-semibold">SEO Settings</h6>
                        </div>
                        <div class="card-body mt-4">
                            @php
                                $model = $edit_data;
                            @endphp
                            @include('backEnd.layouts.partials.seo-fields')
                        </div>
                      </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label d-block fw-semibold">Status</label>
                        <div class="form-check form-switch custom-switch-sharp">
                            <input type="checkbox" class="form-check-input rounded-0" value="1" name="status" {{ ((int) ($edit_data->status ?? 0) === 1) ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                        @error('status')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <x-admin.button variant="success" shape="square" ripple="true" class="mt-2" type="submit">
                            <i class="ri-refresh-line me-1"></i> Update Brand
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
