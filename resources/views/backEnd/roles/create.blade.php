@extends('backEnd.layouts.app')
@section('title','Roles Create')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Roles Create</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="rolesForm">
              <i class="ri ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="container-xxl flex-grow-1 container-p-y">
   <div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="mb-0">Roles Create</h4>
            <a href="{{route('roles.index')}}" class="btn btn-primary btn-sm">Manage</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{route('roles.store')}}" method="POST" class="row" data-parsley-validate=""  enctype="multipart/form-data" id="rolesForm">
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
                    <!-- col-end -->
                    <div class="col-sm-12">
                        <div class="form-check checkall text-primary">
                            <input type="checkbox" class="form-check-input">
                            <label class="form-check-label" for="checkall">Check All</label>
                        </div>
                        <div class="row">
                        @foreach($permission as $value)
                        <div class="col-sm-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" value="{{$value->id}}" id="customCheck{{$value->id}}" name="permission[]">
                                <label class="form-check-label" for="customCheck{{$value->id}}">{{$value->name}}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    </div>
                    <div class="mt-3">
                        <input type="submit" class="btn btn-success" value="Submit">
                    </div>

                </form>

            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div> <!-- end col-->
   </div>
</div>

    </div>
  </div>
</div>
@endsection

