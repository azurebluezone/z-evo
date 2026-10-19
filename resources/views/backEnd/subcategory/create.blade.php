@extends('backEnd.layouts.app')
@section('title','Subcategory Create')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Subcategory Create</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('subcategories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="subcategoriesForm">
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
                <h5 class="mb-0 text-uppercase fw-bold">Create Subcategory</h5>
                <a href="{{ route('subcategories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form action="{{ route('subcategories.store') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="subcategoriesForm">
                    @csrf

                    <div class="col-12">
                        <div class="form-floating form-floating-outline">
                            <select class="form-select rounded-0 @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                                <option value="">Select Category..</option>
                                @if(isset($categories) && count($categories) > 0)
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id ?? '' }}" {{ (old('category_id') == ($category->id ?? '')) ? 'selected' : '' }}>{{ $category->name ?? 'N/A' }}</option>
                                    @endforeach
                                @else
                                    <option value="" disabled>No Categories Available</option>
                                @endif
                            </select>
                            <label for="category_id">Category *</label>
                        </div>
                        @error('category_id')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control rounded-0 @error('subcategoryName') is-invalid @enderror" name="subcategoryName" value="{{ old('subcategoryName') }}" id="subcategoryName" placeholder="Enter Subcategory Name">
                            <label for="subcategoryName">Subcategory Name *</label>
                        </div>
                        @error('subcategoryName')
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
                                $model = new App\Models\Subcategory();
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

                    <div class="col-12">
                        <x-admin.button variant="success" shape="square" ripple="true" class="mt-2" type="submit">
                            <i class="ri-save-line me-1"></i> Save Subcategory
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
