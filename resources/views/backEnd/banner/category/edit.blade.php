@extends('backEnd.layouts.app')
@section('title','Banner Category Edit')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Banner Category Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('banner_category.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="banner-categoryForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
            <h4 class="mb-0">Banner Category Edit</h4>
            <a href="{{ route('banner_category.index') }}" class="btn btn-primary">Manage</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('banner_category.update') }}" method="POST" class="row g-3" data-parsley-validate="" enctype="multipart/form-data" id="banner-categoryForm">
                    @csrf
                    <input type="hidden" value="{{ $edit_data->id }}" name="id">

                    <div class="col-12">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name }}" id="name">
                        @error('name')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label d-block">Status</label>
                        <label class="switch">
                            <input type="checkbox" value="1" name="status" {{ ((int) $edit_data->status === 1) ? 'checked' : '' }}>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                        </label>
                        @error('status')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <x-admin.button variant="success" type="submit">Submit</x-admin.button>
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
