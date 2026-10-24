@extends('backEnd.layouts.app')
@section('title', 'Review Create')
@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Review Create</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('reviews.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="reviewForm">
              <i class="ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

      <div class="app-ecommerce">
        <form action="{{ route('reviews.store') }}" method="POST" class="row" data-parsley-validate="" enctype="multipart/form-data" id="reviewForm">
          @csrf

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
                      @foreach ($products as $value)
                        <option value="{{ $value->id }}" @if(old('product_id') == $value->id) selected @endif>{{ $value->name }}</option>
                      @endforeach
                    </select>
                    @error('product_id')
                      <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="col-12 col-sm-6">
                    <label for="customer_id" class="form-label fw-semibold">Customer (optional)</label>
                    <select
                      class="form-select rounded-0 select2-multiple @error('customer_id') is-invalid @enderror"
                      name="customer_id"
                      id="customer_id"
                      data-toggle="select2"
                      data-placeholder="Choose ...">
                      <option value="">Walk-in buyer (no account)</option>
                      @foreach ($customers as $value)
                        <option value="{{ $value->id }}" @if(old('customer_id') == $value->id) selected @endif>{{ $value->name }}</option>
                      @endforeach
                    </select>
                    <div class="form-text">A linked customer's name and email win over the two fields below.</div>
                    @error('customer_id')
                      <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="col-12 col-sm-6">
                    <div class="form-floating form-floating-outline">
                      <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" id="name" placeholder="Buyer Name" />
                      <label for="name">Buyer Name</label>
                    </div>
                    @error('name')
                      <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="col-12 col-sm-6">
                    <div class="form-floating form-floating-outline">
                      <input type="email" class="form-control rounded-0 @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" id="email" placeholder="Buyer Email" />
                      <label for="email">Buyer Email</label>
                    </div>
                    @error('email')
                      <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="col-12 col-sm-6">
                    <label for="ratting" class="form-label fw-semibold">Ratting *</label>
                    <select
                      class="form-select rounded-0 @error('ratting') is-invalid @enderror"
                      name="ratting"
                      id="ratting">
                      <option value="">Select..</option>
                      @for ($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" @if((string) old('ratting') === (string) $i) selected @endif>{{ $i }}</option>
                      @endfor
                    </select>
                    @error('ratting')
                      <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                      </span>
                    @enderror
                  </div>

                  <div class="col-12 col-sm-6 d-flex align-items-end">
                    <div class="w-100">
                      <label for="status" class="form-label fw-semibold d-block mb-2">Status</label>
                      <label class="switch">
                        <input type="checkbox" value="1" name="status" checked>
                        <span class="slider round"></span>
                      </label>
                      @error('status')
                        <span class="invalid-feedback d-block fw-semibold" role="alert">
                          <strong>{{ $message }}</strong>
                        </span>
                      @enderror
                    </div>
                  </div>

                  <div class="col-12">
                    <div class="card rounded-0 border mb-0">
                      <div class="card-header border-bottom">
                        <div class="fw-semibold">Review *</div>
                      </div>
                      <div class="card-body p-0">
                        <textarea id="review" name="review" rows="1" class="form-control d-none @error('review') is-invalid @enderror">{{ old('review') }}</textarea>
                        <div
                          id="review-editor"
                          class="js-quill-editor border-0 rounded-0"
                          data-quill-theme="snow"
                          data-quill-toolbar-preset="mini"
                          data-quill-target="#review"
                          data-quill-placeholder="Write review..."
                          data-quill-initial-html="{{ e((string) old('review')) }}"
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



