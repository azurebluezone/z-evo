@extends('backEnd.layouts.app')
@section('title','Permissions Create')

@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Permissions Create</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="permissionsForm">
              <i class="ri ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
            <h4 class="mb-0">Permissions Create</h4>
            <a href="{{ route('permissions.index') }}" class="btn btn-primary">Manage</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('permissions.store') }}" method="POST" class="row g-3" data-parsley-validate="" enctype="multipart/form-data" id="permissionsForm">
                    @csrf
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" id="name" required="">
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <input type="submit" class="btn btn-success" value="Submit">
                    </div>
                </form>
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div>
</div>

    </div>
  </div>
</div>
@endsection



