@extends('backEnd.layouts.app') 
@section('title','Payment Gateway')
@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <h4 class="mb-0">Payment Gateway</h4>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <h5 class="mb-3">Bkash</h5>
        <form action="{{route('paymentgeteway.update')}}" method="POST" class="row g-3" data-parsley-validate="" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="id" value="{{$bkash->id}}">

          <div class="col-12 col-md-4">
            <label for="username" class="form-label">User Name *</label>
            <input type="text" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ $bkash->username}}" id="username" />
            @error('username')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label for="app_key" class="form-label">App Key *</label>
            <input type="text" class="form-control @error('app_key') is-invalid @enderror" name="app_key" value="{{ $bkash->app_key }}" id="app_key" />
            @error('app_key')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label for="app_secret" class="form-label">App Secret *</label>
            <input type="text" class="form-control @error('app_secret') is-invalid @enderror" name="app_secret" value="{{ $bkash->app_secret }}" id="app_secret" />
            @error('app_secret')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label for="base_url" class="form-label">Base Url *</label>
            <input type="text" class="form-control @error('base_url') is-invalid @enderror" name="base_url" value="{{ $bkash->base_url }}" id="base_url" />
            @error('base_url')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label for="password" class="form-label">Password *</label>
            <input type="text" class="form-control @error('password') is-invalid @enderror" name="password" value="{{ $bkash->password }}" id="password" />
            @error('password')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label d-block">Status</label>
            <label class="switch">
              <input type="checkbox" class="switch-input" value="1" @if($bkash->status==1)checked @endif name="status" />
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

    <div class="card">
      <div class="card-body">
        <h5 class="mb-3">Shurjopay</h5>
        <form action="{{route('paymentgeteway.update')}}" method="POST" class="row g-3" data-parsley-validate="" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="id" value="{{ $shurjopay->id}}">

          <div class="col-12 col-md-4">
            <label for="username" class="form-label">User Name *</label>
            <input type="text" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ $shurjopay->username}}" id="username" />
            @error('username')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label for="prefix" class="form-label">Prefix *</label>
            <input type="text" class="form-control @error('prefix') is-invalid @enderror" name="prefix" value="{{ $shurjopay->prefix}}" id="prefix" />
            @error('prefix')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label for="success_url" class="form-label">Success Url *</label>
            <input type="text" class="form-control @error('success_url') is-invalid @enderror" name="success_url" value="{{ $shurjopay->success_url}}" id="success_url" />
            @error('success_url')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label for="return_url" class="form-label">Return Url *</label>
            <input type="text" class="form-control @error('return_url') is-invalid @enderror" name="return_url" value="{{ $shurjopay->return_url}}" id="return_url" />
            @error('return_url')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label for="base_url" class="form-label">Base Url *</label>
            <input type="text" class="form-control @error('base_url') is-invalid @enderror" name="base_url" value="{{ $shurjopay->base_url}}" id="base_url" />
            @error('base_url')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label for="password" class="form-label">Password *</label>
            <input type="text" class="form-control @error('password') is-invalid @enderror" name="password" value="{{ $shurjopay->password}}" id="password" />
            @error('password')
              <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label d-block">Status</label>
            <label class="switch">
              <input type="checkbox" class="switch-input" value="1" @if($shurjopay->status==1)checked @endif name="status" />
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
@endsection
