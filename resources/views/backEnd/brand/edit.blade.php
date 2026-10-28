@extends('backEnd.layouts.app')
@section('title','Brand Edit')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Brand Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="brandsForm">
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
                <h5 class="mb-0 text-uppercase fw-bold">Edit Brand</h5>
                <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form action="{{ route('brands.update') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="brandsForm">
                    @csrf
                    <input type="hidden" value="{{ $edit_data->id }}" name="id">

                    <div class="col-12">
                        <x-admin.input name="name" label="Brand Name" :value="$edit_data->name ?? ''" placeholder="Enter Brand Name" required />
                    </div>

                    <div class="col-12">
                        @php
                            $brandImg = $edit_data->image ?? null;
                            $brandImgPath = $brandImg ? preg_replace('~^public/~', '', $brandImg) : null;
                            $brandImgPath = $brandImgPath ? ltrim($brandImgPath, '/') : null;
                            $fallbackImg = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                            $fallbackImg = preg_replace('~^public/~', '', $fallbackImg);
                            $fallbackImg = ltrim($fallbackImg, '/');
                        @endphp
                        <x-admin.input name="image" label="Brand Image" type="file" class="js-dropify"
                                       data-default-file="{{ $brandImgPath && file_exists(public_path($brandImgPath)) ? asset($brandImgPath) : asset($fallbackImg) }}" />
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
                            <i class="ri ri-refresh-line me-1"></i> Update Brand
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
