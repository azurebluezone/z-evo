@extends('backEnd.layouts.app')
@section('title','Review Edit')
@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Review Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('reviews.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="reviewForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

      <div class="app-ecommerce">
        <form action="{{ route('reviews.update') }}" method="POST" class="row" data-parsley-validate="" enctype="multipart/form-data" id="reviewForm">
          @csrf
          <input type="hidden" value="{{ $edit_data->id }}" name="hidden_id">

          <div class="col-12">
            <div class="card rounded-0 mb-4">
              <div class="card-header border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Review Information</h5>
              </div>

              <div class="card-body mt-4">
                <div class="row g-4">
                  <div class="col-12 col-sm-6">
                    <label for="product_id" class="form-label fw-semibold">Product *</label>
                    <select
                      class="form-select rounded-0 select2-multiple @error('product_id') is-invalid @enderror"
                      name="product_id"
                      id="product_id"
                      data-toggle="select2"
                      data-placeholder="Choose ..."
                      required>
                      <option value="">Select..</option>
                      @foreach($products as $value)
                        <option value="{{ $value->id }}" @if((string) $edit_data->product_id === (string) $value->id) selected @endif>{{ $value->name }}</option>
                      @endforeach
                    </select>
                    @error('product_id')
                      <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="col-12 col-sm-6">
                    <div class="form-floating form-floating-outline">
                      <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{ old('name', $edit_data->name) }}" id="name" placeholder="Customer Name" required="" />
                      <label for="name">Customer Name *</label>
                    </div>
                    @error('name')
                      <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="col-12 col-sm-6">
                    <div class="form-floating form-floating-outline">
                      <input type="email" class="form-control rounded-0 @error('email') is-invalid @enderror" name="email" value="{{ old('email', $edit_data->email) }}" id="email" placeholder="Customer Email" required="" />
                      <label for="email">Customer Email *</label>
                    </div>
                    @error('email')
                      <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="col-12 col-sm-6 d-flex align-items-end">
                    <div class="w-100">
                      <label for="status" class="form-label fw-semibold d-block mb-2">Status</label>
                      <label class="switch">
                        {{-- status is the string 'active'/'pending', never 1: the old `== 1` check
                             left an active review looking switched off, so any
                             save sent it back to pending. --}}
                        <input type="checkbox" value="1" name="status" @if($edit_data->status === 'active')checked @endif>
                        <span class="slider round"></span>
                      </label>
                      @error('status')
                        <span class="invalid-feedback d-block fw-semibold" role="alert">
                          <strong>{{ $message }}</strong>
                        </span>
                      @enderror
                    </div>
                  </div>

                  <div class="col-12 col-sm-6">
                    <div class="form-floating form-floating-outline">
                      <input type="number" class="form-control rounded-0 @error('ratting') is-invalid @enderror" name="ratting" value="{{ old('ratting', $edit_data->ratting) }}" id="ratting" placeholder="Rating" required="" />
                      <label for="ratting">Ratting *</label>
                    </div>
                    @error('ratting')
                      <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="col-12">
                    <div class="card rounded-0 border mb-0">
                      <div class="card-header border-bottom">
                        <div class="fw-semibold">Review *</div>
                      </div>
                      <div class="card-body p-0">
                        <textarea id="review" name="review" rows="6" class="form-control d-none @error('review') is-invalid @enderror">{{ old('review', $edit_data->review) }}</textarea>
                        <div
                          id="review-editor"
                          class="js-quill-editor border-0 rounded-0"
                          data-quill-theme="snow"
                          data-quill-toolbar-preset="mini"
                          data-quill-target="#review"
                          data-quill-placeholder="Write review..."
                          data-quill-initial-html="{{ e((string) old('review', $edit_data->review)) }}"
                          style="min-height: 220px;"></div>
                        @error('review')
                          <div class="text-danger fw-semibold mt-1 px-3 pb-2">{{ $message }}</div>
                        @enderror
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>
@endsection



