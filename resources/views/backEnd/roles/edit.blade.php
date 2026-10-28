@extends('backEnd.layouts.app')
@section('title','Roles Edit')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Roles Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="rolesForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="container-fluid">
    
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('users.index')}}" class="btn btn-primary rounded-pill">Manage</a>
                </div>
                <h4 class="page-title">Roles Edit</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form action="{{route('roles.update')}}" method="POST" class=row data-parsley-validate=""  enctype="multipart/form-data" id="rolesForm">
                    @csrf
                    <input type="hidden" name="hidden_id" value="{{$edit_data->id}}">
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name}}" id="name" required="">
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
                        <div class="col-sm-4 my-1">
                            <div class="form-check">
                                <input type="checkbox" name="permission[]" class="form-check-input" value="{{$value->id}}" id="customCheck{{$value->id}}"  @foreach($edit_data->permissions as $permission) {{$permission->id==$value->id?'checked':''}} @endforeach >
                                <label class="form-check-label" for="customCheck{{$value->id}}">{{$value->name}}</label>
                            </div>
                        </div>
                        @endforeach
                        </div>
                      </div>
                    <div class="mt-3">
                        <x-admin.button variant="success" type="submit">Submit</x-admin.button>
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
