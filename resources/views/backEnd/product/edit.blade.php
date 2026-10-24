@extends('backEnd.layouts.app') 
@section('title','Product Edit') 

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card rounded-0 mb-4">
      <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
        <h5 class="mb-0 text-uppercase fw-bold">Edit Product</h5>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                <i class="ri ri-arrow-left-line me-1"></i> All Products
            </a>
            <x-admin.button variant="success" shape="square" ripple="true" type="submit" form="productEditForm">
                <i class="ri ri-refresh-line me-1"></i> Update Product
            </x-admin.button>
        </div>
      </div>
    </div>

    <div class="app-ecommerce">
      <form action="{{route('products.update')}}" method="POST" class="row" enctype="multipart/form-data" name="editForm" id="productEditForm">
        @csrf
        <input type="hidden" value="{{$edit_data->id}}" name="id" />

        <div class="col-12">
          <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom">
              <h5 class="card-title mb-0 fw-semibold">Product Information</h5>
            </div>
            <div class="card-body mt-4">
              <div class="row g-4">
                <div class="col-12 col-sm-6">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{$edit_data->name ?? '' }}" id="name" placeholder="Enter Product Name" />
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
                          <option value="{{$category->id ?? ''}}" @if(($edit_data->category_id ?? '') == ($category->id ?? '')) selected @endif>{{$category->name ?? 'N/A'}}</option>
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
                    <select class="form-select rounded-0 @error('subcategory_id') is-invalid @enderror" id="subcategory_id" name="subcategory_id">
                      <option value="">Select Subcategory..</option>
                      @if(isset($subcategory) && count($subcategory) > 0)
                        @foreach($subcategory as $key=>$value)
                        <option value="{{$value->id ?? ''}}" @if(($edit_data->subcategory_id ?? '') == ($value->id ?? '')) selected @endif>{{$value->subcategoryName ?? 'N/A'}}</option>
                        @endforeach
                      @endif
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
                    <select class="form-select rounded-0 @error('childcategory_id') is-invalid @enderror" id="childcategory_id" name="childcategory_id">
                      <option value="">Select Childcategory..</option>
                      @if(isset($childcategory) && count($childcategory) > 0)
                        @foreach($childcategory as $key=>$value)
                        <option value="{{$value->id ?? ''}}" @if(($edit_data->childcategory_id ?? '') == ($value->id ?? '')) selected @endif>{{$value->childcategoryName ?? 'N/A'}}</option>
                        @endforeach
                      @endif
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
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('pro_unit') is-invalid @enderror" name="pro_unit" value="{{ $edit_data->pro_unit ?? '' }}" id="pro_unit" placeholder="Unit" />
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
                    <input type="text" class="form-control rounded-0 @error('pro_video') is-invalid @enderror" name="pro_video" value="{{ $edit_data->pro_video ?? '' }}" id="pro_video" placeholder="Video URL" />
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
                  @php
                    $rawDoc = (string) ($edit_data->document ?? '');
                    $hasDocFile = $rawDoc !== '' && (str_starts_with($rawDoc, 'uploads/') || str_starts_with($rawDoc, 'public/uploads/') || str_starts_with($rawDoc, '/uploads/') || str_starts_with($rawDoc, '/public/uploads/'));
                  @endphp
                  @if($hasDocFile)
                    @php
                      $docPath = preg_replace('~^public/~', '', $rawDoc);
                      $docPath = ltrim($docPath, '/');
                    @endphp
                    <div class="form-text">
                      <a href="{{ asset($docPath) }}" target="_blank" class="text-primary fw-semibold"><i class="ri ri-file-download-line me-1"></i> View current document</a>
                    </div>
                  @endif
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
                      <textarea name="description" id="description" class="form-control d-none @error('description') is-invalid @enderror" rows="6">{{ old('description', $edit_data->description ?? '') }}</textarea>
                      <div
                        id="full-editor"
                        class="js-quill-editor border-0 rounded-0"
                        data-quill-theme="snow"
                        data-quill-target="#description"
                        data-quill-placeholder="Write product description..."
                        style="min-height: 260px;"></div>
                      @error('description')
                      <div class="text-danger fw-semibold mt-1 px-3 pb-2">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
          
          <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom">
              <h5 class="card-title mb-0 fw-semibold">Gallery Images</h5>
            </div>
            <div class="card-body mt-4">
              <div class="row g-4">
                <div class="col-sm-12">
                  @if(isset($edit_data->images) && count($edit_data->images) > 0)
                    <div class="d-flex flex-wrap gap-4 mb-4">
                      @foreach ($edit_data->images as $image)
                        @php
                            $imgPath = preg_replace('~^public/~', '', (string) ($image->image ?? ''));
                            $imgPath = ltrim($imgPath, '/');
                        @endphp
                        @if($imgPath)
                        <div class="card rounded-0 border position-relative" style="width: 150px;">
                            <img src="{{ asset($imgPath) }}" alt="Product Image" class="card-img-top rounded-0" style="height: 120px; object-fit: cover;" onerror="this.src='{{ asset('assets/images/empty.png') }}'">
                            <div class="card-body p-2">
                              <input type="text" name="title[]" class="form-control form-control-sm rounded-0 mb-2" value="{{ $image->title ?? '' }}" placeholder="Image Title">
                              <input type="hidden" name="image[]" value="{{ $image->id ?? '' }}">
                              <x-admin.button variant="danger" size="sm" shape="square" ripple="true" class="w-100 remove-image delete-confirm" type="submit" form="fmRemoveProductImage{{ $image->id }}" data-confirm-title="Remove this photo?" data-confirm-text="The gallery row and its file are deleted immediately." data-confirm-ok="Yes, remove"><i class="ri ri-delete-bin-line me-1"></i> Remove</x-admin.button>
                            </div>
                        </div>
                        @endif
                      @endforeach
                    </div>
                  @endif

                  <div class="clone hide" style="display: none;">
                      <div class="control-group input-group mt-2">
                          <input type="file" name="image[]" class="form-control rounded-0" />
                          <input type="text" name="title[]" class="form-control rounded-0" placeholder="Image Title" />
                          <x-admin.button variant="danger" shape="square" ripple="true" type="button"><i class="ri ri-delete-bin-line"></i></x-admin.button>
                      </div>
                  </div>

                  <div class="input-group control-group increment">
                      <input type="file" name="image[]" class="form-control rounded-0 @error('image') is-invalid @enderror" />
                      <input type="text" name="title[]" class="form-control rounded-0" placeholder="Image Title" />
                      <x-admin.button variant="primary" shape="square" ripple="true" class="btn-increment" type="button" title="Add image"><i class="ri ri-add-line me-1"></i> Add</x-admin.button>
                  </div>
                  @error('image')
                  <span class="invalid-feedback d-block fw-semibold mt-1" role="alert">
                      <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>
              </div>
            </div>
          </div>

          <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0 fw-semibold">Variants</h5>
              <a href="{{ route('admin.variants.index', ['product_id' => $edit_data->id]) }}" class="btn btn-outline-primary btn-square waves-effect">
                <i class="ri ri-git-merge-line me-1"></i> Manage Variants
              </a>
            </div>
            <div class="card-body mt-3">
              <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 rounded-0">
                  <thead class="table-light">
                    <tr>
                      <th style="min-width:90px;">ID</th>
                      <th style="min-width:160px;">SKU</th>
                      <th style="min-width:260px;">Title</th>
                      <th style="min-width:120px;">Status</th>
                      <th style="min-width:120px;">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @if(isset($variants) && count($variants) > 0)
                      @foreach($variants as $v)
                        <tr>
                          <td>{{ $v->id ?? '' }}</td>
                          <td>{{ $v->sku ?? '' }}</td>
                          <td>{{ $v->title ?? '' }}</td>
                          <td>
                            @if((int) ($v->status ?? 0) === 1)
                              <span class="badge bg-success">Active</span>
                            @else
                              <span class="badge bg-secondary">Inactive</span>
                            @endif
                          </td>
                          <td>
                            <a href="{{ route('admin.variants.edit', $v->id) }}" class="btn btn-outline-secondary btn-sm btn-square waves-effect">
                              <i class="ri ri-edit-line me-1"></i> Edit
                            </a>
                          </td>
                        </tr>
                      @endforeach
                    @else
                      <tr>
                        <td colspan="5" class="text-center text-muted">No variants found.</td>
                      </tr>
                    @endif
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom">
              <h5 class="card-title mb-0 fw-semibold">Pricing & Inventory</h5>
            </div>
            <div class="card-body mt-4">
              <div class="row g-4">
                <div class="col-12 col-md-3">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('purchase_price') is-invalid @enderror" name="purchase_price" value="{{ $edit_data->purchase_price ?? ''}}" id="purchase_price" placeholder="0.00" />
                    <label for="purchase_price">Purchase Price *</label>
                  </div>
                  @error('purchase_price')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-md-3">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('new_price') is-invalid @enderror" name="new_price" value="{{ $edit_data->new_price ?? '' }}" id="new_price" placeholder="0.00" />
                    <label for="new_price">New Price *</label>
                  </div>
                  @error('new_price')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-md-3">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 @error('old_price') is-invalid @enderror" name="old_price" value="{{ $edit_data->old_price ?? '' }}" id="old_price" placeholder="0.00" />
                    <label for="old_price">Old Price *</label>
                  </div>
                  @error('old_price')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                </div>

                <div class="col-12 col-md-3">
                  <div class="form-floating form-floating-outline">
                    <input type="text" class="form-control rounded-0 bg-light @error('stock') is-invalid @enderror" name="stock" value="{{ $edit_data->stock ?? 0 }}" id="stock" readonly placeholder="0" />
                    <label for="stock">Stock * (Ledger-based)</label>
                  </div>
                  @error('stock')
                  <span class="invalid-feedback d-block fw-semibold" role="alert">
                    <strong>{{ $message }}</strong>
                  </span>
                  @enderror
                  <div class="form-text mt-1">
                    Use <a href="{{ route('admin.inventory.adjustment') }}" class="fw-semibold text-primary">Inventory Adjustment</a> to add/reduce stock.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card rounded-0 mb-4">
            <div class="card-header border-bottom">
              <h5 class="card-title mb-0 fw-semibold">Organize & Settings</h5>
            </div>
            <div class="card-body mt-4">
              <div class="row g-4">
                <div class="col-12 col-md-6 mb-3">
                  <label class="form-label fw-semibold">Brands</label>
                  <select class="form-select rounded-0 @error('brand_id') is-invalid @enderror" name="brand_id">
                    <option value="">Select Brand..</option>
                    @if(isset($brands) && count($brands) > 0)
                      @foreach($brands as $value)
                      <option value="{{$value->id ?? ''}}" @if(($edit_data->brand_id ?? '')==($value->id ?? '')) selected @endif>{{$value->name ?? 'N/A'}}</option>
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

                <div class="col-12 col-md-6 mb-3">
                  <div class="form-floating form-floating-outline">
                    <select class="select2 form-select form-select-sm" name="coupon_ids[]" id="coupon_ids" multiple data-placeholder="Select coupons" data-allow-clear="true">
                      <option value=""></option>
                      @if(isset($coupons) && count($coupons) > 0)
                        @foreach($coupons as $coupon)
                          <option value="{{ $coupon->id ?? '' }}" @if(isset($selected_coupon_ids) && in_array($coupon->id ?? '', $selected_coupon_ids)) selected @endif>{{ $coupon->code ?? 'N/A' }}</option>
                        @endforeach
                      @endif
                    </select>
                    <label for="coupon_ids">Coupons (Optional)</label>
                  </div>
                </div>
              </div>

              <div class="row g-4 mt-1">
                <div class="col-12 col-md-4 mb-3">
                  <label class="form-label d-block fw-semibold">Status</label>
                  <div class="form-check form-switch custom-switch-sharp">
                      <input type="checkbox" class="form-check-input rounded-0" value="1" name="status" {{ ($edit_data->status ?? 0) == 1 ? 'checked' : '' }}>
                      <label class="form-check-label">Active</label>
                  </div>
                </div>

                <div class="col-12 col-md-4 mb-3">
                  <label class="form-label d-block fw-semibold">Hot Deals</label>
                  <div class="form-check form-switch custom-switch-sharp">
                      <input type="checkbox" class="form-check-input rounded-0" value="1" name="topsale" {{ ($edit_data->topsale ?? 0) == 1 ? 'checked' : '' }}>
                      <label class="form-check-label">Enable</label>
                  </div>
                </div>

                <div class="col-12 col-md-4 mb-3">
                  <label class="form-label d-block fw-semibold">Recommendation</label>
                  <div class="form-check form-switch custom-switch-sharp">
                      <input type="checkbox" class="form-check-input rounded-0" value="1" name="is_recommended" {{ ($edit_data->is_recommended ?? 0) == 1 ? 'checked' : '' }}>
                      <label class="form-check-label">Enable</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
            @php
              $model = $edit_data;
            @endphp
            @include('backEnd.layouts.partials.seo-meta-form')
          @endcomponent

        </div>
        </form>

        {{-- One POST form per gallery photo, outside the edit form because a
             nested form is invalid HTML. The Remove button points here with
             form="...", so the request carries only the image id plus the CSRF
             token - not the whole product form. --}}
        @if(isset($edit_data->images))
          @foreach($edit_data->images as $image)
            <form id="fmRemoveProductImage{{ $image->id }}" method="POST" action="{{ route('products.image.destroy') }}" class="d-none">
              @csrf
              <input type="hidden" name="id" value="{{ $image->id }}">
            </form>
          @endforeach
        @endif
      </div>
    </div>
  </div>
@endsection

@push('script')
<script>
    // WAF Safety: Remove empty file inputs before form submit to prevent
    // LiteSpeed/ModSecurity 403 errors caused by empty filename + application/octet-stream
    (function () {
      var form = document.getElementById('productEditForm');
      if (form) {
        form.addEventListener('submit', function () {
          var fileInputs = form.querySelectorAll('input[type="file"]');
          fileInputs.forEach(function (input) {
            if (!input.files || input.files.length === 0) {
              input.removeAttribute('name');
            }
          });
        });
      }
    })();

    // Handle dynamically adding/removing more gallery inputs
    document.addEventListener('click', function(e) {
      if (e.target && e.target.closest('.btn-increment')) {
        e.preventDefault();
        var cloneHtml = document.querySelector('.clone').innerHTML;
        var newEl = document.createElement('div');
        newEl.innerHTML = cloneHtml;
        var firstChild = newEl.firstElementChild;
        if(firstChild) {
          e.target.closest('.increment').insertAdjacentElement('afterend', firstChild);
        }
      } else if (e.target && e.target.closest('.btn-danger') && !e.target.closest('.remove-image')) {
        e.preventDefault();
        var group = e.target.closest('.control-group');
        if(group) {
          group.remove();
        }
      }
    });
</script>
@endpush
