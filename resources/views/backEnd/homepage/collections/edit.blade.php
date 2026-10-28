@extends('backEnd.layouts.app')
@section('title','Homepage Collection Edit')


@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Homepage Collection Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.homepage.collections.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="homepage-collection-editForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.homepage.collections.index') }}" class="btn btn-primary rounded-pill">Manage</a>
                </div>
                <h4 class="page-title">Homepage Collection Edit</h4>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.homepage.collections.update', $collection->id) }}" method="POST" class="row" enctype="multipart/form-data">
                        @csrf

                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label for="title" class="form-label">Title *</label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $collection->title) }}" id="title">
                                @error('title')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-12 mb-3">
                            <div class="form-group">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-control @error('category_id') is-invalid @enderror" name="category_id" id="category_id">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ (string)old('category_id', $collection->category_id) === (string)$cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="product_limit" class="form-label">Product Limit *</label>
                                <input type="number" class="form-control @error('product_limit') is-invalid @enderror" name="product_limit" id="product_limit" value="{{ old('product_limit', $collection->product_limit) }}" min="1" max="100">
                                @error('product_limit')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control @error('sort_order') is-invalid @enderror" name="sort_order" id="sort_order" value="{{ old('sort_order', $collection->sort_order) }}" min="0">
                                @error('sort_order')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-12 mb-3">
                            <div class="form-group">
                                <label for="banner_image" class="form-label">Banner Image</label>
                                <input type="file" class="form-control @error('banner_image') is-invalid @enderror" name="banner_image" id="banner_image">
                                @error('banner_image')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                                @if($collection->banner_image)
                                    <div class="mt-2">
                                        <img src="{{ asset($collection->banner_image) }}" class="backend-image" alt="">
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="status" class="d-block">Status</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="status" {{ (int)old('status', $collection->status) === 1 ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <x-admin.button variant="success" type="submit">Update</x-admin.button>
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


