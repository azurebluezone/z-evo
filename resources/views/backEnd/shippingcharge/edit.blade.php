@extends('backEnd.layouts.app')
@section('title','Shipping Charge Edit')

@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Shipping Charge Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('shippingcharges.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="shippingchargesForm">
              <i class="ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <h4 class="fw-bold mb-1">Shipping Charge Edit</h4>
    <a href="{{route('shippingcharges.index')}}" class="btn btn-outline-secondary btn-sm waves-effect">
        <i class="icon-base ri ri-arrow-left-line me-2"></i>Manage
    </a>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card mb-6">
            <div class="card-body">
                <form action="{{route('shippingcharges.update')}}" method="POST" class="row g-4" enctype="multipart/form-data" id="shippingchargesForm">
                    @csrf
                    <input type="hidden" value="{{$edit_data->id}}" name="id">

                    <div class="col-sm-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name }}" id="areaName" placeholder="Area Charge">
                            <label for="areaName">Area Name</label>
                            @error('name')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control @error('amount') is-invalid @enderror" name="amount" value="{{ $edit_data->amount }}" id="chargeAmount" placeholder="Amount">
                            <label for="chargeAmount">Charge Amount</label>
                            @error('amount')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-sm-6">
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
                            <i class="icon-base ri ri-save-line me-2"></i>Update Shipping Charge
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
