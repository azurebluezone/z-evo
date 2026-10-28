@extends('backEnd.layouts.app')
@section('title','Childcategory Create')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Childcategory Create</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('childcategories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="childcategoriesForm">
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
                <h5 class="mb-0 text-uppercase fw-bold">Create Childcategory</h5>
                <a href="{{ route('childcategories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form action="{{ route('childcategories.store') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="childcategoriesForm">
                    @csrf

                    <div class="col-12">
                        {{-- The subcategory dropdown keeps its hand-written markup:
                             it is a GROUPED select (a disabled "-- Category --"
                             header before each category's subcategories), and
                             x-admin.select only emits a flat key=>value list, so
                             it cannot express this without losing the grouping. --}}
                        <div class="form-floating form-floating-outline">
                            <select class="form-select rounded-0 @error('subcategory_id') is-invalid @enderror" id="subcategory_id" name="subcategory_id">
                                <option value="">Select Subcategory..</option>
                                @if(isset($menucategories) && count($menucategories) > 0)
                                    @foreach ($menucategories as $category)
                                        <option value="" disabled class="fw-bold bg-light">-- {{ $category->name ?? 'Category' }} --</option>
                                        @if(isset($category->subcategories) && count($category->subcategories) > 0)
                                            @foreach ($category->subcategories as $subcat)
                                                <option value="{{ $subcat->id ?? '' }}" {{ (old('subcategory_id') == ($subcat->id ?? '')) ? 'selected' : '' }}>{{ $subcat->subcategoryName ?? 'N/A' }}</option>
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
                        <x-admin.input name="childcategoryName" label="Childcategory Name" :value="old('childcategoryName')" placeholder="Enter Childcategory Name" required />
                    </div>

                    <div class="col-12">
                      <div class="card rounded-0 border mb-0">
                        <div class="card-header border-bottom">
                            <h6 class="mb-0 fw-semibold">SEO Settings</h6>
                        </div>
                        <div class="card-body mt-4">
                            @php
                                $model = new App\Models\Childcategory();
                            @endphp
                            @include('backEnd.layouts.partials.seo-fields')
                        </div>
                      </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <x-admin.switch name="status" label="Active" :checked="true" />
                    </div>

                    <div class="col-12">
                        <x-admin.button variant="success" shape="square" ripple="true" class="mt-2" type="submit">
                            <i class="ri ri-save-line me-1"></i> Save Childcategory
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
