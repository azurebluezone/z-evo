@extends('backEnd.layouts.app')
@section('title','Childcategory Edit')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Childcategory Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('childcategories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="childcategoriesForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="row">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Edit Childcategory</h5>
                <a href="{{ route('childcategories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form action="{{ route('childcategories.update') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="childcategoriesForm">
                    @csrf
                    <input type="hidden" value="{{ $edit_data->id }}" name="id">

                    <div class="col-12">
                        {{-- Grouped select kept hand-written: x-admin.select only
                             emits a flat key=>value list and cannot express the
                             disabled "-- Category --" group headers. --}}
                        <div class="form-floating form-floating-outline">
                            <select class="form-select rounded-0 @error('subcategory_id') is-invalid @enderror" id="subcategory_id" name="subcategory_id">
                                <option value="">Select Subcategory..</option>
                                @if(isset($menucategories) && count($menucategories) > 0)
                                    @foreach ($menucategories as $category)
                                        <option value="" disabled class="fw-bold bg-light">-- {{ $category->name ?? 'Category' }} --</option>
                                        @if(isset($category->subcategories) && count($category->subcategories) > 0)
                                            @foreach ($category->subcategories as $subcat)
                                                <option value="{{ $subcat->id ?? '' }}" {{ ((int) ($subcat->id ?? 0) === (int) ($edit_data->subcategory_id ?? 0)) ? 'selected' : '' }}>{{ $subcat->subcategoryName ?? 'N/A' }}</option>
                                            @endforeach
                                        @endif
                                    @endforeach
                                @else
                                    <option value="" disabled>No Subcategories Available</option>
                                @endif
                            </select>
                            <label for="subcategory_id">Sub Category *</label>
                        </div>
                        @error('subcategory_id')
                            <span class="invalid-feedback d-block fw-semibold" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <x-admin.input name="childcategoryName" label="Childcategory Name" :value="$edit_data->childcategoryName ?? ''" placeholder="Enter Childcategory Name" required />
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
                        <x-admin.switch name="status" label="Active" :checked="((int) ($edit_data->status ?? 0) === 1)" />
                    </div>

                    <div class="col-12">
                        <x-admin.button variant="success" shape="square" ripple="true" class="mt-2" type="submit">
                            <i class="ri ri-refresh-line me-1"></i> Update Childcategory
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
