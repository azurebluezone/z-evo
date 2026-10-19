@extends('backEnd.layouts.app')
@section('title','Edit Variant')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Edit Variant</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.variants.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="edit-variantForm">
              <i class="ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="row">
    <div class="col-12">
        <div class="card rounded-0">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                <h5 class="mb-0 text-uppercase fw-bold">Edit Variant</h5>
                <a href="{{ route('admin.variants.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                    <i class="ri-arrow-left-line me-1"></i> Back to Manage
                </a>
            </div>

            <div class="card-body mt-4">
                <form method="POST" enctype="multipart/form-data" action="{{ route('admin.variants.update', $variant->id) }}" id="edit-variantForm" class="row g-4">
                    @csrf

                    <div class="col-md-6">
                        @php
                            $productLabel = '#' . ($variant->product_id ?? '');
                            if (isset($products) && $products) {
                                foreach ($products as $p) {
                                    if ((string) ($p->id ?? '') === (string) ($variant->product_id ?? '')) {
                                        $productLabel = '#' . ($p->id ?? '') . ' — ' . ($p->name ?? '');
                                        if (!empty($p->product_code)) {
                                            $productLabel .= ' (' . $p->product_code . ')';
                                        }
                                        break;
                                    }
                                }
                            }
                        @endphp
                        <div class="form-floating form-floating-outline">
                            <input type="text" class="form-control rounded-0" value="{{ $productLabel }}" placeholder="Product" readonly />
                            <label>Product *</label>
                        </div>
                        <input type="hidden" name="product_id" value="{{ old('product_id', $variant->product_id) }}" />
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline mt-md-4 mt-0">
                            <input type="text" name="sku" id="sku" class="form-control rounded-0 @error('sku') is-invalid @enderror" value="{{ old('sku', $variant->sku ?? '') }}" placeholder="SKU" />
                            <label for="sku">SKU *</label>
                        </div>
                        @error('sku')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="text" name="title" id="title" class="form-control rounded-0 @error('title') is-invalid @enderror" value="{{ old('title', $variant->title ?? '') }}" placeholder="Title (Optional)" />
                            <label for="title">Title (Optional)</label>
                        </div>
                        @error('title')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="number" name="price_override" id="price_override" class="form-control rounded-0 @error('price_override') is-invalid @enderror" value="{{ old('price_override', $variant->price_override ?? '') }}" min="0" placeholder="Price Override (Optional)" />
                            <label for="price_override">Price Override (Optional)</label>
                        </div>
                        @error('price_override')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input type="number" name="weight_gram" id="weight_gram" class="form-control rounded-0 @error('weight_gram') is-invalid @enderror" value="{{ old('weight_gram', $variant->weight_gram ?? '') }}" min="0" placeholder="Weight (gram) (Optional)" />
                            <label for="weight_gram">Weight (gram) (Optional)</label>
                        </div>
                        @error('weight_gram')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>


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
                        @if(!empty($variant->image))
                            <img src="{{ \App\Support\MediaPath::url($variant->image, 'product') }}"
                                 alt="Current variant photo"
                                 class="rounded-0 mb-2"
                                 style="max-height:120px;max-width:100%;object-fit:contain;border:1px solid #e5e9e0;">
                            <p class="text-muted small">Upload a new file to replace it. Leave empty to keep the current photo.</p>
                        @endif
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

                    <div class="col-md-6">
                        <label class="form-label d-block fw-semibold">Status</label>
                        <div class="form-check form-switch custom-switch-sharp">
                            <input type="checkbox" name="status" class="form-check-input rounded-0" value="1" {{ (string)old('status', $variant->status ?? '1') === '1' ? 'checked' : '' }}>
                            <label class="form-check-label">Active</label>
                        </div>
                        @error('status')
                            <span class="invalid-feedback d-block fw-semibold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12 mt-4">
                        <x-admin.button variant="success" shape="square" ripple="true" type="submit">
                            <i class="ri-refresh-line me-1"></i> Update Variant
                        </x-admin.button>
                    </div>
                </form>

                @php
                    $attrsDecoded = null;
                    try {
                        $attrsDecoded = json_decode((string) ($variant->attributes_json ?? ''), true);
                    } catch (\Throwable $e) {
                        $attrsDecoded = null;
                    }
                    $attrsMap = is_array($attrsDecoded) ? ($attrsDecoded['attrs'] ?? $attrsDecoded) : [];
                    $idsMap = is_array($attrsDecoded) ? ($attrsDecoded['ids'] ?? []) : [];
                @endphp

                <div class="card rounded-0 mt-4 border-secondary shadow-none">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Variant Mapping (Read-only)</div>
                        <div class="text-muted">Variant ID: <span class="fw-semibold">{{ $variant->id ?? '' }}</span></div>

                        @if(is_array($attrsMap) && count($attrsMap) > 0)
                            <div class="mt-3">
                                <div class="fw-semibold mb-2">Attribute value IDs (used for matching + inventory)</div>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0 rounded-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="min-width:160px;">Attribute</th>
                                                <th style="min-width:140px;">Value ID</th>
                                                <th>Value Label</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($attrsMap as $slug => $label)
                                                <tr>
                                                    <td>{{ $slug }}</td>
                                                    <td>{{ $idsMap[$slug] ?? '' }}</td>
                                                    <td>{{ $label }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="mt-2 text-muted">No attributes_json attached to this variant.</div>
                        @endif
                    </div>
                </div>

                <div class="card rounded-0 mt-4 border-info shadow-none">
                    <div class="card-body text-info d-flex align-items-center">
                        <i class="ri-information-line fs-4 me-2"></i>
                        <span>Attributes UI is coming soon. This screen manages core variant fields used by inventory ledger.</span>
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
