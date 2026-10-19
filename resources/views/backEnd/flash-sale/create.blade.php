@extends('backEnd.layouts.app')
@section('title','Flash Sale Create')
@section('content')

<div class="row">
  <div class="col-12">
    <div class="card rounded-0 mb-4">
      <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Create Flash Sale</h5>
        <div class="d-flex align-items-center gap-2">
          <a href="{{ route('flash-sales.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
            <i class="ri-arrow-left-line me-1"></i> Manage
          </a>
          <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="flashSaleForm">
            <i class="ri-save-line me-1"></i> Submit
          </x-admin.button>
        </div>
      </div>
    </div>

    <div class="app-ecommerce">
      <form action="{{ route('flash-sales.store') }}" method="post" enctype="multipart/form-data" class="row" id="flashSaleForm">
        @csrf

        <div class="col-12">
          <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom">
              <h5 class="card-title mb-0 fw-semibold">Flash Sale Information</h5>
            </div>

            <div class="card-body mt-4">
              <div class="row g-4">

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="title" id="title" value="{{ old('title') }}" class="form-control rounded-0 @error('title') is-invalid @enderror" placeholder="Enter Title">
                            <label for="title">Title *</label>
                        </div>
                        @error('title')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="banner_link" id="banner_link" value="{{ old('banner_link') }}" class="form-control rounded-0 @error('banner_link') is-invalid @enderror" placeholder="https://...">
                            <label for="banner_link">Banner Link</label>
                        </div>
                        @error('banner_link')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="datetime-local" name="start_time" id="start_time" value="{{ old('start_time') }}" class="form-control rounded-0 @error('start_time') is-invalid @enderror">
                            <label for="start_time">Start Time *</label>
                        </div>
                        @error('start_time')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="datetime-local" name="end_time" id="end_time" value="{{ old('end_time') }}" class="form-control rounded-0 @error('end_time') is-invalid @enderror">
                            <label for="end_time">End Time *</label>
                        </div>
                        @error('end_time')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="banner_image" class="form-label fw-semibold">Banner Image</label>
                        <input type="file" name="banner_image" id="banner_image" class="form-control rounded-0 @error('banner_image') is-invalid @enderror">
                        @error('banner_image')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block fw-semibold">Status</label>
                        <div class="form-check form-switch custom-switch-sharp">
                            <input type="checkbox" class="form-check-input rounded-0" value="1" name="status" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                        @error('status')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="product_ids" class="form-label fw-semibold">Select Products *</label>
                        <select name="product_ids[]" id="product_ids" class="form-select select2-ajax" multiple style="width: 100%;" data-select2-ajax-url="{{ route('flash-sales.products.ajax') }}" data-placeholder="Search and select products..." data-minimum-input-length="1">
                            @if(is_array(old('product_ids')))
                                @foreach(old('product_ids') as $pid)
                                    <option value="{{ $pid }}" selected>{{ $pid }}</option>
                                @endforeach
                            @endif
                        </select>
                        @error('product_ids')
                            <span class="text-danger small fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

              </div>
            </div>
          </div>
        </div>

      </form>
    </div>
  </div>
</div>
@endsection
