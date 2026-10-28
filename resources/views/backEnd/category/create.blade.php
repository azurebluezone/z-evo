@extends('backEnd.layouts.app')
@section('title','Category Create')
@section('content')


<div class="row">
  <div class="col-12">
    <div class="card rounded-0 mb-4">
      <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Category Create</h5>
        <div class="d-flex align-items-center gap-2">
          <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
            <i class="ri ri-arrow-left-line me-1"></i> Manage
          </a>
          <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="categoryForm">
            <i class="ri ri-save-line me-1"></i> Submit
          </x-admin.button>
        </div>
      </div>
    </div>

    <div class="app-ecommerce">
      <div class="card rounded-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Create New Category</h5>
        </div>
        <div class="card-body mt-4">
          <form action="{{ route('categories.store') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="categoryForm">
            @csrf

            <div class="col-12 col-md-6">
                <x-admin.input name="name" label="Name" placeholder="Enter Category Name" required />
            </div>

            <div class="col-12 col-md-6">
                <x-admin.input name="image" label="Category Image" type="file" required class="js-dropify" />
            </div>

            <div class="col-12">
                @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
                    @php
                        $model = new App\Models\Category();
                    @endphp
                    @include('backEnd.layouts.partials.seo-meta-form')
                @endcomponent
            </div>

            <div class="col-12 col-md-6">
                <x-admin.switch name="status" label="Active" :checked="true" />
            </div>

            <div class="col-12 col-md-6">
                <x-admin.switch name="front_view" label="Front View (Visible)" :checked="(bool) old('front_view')" />
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
