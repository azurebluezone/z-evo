@extends('backEnd.layouts.app')
@section('title','Coupon Edit')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Coupon Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('coupons.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="couponsForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="mb-0">Coupon Edit</h4>
            <a href="{{ route('coupons.index') }}" class="btn btn-outline-secondary waves-effect">
                <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('coupons.update') }}" method="post" class="row g-3" id="couponsForm">
                    @csrf
                    <input type="hidden" name="id" value="{{ $coupon->id }}">

                    <div class="col-md-4">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" value="{{ old('code', $coupon->code) }}" class="form-control @error('code') is-invalid @enderror">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror">
                            <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>Fixed</option>
                            <option value="percent" {{ old('type', $coupon->type) === 'percent' ? 'selected' : '' }}>Percent</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Value *</label>
                        <input type="number" step="0.01" name="value" value="{{ old('value', $coupon->value) }}" class="form-control @error('value') is-invalid @enderror">
                        @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Min Amount</label>
                        <input type="number" step="0.01" name="min_amount" value="{{ old('min_amount', $coupon->min_amount) }}" class="form-control @error('min_amount') is-invalid @enderror">
                        @error('min_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Max Discount</label>
                        <input type="number" step="0.01" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" class="form-control @error('max_discount') is-invalid @enderror">
                        @error('max_discount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Usage Limit</label>
                        <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="form-control @error('usage_limit') is-invalid @enderror">
                        @error('usage_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Start At</label>
                        <input type="datetime-local" name="start_at" value="{{ old('start_at', $coupon->start_at ? $coupon->start_at->format('Y-m-d\TH:i') : '') }}" class="form-control @error('start_at') is-invalid @enderror">
                        @error('start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">End At</label>
                        <input type="datetime-local" name="end_at" value="{{ old('end_at', $coupon->end_at ? $coupon->end_at->format('Y-m-d\TH:i') : '') }}" class="form-control @error('end_at') is-invalid @enderror">
                        @error('end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Products (Optional)</label>
                        <select name="product_ids[]" id="product_ids" class="form-select select2-ajax" multiple style="width: 100%;">
                            @foreach($coupon->products ?? [] as $product)
                                <option value="{{ $product->id }}" selected>{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text mt-2">Search and select products. Leave empty to apply coupon to all products.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label d-block">Status</label>
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" name="status" value="1" {{ $coupon->status ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <x-admin.button variant="success" ripple="true" type="submit">
                            <i class="ri ri-save-line me-1"></i> Update Coupon
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
