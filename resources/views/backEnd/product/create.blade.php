@extends('backEnd.layouts.app')
<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-page-product-create.css') }}">

@section('title','Product Create')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Create Product</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                <i class="ri ri-arrow-left-line me-1"></i> All Products
            </a>
          </div>
        </div>
      </div>


      <div class="app-ecommerce">
        <form action="{{route('products.store')}}" method="POST" class="row" enctype="multipart/form-data" id="productForm">
        @csrf

        <div class="col-12">
          <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom">
              <h5 class="card-title mb-0 fw-semibold">Product Information</h5>
            </div>
            <div class="card-body">

              <div class="row g-4">
                <div class="col-12 col-sm-6">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" id="name" placeholder="Enter Product Name" />
                    <label for="name">Product Name *</label>
                  </div>
                  @error('name')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-sm-6">
                  <div class="form-floating form-floating-outline">
                    <select class="form-select rounded-0 @error('category_id') is-invalid @enderror" name="category_id" id="category_id">
                      <option value="">Select Category..</option>
                      @if(isset($categories) && count($categories) > 0)
                          @foreach($categories as $category)
                          <option value="{{$category->id ?? ''}}" @if(old('category_id')==($category->id ?? '')) selected @endif>{{$category->name ?? 'N/A'}}</option>
                          @endforeach
                      @else
                          <option value="" disabled>No Data Available</option>
                      @endif
                    </select>
                    <label for="category_id">Categories *</label>
                  </div>
                  @error('category_id')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-sm-6">
                  <div class="form-floating form-floating-outline">
                    <select class="form-select rounded-0 @error('subcategory_id') is-invalid @enderror" id="subcategory_id" name="subcategory_id" data-selected="{{ old('subcategory_id') }}">
                      <option value="">Select Subcategory..</option>
                    </select>
                    <label for="subcategory_id">SubCategories (Optional)</label>
                  </div>
                  @error('subcategory_id')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-sm-6">
                  <div class="form-floating form-floating-outline">
                    <select class="form-select rounded-0 @error('childcategory_id') is-invalid @enderror" id="childcategory_id" name="childcategory_id" data-selected="{{ old('childcategory_id') }}">
                      <option value="">Select Childcategory..</option>
                    </select>
                    <label for="childcategory_id">Child Categories (Optional)</label>
                  </div>
                  @error('childcategory_id')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-sm-6">
                  <label for="image" class="form-label fw-semibold">Main Image *</label>
                  <input type="file" name="image[]" class="form-control rounded-0" />
                  {{-- Optional title for the main image (pairs with image[0] as title[0]);
                       gallery images fall back to their filename in the controller. --}}
                  <input type="text" name="title[]" class="form-control rounded-0 mt-2" placeholder="Image Title (optional)" />
                  @error('image')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-sm-6">
                  <label for="gallery_images" class="form-label fw-semibold">Gallery Images</label>
                  <input type="file" name="image[]" class="form-control rounded-0" multiple />
                </div>

                <div class="col-12 col-sm-6">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('pro_unit') is-invalid @enderror" name="pro_unit" value="{{ old('pro_unit') }}" id="pro_unit" placeholder="e.g. Kg, Ltr, Pcs"/>
                    <label for="pro_unit">Product Unit (Optional)</label>
                  </div>
                  @error('pro_unit')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-sm-6">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('pro_video') is-invalid @enderror" name="pro_video" value="{{ old('pro_video') }}" id="pro_video" placeholder="Video URL" />
                    <label for="pro_video">Product Video (Optional)</label>
                  </div>
                  @error('pro_video')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-sm-6">
                  <label for="document" class="form-label fw-semibold">Document (Optional)</label>
                  <input type="file" class="form-control rounded-0 @error('document') is-invalid @enderror" name="document" id="document" />
                  @error('document')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-sm-12">
                  <div class="card rounded-0 border mb-0">
                    <div class="card-header border-bottom">
                      <div class="fw-semibold">Description *</div>
                    </div>
                    <div class="card-body p-0">
                      <textarea name="description" id="description" class="form-control d-none @error('description') is-invalid @enderror" rows="6">{{ old('description') }}</textarea>
                      <div
                        id="full-editor"
                        class="js-quill-editor border-0 rounded-0"
                        data-quill-theme="snow"
                        data-quill-target="#description"
                        data-quill-placeholder="Write product description..."
                        data-quill-initial-html="{{ e((string) old('description')) }}"
                        style="min-height: 260px;"></div>
                      @error('description')
                      <div class="text-danger fw-semibold mt-1 px-3 pb-2">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>

                <div class="col-sm-12">
                  <div class="card rounded-0 border">
                    <div class="card-header border-bottom">
                      <h5 class="card-title mb-0 fw-semibold">Attributes / Variants</h5>
                    </div>
                    <div class="card-body mt-3">
                      <div class="row g-3">
                        <div class="col-12 col-lg-6">
                          <div class="form-floating form-floating-outline">
                            <select class="select2 form-select form-select-sm" id="variant_attributes" multiple data-placeholder="Select attributes" data-allow-clear="true">
                            <option value=""></option>
                            @if(isset($attributes) && count($attributes) > 0)
                              @foreach($attributes as $a)
                                <option value="{{ $a->id ?? '' }}" data-slug="{{ $a->slug ?? '' }}">{{ $a->name ?? 'N/A' }}</option>
                              @endforeach
                            @else
                              <option value="" disabled>No attributes available</option>
                            @endif
                            </select>
                            <label for="variant_attributes">Attributes</label>
                          </div>
                        </div>
                        <div class="col-12 col-lg-6" id="variant_attribute_values_container"></div>
                      </div>

                      <textarea class="d-none" id="variant_attributes_json">@json(isset($attributes) ? $attributes : [])</textarea>
                      <textarea class="d-none" id="variant_attribute_values_json">@json(isset($attributeValues) ? $attributeValues : [])</textarea>

                      <div class="row mt-3">
                        <div class="col-12" id="variants_table" style="display:none">
                          <div class="table-responsive">
                            <table class="table table-bordered table-variants align-middle mb-0 rounded-0">
                              <thead class="table-light">
                                <tr>
                                  <th>Variant</th>
                                  <th>Purchase</th>
                                  <th>Sale</th>
                                  <th>Discount</th>
                                  <th>Image</th>
                                  <th>Stock</th>
                                </tr>
                              </thead>
                              <tbody id="variants_container"></tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom">
              <h5 class="card-title mb-0 fw-semibold">Pricing & Inventory</h5>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <div class="col-12 col-md-6 mb-3">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('purchase_price') is-invalid @enderror" name="purchase_price" value="{{ old('purchase_price') }}" id="purchase_price" placeholder="0.00" />
                    <label for="purchase_price">Purchase Price *</label>
                  </div>
                  @error('purchase_price')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-md-6 mb-3">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('new_price') is-invalid @enderror" name="new_price" value="{{ old('new_price') }}" id="new_price" placeholder="0.00" />
                    <label for="new_price">Best Price *</label>
                  </div>
                  @error('new_price')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-md-6 mb-3">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('old_price') is-invalid @enderror" name="old_price" value="{{ old('old_price') }}" id="old_price" placeholder="0.00" />
                    <label for="old_price">Discounted Price (Old Price)</label>
                  </div>
                  @error('old_price')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-md-6 mb-3">
                  <div class="form-floating form-floating-outline">
                    <input type="number" class="form-control rounded-0 @error('stock') is-invalid @enderror" name="stock" value="{{ old('stock', 0) }}" id="stock" placeholder="0" />
                    <label for="stock">Stock *</label>
                  </div>
                  @error('stock')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>
              </div>
            </div>
          </div>

          <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom">
              <h5 class="card-title mb-0 fw-semibold">Organize & Settings</h5>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <div class="col-12 col-md-6 mb-3">
                  <div class="form-floating form-floating-outline">
                    <select class="select2 form-select form-select-sm" name="coupon_ids[]" id="coupon_ids" multiple data-placeholder="Select coupons" data-allow-clear="true">
                    <option value=""></option>
                    @if(isset($coupons) && count($coupons) > 0)
                      @foreach($coupons as $coupon)
                        <option value="{{ $coupon->id ?? '' }}">{{ $coupon->code ?? 'N/A' }}</option>
                      @endforeach
                    @else
                      <option value="" disabled>No coupons available</option>
                    @endif
                    </select>
                    <label for="coupon_ids">Coupons (Optional)</label>
                  </div>
                </div>

                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label fw-semibold">Brands</label>
                  <select class="form-select rounded-0 @error('brand_id') is-invalid @enderror" name="brand_id">
                    <option value="">Select Brand..</option>
                    @if(isset($brands) && count($brands) > 0)
                      @foreach($brands as $value)
                      <option value="{{$value->id ?? ''}}" @if(old('brand_id')==($value->id ?? '')) selected @endif>{{$value->name ?? 'N/A'}}</option>
                      @endforeach
                    @else
                      <option value="" disabled>No brands available</option>
                    @endif
                  </select>
                  @error('brand_id')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>
              </div>

              <div class="row g-4 mt-1">
                <div class="col-12 col-md-4 mb-3">
                  <label class="form-label d-block fw-semibold">Status</label>
                  <div class="form-check form-switch custom-switch-sharp">
                      <input type="checkbox" class="form-check-input rounded-0" value="1" name="status" {{ old('status', 1) ? 'checked' : '' }}>
                      <label class="form-check-label">Active</label>
                  </div>
                </div>

                <div class="col-12 col-md-4 mb-3">
                  <label class="form-label d-block fw-semibold">Hot Deals</label>
                  <div class="form-check form-switch custom-switch-sharp">
                      <input type="checkbox" class="form-check-input rounded-0" value="1" name="topsale" {{ old('topsale') ? 'checked' : '' }}>
                      <label class="form-check-label">Enable</label>
                  </div>
                </div>

                <div class="col-12 col-md-4 mb-3">
                  <label class="form-label d-block fw-semibold">Recommendation</label>
                  <div class="form-check form-switch custom-switch-sharp">
                      <input type="checkbox" class="form-check-input rounded-0" value="1" name="is_recommended" {{ old('is_recommended') ? 'checked' : '' }}>
                      <label class="form-check-label">Enable</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
            @php
              $model = new App\Models\Product();
            @endphp
            @include('backEnd.layouts.partials.seo-meta-form')
          @endcomponent

        </div>
        <div class="d-flex justify-content-end gap-2 mb-4">
          <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="productForm">
              <i class="ri ri-save-line me-1"></i> Publish Product
          </x-admin.button>
        </div>
        </form>
      </div>
    </div>
  </div>

@endsection

@push('script')
<script src="{{ asset('admin/assets/js/admin-page-product-create.js') }}"></script>
@endpush
