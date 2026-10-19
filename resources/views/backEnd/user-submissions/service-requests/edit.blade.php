@extends('backEnd.layouts.app')
@section('title','Edit Service Request')

@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Edit Service Request</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.user-submissions.service-requests.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="edit-service-requestForm">
              <i class="ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="mb-0">Edit Service Request</h4>
                <a href="{{ route('admin.user-submissions.service-requests.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.user-submissions.service-requests.update', $requestItem->id) }}" method="POST">
                        @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $requestItem->first_name) }}" class="form-control @error('first_name') is-invalid @enderror">
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $requestItem->last_name) }}" class="form-control @error('last_name') is-invalid @enderror">
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', $requestItem->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Service Type</label>
                            <input type="text" name="service_type" value="{{ old('service_type', $requestItem->service_type) }}" class="form-control @error('service_type') is-invalid @enderror">
                            @error('service_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="pending" {{ old('status', $requestItem->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ old('status', $requestItem->status) === 'approved' ? 'selected' : '' }}>Approved</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" rows="5" class="form-control @error('message') is-invalid @enderror">{{ old('message', $requestItem->message) }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                        <div class="mt-4 d-flex gap-2">
                            <x-admin.button variant="primary" type="submit">Update</x-admin.button>
                            <a href="{{ route('admin.user-submissions.service-requests.show', $requestItem->id) }}" class="btn btn-outline-secondary">View</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
  </div>
</div>
@endsection
