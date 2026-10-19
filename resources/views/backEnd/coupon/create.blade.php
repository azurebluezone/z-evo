@extends('backEnd.layouts.app')
@section('title','Coupon Create')
@section('content')


<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Coupon Create</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('coupons.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="couponsForm">
              <i class="ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="card rounded-0">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Create New Coupon</h5>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('coupons.index') }}" class="btn btn-primary btn-square waves-effect">
                <i class="ri-arrow-left-line"></i> Back to List
            </a>
        </div>
    </div>
    <div class="card-body mt-4">
        <form action="{{ route('coupons.store') }}" method="post" class="row g-4" id="couponsForm">
            @csrf

            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('code') is-invalid @enderror" name="code" value="{{ old('code') }}" id="code" placeholder="Enter Code">
                    <label for="code">Code *</label>
                </div>
                @error('code')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <select class="form-select rounded-0 @error('type') is-invalid @enderror" name="type" id="type">
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Fixed</option>
                        <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>Percent</option>
                    </select>
                    <label for="type">Type *</label>
                </div>
                @error('type')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="number" step="0.01" class="form-control rounded-0 @error('value') is-invalid @enderror" name="value" value="{{ old('value') }}" id="value" placeholder="Enter Value">
                    <label for="value">Value *</label>
                </div>
                @error('value')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="number" step="0.01" class="form-control rounded-0 @error('min_amount') is-invalid @enderror" name="min_amount" value="{{ old('min_amount') }}" id="min_amount" placeholder="Enter Min Amount">
                    <label for="min_amount">Min Amount</label>
                </div>
                @error('min_amount')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="number" step="0.01" class="form-control rounded-0 @error('max_discount') is-invalid @enderror" name="max_discount" value="{{ old('max_discount') }}" id="max_discount" placeholder="Enter Max Discount">
                    <label for="max_discount">Max Discount</label>
                </div>
                @error('max_discount')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input type="number" class="form-control rounded-0 @error('usage_limit') is-invalid @enderror" name="usage_limit" value="{{ old('usage_limit') }}" id="usage_limit" placeholder="Enter Usage Limit">
                    <label for="usage_limit">Usage Limit</label>
                </div>
                @error('usage_limit')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="datetime-local" class="form-control rounded-0 @error('start_at') is-invalid @enderror" name="start_at" value="{{ old('start_at') }}" id="start_at">
                    <label for="start_at">Start At</label>
                </div>
                @error('start_at')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                    <input type="datetime-local" class="form-control rounded-0 @error('end_at') is-invalid @enderror" name="end_at" value="{{ old('end_at') }}" id="end_at">
                    <label for="end_at">End At</label>
                </div>
                @error('end_at')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-12">
                <label for="product_ids" class="form-label fw-semibold">Products (Optional)</label>
                <select name="product_ids[]" id="product_ids" class="form-select select2-ajax" multiple style="width: 100%;">
                    @if(is_array(old('product_ids')))
                        @foreach(old('product_ids') as $pid)
                            <option value="{{ $pid }}" selected>{{ $pid }}</option>
                        @endforeach
                    @endif
                </select>
                @error('product_ids')
                    <span class="text-danger small fw-semibold">{{ $message }}</span>
                @enderror
                <div class="form-text mt-2">Search and select products. Leave empty to apply coupon to all products.</div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label d-block fw-semibold">Status</label>
                <div class="form-check form-switch custom-switch-sharp">
                    <input type="checkbox" class="form-check-input rounded-0" value="1" name="status" checked>
                    <label class="form-check-label">Active</label>
                </div>
                @error('status')
                    <span class="invalid-feedback d-block fw-semibold" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-12 text-end border-top pt-3">
                <x-admin.button variant="success" shape="square" class="px-5" type="submit">
                    <i class="ri-save-line me-1"></i> Save Coupon
                </x-admin.button>
            </div>
        </form>
    </div>
</div>

    </div>
  </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #d9dee3;
        border-radius: 0.375rem;
        min-height: 38px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #696cff;
        border: none;
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: white;
        margin-right: 5px;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #696cff;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#product_ids').select2({
            ajax: {
                url: '{{ route("flash-sales.products.ajax") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.data,
                        pagination: {
                            more: data.current_page < data.last_page
                        }
                    };
                },
                cache: true
            },
            placeholder: 'Search for products...',
            minimumInputLength: 1,
            allowClear: true,
            closeOnSelect: false
        });
    });
</script>
@endpush
