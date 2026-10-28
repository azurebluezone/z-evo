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
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="subcategoriesForm">
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
                <h5 class="mb-0 text-uppercase fw-bold">Create Subcategory</h5>
                <a href="{{ route('subcategories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form action="{{ route('subcategories.store') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="subcategoriesForm">
                    @csrf

                    <div class="col-12">
                        <x-admin.select name="category_id" label="Category"
                                        :options="($categories ?? collect())->pluck('name', 'id')->all()"
                                        placeholder="Select Category.." required />
                    </div>

                    <div class="col-12">
                        <x-admin.input name="subcategoryName" label="Subcategory Name" :value="old('subcategoryName')" placeholder="Enter Subcategory Name" required />
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
                        <x-admin.switch name="status" label="Active" :checked="true" />
                    </div>

                    <div class="col-12">
                        <x-admin.button variant="success" shape="square" ripple="true" class="mt-2" type="submit">
                            <i class="ri ri-save-line me-1"></i> Save Subcategory
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
