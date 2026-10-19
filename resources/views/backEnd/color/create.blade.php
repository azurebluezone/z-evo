@extends('backEnd.layouts.app')
@section('title','Color Create')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Color Create</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('colors.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="colorsForm">
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
                <h5 class="mb-0 text-uppercase fw-bold">Create Color</h5>
                <a href="{{ route('colors.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form action="{{ route('colors.store') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="colorsForm">
                    @csrf

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control rounded-0 @error('colorName') is-invalid @enderror" name="colorName" value="{{ old('colorName') }}" id="colorName" placeholder="Enter Color Name">
                            <label for="colorName">Color Name *</label>
                        </div>
                        @error('colorName')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="color" class="form-label fw-semibold">Color *</label>
                        <input type="color" class="form-control form-control-color rounded-0 w-100 @error('color') is-invalid @enderror" name="color" value="{{ old('color') ?? '#000000' }}" id="color" title="Choose your color">
                        @error('color')
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
                                $model = new App\Models\Color();
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

                    <div class="col-12 mt-4">
                        <x-admin.button variant="success" shape="square" ripple="true" type="submit">
                            <i class="ri-save-line me-1"></i> Save Color
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
