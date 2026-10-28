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
                        <x-admin.input name="name" label="Brand Name" :value="old('name')" placeholder="Enter Brand Name" required />
                    </div>

                    <div class="col-12">
                        <x-admin.input name="image" label="Brand Image" type="file" class="js-dropify" />
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
                        <x-admin.switch name="status" label="Active" :checked="true" />
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
