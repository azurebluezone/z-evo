@extends('backEnd.layouts.app')
@section('title','Create Variant')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Create Variant</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.variants.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="adminForm">
              <i class="ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="row">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Create Variant</h5>
                <a href="{{ route('admin.variants.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form method="POST" enctype="multipart/form-data" action="{{ route('admin.variants.store') }}" class="row g-4" id="adminForm">
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Product *</label>
                        <select name="product_id" class="form-control rounded-0 form-select @error('product_id') is-invalid @enderror">
                            <option value="">Select product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                    #{{ $p->id }} — {{ $p->name }} {{ $p->product_code ? '(' . $p->product_code . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mt-md-4 mt-0">
                            <input type="text" name="sku" id="sku" class="form-control rounded-0 @error('sku') is-invalid @enderror" value="{{ old('sku') }}" placeholder="SKU" />
                            <label for="sku">SKU *</label>
                        </div>
                        @error('sku')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="title" id="title" class="form-control rounded-0 @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="Title (Optional)" />
                            <label for="title">Title (Optional)</label>
                        </div>
                        @error('title')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="number" name="price_override" id="price_override" class="form-control rounded-0 @error('price_override') is-invalid @enderror" value="{{ old('price_override') }}" min="0" placeholder="Price Override (Optional)" />
                            <label for="price_override">Price Override (Optional)</label>
                        </div>
                        @error('price_override')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="number" name="weight_gram" id="weight_gram" class="form-control rounded-0 @error('weight_gram') is-invalid @enderror" value="{{ old('weight_gram') }}" min="0" placeholder="Weight (gram) (Optional)" />
                            <label for="weight_gram">Weight (gram) (Optional)</label>
                        </div>
                        @error('weight_gram')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">

                            {{--
                                Added: the fields that make the storefront's variant swap
                                work.

                                product_variants.image is what FrontendController::variantDetail
                                returns as the new main photo, and sale_price / discount_price
                                are the variant's own prices. This screen could not set or
                                change any of them, so a variant could never be given its own
                                picture or price after the product was created - every swatch
                                showed the product's own image and price.

                                The <form> carries enctype for the file input.
                            --}}
                            <div class="col-md-6">
                                <label class="form-label d-block fw-semibold">Variant photo</label>
                                <input type="file"
                                       name="image"
                                       id="image"
                                       class="form-control rounded-0 @error('image') is-invalid @enderror"
                                       accept="image/jpeg,image/png,image/webp">
                                @error('image')
                                    <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="number" name="sale_price" id="sale_price" class="form-control rounded-0 @error('sale_price') is-invalid @enderror" value="{{ old('sale_price', $variant->sale_price ?? '') }}" min="0" placeholder="Variant sale price" />
                                    <label for="sale_price">Variant sale price</label>
                                </div>
                                <p class="text-muted small mt-1">Leave empty to use the product price.</p>
                                @error('sale_price')
                                    <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="number" name="purchase_price" id="purchase_price" class="form-control rounded-0 @error('purchase_price') is-invalid @enderror" value="{{ old('purchase_price', $variant->purchase_price ?? '') }}" min="0" placeholder="Variant cost price" />
                                    <label for="purchase_price">Variant cost price</label>
                                </div>
                                @error('purchase_price')
                                    <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="number" name="discount_price" id="discount_price" class="form-control rounded-0 @error('discount_price') is-invalid @enderror" value="{{ old('discount_price', $variant->discount_price ?? '') }}" min="0" placeholder="Variant discount price" />
                                    <label for="discount_price">Variant discount price</label>
                                </div>
                                @error('discount_price')
                                    <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Attribute selection. Stored in product_variants.attributes_json;
                                 this is what turns the storefront's option pills on. --}}
                            <div class="col-12">
                                <label class="form-label d-block fw-semibold">Options (colour, size, etc.)</label>

                                @if(empty($attributes))
                                    <p class="text-muted small mb-0">
                                        No selectable attributes are set up yet. Create one under
                                        Attributes first, then the storefront will show a picker here.
                                    </p>
                                @else
                                    @foreach($attributes as $attribute)
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold" for="attr-{{ $attribute->id }}">
                                                {{ $attribute->name }}
                                            </label>
                                            <select class="form-select rounded-0"
                                                    id="attr-{{ $attribute->id }}"
                                                    name="attribute_ids[{{ $attribute->slug }}]">
                                                <option value="">-- none --</option>
                                                @foreach($attribute->values as $value)
                                                    <option value="{{ $value->id }}"
                                                        @selected((string) old('attribute_ids.' . $attribute->slug, $selected[$attribute->slug] ?? '') === (string) $value->id)>
                                                        {{ $value->value }}{{ $value->code ? ' (' . $value->code . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                        <label class="form-label d-block fw-semibold">Status</label>
                        <div class="form-check form-switch custom-switch-sharp">
                            <input type="checkbox" name="status" class="form-check-input rounded-0" value="1" {{ old('status', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                        @error('status')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <x-admin.button variant="success" shape="square" ripple="true" type="submit">
                            <i class="ri-save-line me-1"></i> Create Variant
                        </x-admin.button>
                    </div>
                </form>

                <div class="card rounded-0 mt-4 border-info shadow-none">
                    <div class="card-body text-info d-flex align-items-center">
                        <i class="ri-information-line fs-4 me-2"></i>
                        <span>Attributes UI is coming soon. For now, this is a basic variant record for inventory ledger tracking.</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

    </div>
  </div>
</div>
@endsection
