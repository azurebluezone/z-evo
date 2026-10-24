@extends('backEnd.layouts.app')
@section('title','Brand Create')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Brand Create</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="brandsForm">
              <i class="ri ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="row">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Create Brand</h5>
            </div>

            <div class="card-body mt-4">
                <form action="{{ route('brands.store') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="brandsForm">
                    @csrf

                    <div class="col-12">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" id="name" placeholder="Enter Brand Name">
                            <label for="name">Brand Name *</label>
                        </div>
                        @error('name')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="image" class="form-label fw-semibold">Brand Image</label>
                        <input type="file" class="form-control rounded-0 js-dropify @error('image') is-invalid @enderror" name="image" id="image">
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
                                $model = new App\Models\Brand();
                            @endphp
                            @include('backEnd.layouts.partials.seo-fields')
                        </div>
                      </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label d-block fw-semibold">Status</label>
                        <div class="form-check form-switch custom-switch-sharp">
                            <input type="checkbox" class="form-check-input rounded-0" value="1" name="status" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                        @error('status')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
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
