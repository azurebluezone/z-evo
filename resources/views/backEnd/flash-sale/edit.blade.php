@extends('backEnd.layouts.app')
@section('title','Flash Sale Edit')

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card rounded-0 mb-4">
      <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Flash Sale Edit</h5>
        <div class="d-flex align-items-center gap-2">
          <a href="{{ route('flash-sales.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
            <i class="ri ri-arrow-left-line me-1"></i> Manage
          </a>
          <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="flashSaleForm">
            <i class="ri ri-save-line me-1"></i> Update
          </x-admin.button>
        </div>
      </div>
    </div>

    <div class="app-ecommerce">
      <form action="{{ route('flash-sales.update') }}" method="post" enctype="multipart/form-data" class="row" id="flashSaleForm">
        @csrf
        <input type="hidden" name="id" value="{{ $flash_sale->id }}">

        <div class="col-12">
          <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom">
              <h5 class="card-title mb-0 fw-semibold">Flash Sale Information</h5>
            </div>

            <div class="card-body mt-4">
              <div class="row g-4">

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="title" id="title" value="{{ old('title', $flash_sale->title) }}" class="form-control @error('title') is-invalid @enderror" placeholder="Title">
                            <label for="title">Title</label>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="banner_link" id="banner_link" value="{{ old('banner_link', $flash_sale->banner_link) }}" class="form-control @error('banner_link') is-invalid @enderror" placeholder="https://...">
                            <label for="banner_link">Banner Link</label>
                            @error('banner_link')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="datetime-local" name="start_time" id="start_time" value="{{ old('start_time', $flash_sale->start_time ? $flash_sale->start_time->format('Y-m-d\TH:i') : '') }}" class="form-control @error('start_time') is-invalid @enderror">
                            <label for="start_time">Start Time</label>
                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="datetime-local" name="end_time" id="end_time" value="{{ old('end_time', $flash_sale->end_time ? $flash_sale->end_time->format('Y-m-d\TH:i') : '') }}" class="form-control @error('end_time') is-invalid @enderror">
                            <label for="end_time">End Time</label>
                            @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1 me-3">
                                <label class="form-label" for="banner_image">Banner Image</label>
                                <input type="file" name="banner_image" id="banner_image" class="form-control @error('banner_image') is-invalid @enderror">
                            </div>
                            <div>
                                @if(!empty($flash_sale->banner_image))
                                    @php
                                        $bannerPath = preg_replace('~^public/~', '', (string) ($flash_sale->banner_image ?? ''));
                                        $bannerPath = ltrim($bannerPath, '/');
                                    @endphp
                                    @if($bannerPath)
                                    <div class="mt-4">
                                        <img src="{{ asset($bannerPath) }}" alt="banner" class="rounded border" style="max-height: 50px; object-fit: cover;" onerror="this.style.display='none'">
                                    </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @error('banner_image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="statusSwitch" name="status" value="1" {{ $flash_sale->status ? 'checked' : '' }}>
                            <label class="form-check-label" for="statusSwitch">Active</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="product_ids" class="form-label fw-semibold">Select Products *</label>
                        <select name="product_ids[]" id="product_ids" class="form-select select2-ajax" multiple style="width: 100%;" data-select2-ajax-url="{{ route('flash-sales.products.ajax') }}" data-placeholder="Search and select products..." data-minimum-input-length="1">
                            @foreach($flash_sale->products as $product)
                                <option value="{{ $product->id }}" selected>{{ $product->name }}</option>
                            @endforeach
                        </select>
                        @error('product_ids')
                            <span class="text-danger small fw-semibold">{{ $message }}</span>
                        @enderror
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
