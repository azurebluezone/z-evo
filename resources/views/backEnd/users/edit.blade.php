@extends('backEnd.layouts.app')
@section('title','Users Edit')

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card rounded-0 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Users Edit</h5>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                        <i class="ri ri-arrow-left-line me-1"></i> Manage
                    </a>
                    <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="usersForm">
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
                            <h5 class="mb-0 text-uppercase fw-bold">Edit User</h5>
                        </div>
                        <div class="card-body mt-4">
                            <form action="{{ route('users.update') }}" method="POST" class="row g-4" data-parsley-validate="" enctype="multipart/form-data" id="usersForm">
                                @csrf
                                <input type="hidden" value="{{$edit_data->id}}" name="hidden_id">
                                <div class="col-sm-6">
                                    <div class="form-group mb-3">
                                        <label for="name" class="form-label">Name *</label>
                                        <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name}}" id="name" required="">
                                        @error('name')
                                        <span class="invalid-feedback d-block fw-semibold" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- col-end -->
                                <div class="col-sm-6">
                                    <div class="form-group mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="text" class="form-control rounded-0 @error('email') is-invalid @enderror" name="email" value="{{ $edit_data->email}}" id="email" required="">
                                        @error('email')
                                        <span class="invalid-feedback d-block fw-semibold" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- col-end -->
                                <div class="col-sm-6">
                                    <div class="form-group mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control rounded-0 @error('password') is-invalid @enderror" name="password" value="" id="password">
                                        @error('password')
                                        <span class="invalid-feedback d-block fw-semibold" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- col end -->
                                <div class="col-sm-6">
                                    <div class="form-group mb-3">
                                        <label for="confirm-password" class="form-label">Confirm Password *</label>
                                        <input type="password" class="form-control rounded-0 @error('confirm-password') is-invalid @enderror" name="confirm-password" value="" id="confirm-password">
                                        @error('confirm-password')
                                        <span class="invalid-feedback d-block fw-semibold" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- col end -->
                                <div class="col-sm-6">
                                    <div class="form-group mb-3">
                                        <label for="roles" class="form-label">Role *</label>
                                        <select class="form-control select2-multiple" name="roles[]" data-toggle="select2" multiple="multiple" data-placeholder="Choose ...">
                                            <optgroup label="Select Role">
                                                @foreach($roles as $role)
                                                <option value="{{$role->name}}" @foreach($edit_data->roles as $srole) {{$srole->id==$role->id?'selected':''}} @endforeach>{{$role->name}}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        @error('roles')
                                        <span class="invalid-feedback d-block fw-semibold" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- col end -->
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="image" class="form-label">Image *</label>
                                        <input type="file" class="form-control rounded-0 @error('image') is-invalid @enderror" name="image" value="{{ $edit_data->image }}" id="image">
                                        <img src="{{asset($edit_data->image ?? 'uploads/users/default-avatar.png')}}" alt="" class="mt-2" style="max-width:80px;border-radius:50%;">
                                        @error('image')
                                        <span class="invalid-feedback d-block fw-semibold" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- col end -->
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="status" class="d-block fw-semibold">Status</label>
                                        <div class="form-check form-switch custom-switch-sharp">
                                            <input type="checkbox" class="form-check-input rounded-0" value="1" name="status" {{ ($edit_data->status == 1) ? 'checked' : '' }}>
                                            <label class="form-check-label">Active</label>
                                        </div>
                                        @error('status')
                                        <span class="invalid-feedback d-block fw-semibold" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                                <!-- col end -->
                                <div class="col-12 mt-4">
                                    <x-admin.button variant="success" shape="square" ripple="true" type="submit">
                                        <i class="ri ri-save-line me-1"></i> Update
                                    </x-admin.button>
                                </div>

                            </form>

                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div> <!-- end col-->
            </div> <!-- end row-->
        </div> <!-- end app-ecommerce-->
    </div> <!-- end col-12-->
</div> <!-- end row-->
@endsection