@extends('backEnd.layouts.app')
@section('title','Customer Edit')

@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Customer Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="customersForm">
              <i class="ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<!-- Customer Info Card -->
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Customer Profile</h5>
            </div>
            <div class="card-body text-center">
                @php
                    $imgPath = preg_replace('~^public/~', '', (string) ($edit_data->image ?? ''));
                    $imgPath = ltrim($imgPath, '/');
                    $fallbackImg = 'assets/images/empty.png';
                    $imgSrc = $imgPath ? asset($imgPath) : asset($fallbackImg);
                @endphp
                <img src="{{ $imgSrc }}" class="rounded-circle mb-3" width="100" height="100" style="object-fit: cover;" onerror="this.src='{{ asset($fallbackImg) }}'">
                <h5 class="mb-1">{{$edit_data->name}}</h5>
                <p class="text-muted mb-3">{{$edit_data->email}}</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="tel:{{$edit_data->phone}}" class="btn btn-success btn-sm"><i class="ri-phone-line me-1"></i>Call</a>
                    <a href="mailto:{{$edit_data->email}}" class="btn btn-info btn-sm"><i class="ri-mail-send-line me-1"></i>Email</a>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <a href="{{route('customers.index')}}" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                    <i class="ri-arrow-left-line me-1"></i> Back to List
                </a>
                <a href="{{route('customers.profile',['id'=>$edit_data->id])}}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="ri-eye-line me-1"></i> View Profile
                </a>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Customer</h5>
            </div>
            <div class="card-body">
                <form action="{{route('customers.update')}}" method="POST" class="row g-3" enctype="multipart/form-data" id="customersForm">
                    @csrf
                    <input type="hidden" value="{{$edit_data->id}}" name="hidden_id">

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name}}" id="name" placeholder="Name">
                            <label for="name">Full Name *</label>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ $edit_data->phone}}" id="phone" placeholder="Phone">
                            <label for="phone">Phone Number *</label>
                            @error('phone')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $edit_data->email}}" id="email" placeholder="Email">
                            <label for="email">Email Address *</label>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="Password">
                            <label for="password">New Password (optional)</label>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ $edit_data->address}}" id="address" placeholder="Address">
                            <label for="address">Full Address</label>
                            @error('address')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="image" class="form-label">Profile Image</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" name="image" id="image">
                        @error('image')
                            <span class="invalid-feedback d-block" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="statusSwitch" value="1" name="status" @if($edit_data->status==1 || $edit_data->status=='active') checked @endif>
                            <label class="form-check-label" for="statusSwitch">Account Active</label>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <x-admin.button variant="primary" ripple="true" type="submit">
                            <i class="ri-save-line me-2"></i>Save Changes
                        </x-admin.button>
                        <x-admin.button variant="outline-secondary" class="ms-2" type="reset">Reset</x-admin.button>
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
