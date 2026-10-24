@extends('backEnd.layouts.app')
@section('title','Order Status Edit')

@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Order Status Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('orderstatus.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="orderstatusForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <h4 class="fw-bold mb-1">Order Status Edit</h4>
    <a href="{{route('orderstatus.index')}}" class="btn btn-outline-secondary btn-sm waves-effect">
        <i class="icon-base ri ri-arrow-left-line me-2"></i>Manage
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card mb-6">
            <div class="card-body">
                <form action="{{route('orderstatus.update')}}" method="POST" class="row g-4" enctype="multipart/form-data" id="orderstatusForm">
                    @csrf
                    <input type="hidden" value="{{$edit_data->id}}" name="id">

                    <div class="col-12">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name }}" id="statusName" placeholder="Status Name">
                            <label for="statusName">Status Name</label>
                            @error('name')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label d-block">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="statusSwitch" value="1" name="status" @if($edit_data->status==1) checked @endif>
                            <label class="form-check-label" for="statusSwitch">Active</label>
                        </div>
                        @error('status')
                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12 text-end">
                        <x-admin.button variant="primary" ripple="true" class="waves-light" type="submit">
                            <i class="icon-base ri ri-save-line me-2"></i>Update Status
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
