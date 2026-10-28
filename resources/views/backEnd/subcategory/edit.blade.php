@extends('backEnd.layouts.app')
@section('title','Subcategory Edit')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Subcategory Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('subcategories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="subcategoriesForm">
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
                <h5 class="mb-0 text-uppercase fw-bold">Edit Subcategory</h5>
                <a href="{{ route('subcategories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form action="{{ route('subcategories.update') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="subcategoriesForm">
                    @csrf
                    <input type="hidden" value="{{ $edit_data->id }}" name="id">

                    <div class="col-12">
                        <x-admin.select name="category_id" label="Category"
                                        :options="($categories ?? collect())->pluck('name', 'id')->all()"
                                        :selected="$edit_data->category_id ?? null"
                                        placeholder="Select Category.." required />
                    </div>

                    <div class="col-12">
                        <x-admin.input name="subcategoryName" label="Subcategory Name" :value="$edit_data->subcategoryName ?? ''" placeholder="Enter Subcategory Name" required />
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
                            <i class="ri ri-refresh-line me-1"></i> Update Subcategory
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
