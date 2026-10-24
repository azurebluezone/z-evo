@extends('backEnd.layouts.app')

@section('title','Product Create')

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Create Product</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
                <i class="ri-arrow-left-line me-1"></i> All Products
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
            <div class="card-body mt-4">

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
                            <table class="table table-bordered align-middle mb-0 rounded-0">
                              <thead class="table-light">
                                <tr>
                                  <th style="min-width:220px;">Variant</th>
                                  <th style="min-width:140px;">Purchase</th>
                                  <th style="min-width:140px;">Sale</th>
                                  <th style="min-width:140px;">Discount</th>
                                  <th style="min-width:220px;">Image</th>
                                  <th style="min-width:140px;">Stock</th>
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
            <div class="card-body mt-4">
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
            <div class="card-body mt-4">
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
              <i class="ri-save-line me-1"></i> Publish Product
          </x-admin.button>
        </div>
        </form>
      </div>
    </div>
  </div>

@endsection

@push('script')
<script>
    // WAF Safety: Remove empty file inputs before form submit to prevent
    // LiteSpeed/ModSecurity 403 errors caused by empty filename + application/octet-stream
    (function () {
      var form = document.getElementById('productForm');
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

    (function () {
      function byId(id) { return document.getElementById(id); }

      function resetSelect(selectEl, placeholder) {
        if (!selectEl) return;
        selectEl.innerHTML = '';
        var opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder || 'Select...';
        selectEl.appendChild(opt);
      }

      function setOptionsFromMap(selectEl, mapObj, placeholder, selectedValue) {
        resetSelect(selectEl, placeholder);
        if (!selectEl || !mapObj) return;

        Object.keys(mapObj).forEach(function (key) {
          var label = mapObj[key];
          if (label === null || label === undefined) return;
          var opt = document.createElement('option');
          opt.value = String(key);
          opt.textContent = String(label);
          if (selectedValue !== null && selectedValue !== undefined && String(selectedValue) === String(key)) {
            opt.selected = true;
          }
          selectEl.appendChild(opt);
        });
      }

      function fetchJson(url) {
        return fetch(url, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        }).then(function (res) {
          if (!res.ok) throw new Error('Request failed: ' + res.status);
          return res.json();
        });
      }

      function initCategoryDependentDropdowns() {
        var categoryEl = byId('category_id');
        var subcategoryEl = byId('subcategory_id');
        var childcategoryEl = byId('childcategory_id');
        if (!categoryEl || !subcategoryEl || !childcategoryEl) return;

        var oldSub = subcategoryEl.getAttribute('data-selected') || '';
        var oldChild = childcategoryEl.getAttribute('data-selected') || '';

        function loadSubcategories(categoryId, selectedSubId) {
          resetSelect(subcategoryEl, 'Loading...');
          resetSelect(childcategoryEl, 'Select Childcategory..');
          if (!categoryId) {
            resetSelect(subcategoryEl, 'Select Subcategory..');
            return Promise.resolve(null);
          }
          return fetchJson('/ajax-product-subcategory?category_id=' + encodeURIComponent(categoryId))
            .then(function (data) {
              setOptionsFromMap(subcategoryEl, data || {}, 'Select Subcategory..', selectedSubId || '');
              return data;
            })
            .catch(function () {
              resetSelect(subcategoryEl, 'Select Subcategory..');
              return null;
            });
        }

        function loadChildcategories(subcategoryId, selectedChildId) {
          resetSelect(childcategoryEl, 'Loading...');
          if (!subcategoryId) {
            resetSelect(childcategoryEl, 'Select Childcategory..');
            return Promise.resolve(null);
          }
          return fetchJson('/ajax-product-childcategory?subcategory_id=' + encodeURIComponent(subcategoryId))
            .then(function (data) {
              setOptionsFromMap(childcategoryEl, data || {}, 'Select Childcategory..', selectedChildId || '');
              return data;
            })
            .catch(function () {
              resetSelect(childcategoryEl, 'Select Childcategory..');
              return null;
            });
        }

        categoryEl.addEventListener('change', function () {
          var catId = (categoryEl.value || '').trim();
          loadSubcategories(catId, '');
        });

        subcategoryEl.addEventListener('change', function () {
          var subId = (subcategoryEl.value || '').trim();
          loadChildcategories(subId, '');
        });

        // On page load (e.g. after validation error), rehydrate dependent selects.
        var initialCategoryId = (categoryEl.value || '').trim();
        if (initialCategoryId) {
          loadSubcategories(initialCategoryId, oldSub).then(function () {
            var activeSub = (subcategoryEl.value || '').trim();
            if (activeSub) loadChildcategories(activeSub, oldChild);
          });
        } else {
          resetSelect(subcategoryEl, 'Select Subcategory..');
          resetSelect(childcategoryEl, 'Select Childcategory..');
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCategoryDependentDropdowns);
      } else {
        initCategoryDependentDropdowns();
      }
    })();

    (function () {
      function safeJsonParse(text, fallback) {
        try {
          return JSON.parse(text);
        } catch (e) {
          return fallback;
        }
      }

      function uniqBy(arr, keyFn) {
        var seen = new Set();
        var out = [];
        for (var i = 0; i < arr.length; i++) {
          var k = keyFn(arr[i]);
          if (seen.has(k)) continue;
          seen.add(k);
          out.push(arr[i]);
        }
        return out;
      }

      function cartesianProduct(groups) {
        if (!groups.length) return [];
        var result = [[]];
        for (var i = 0; i < groups.length; i++) {
          var next = [];
          for (var r = 0; r < result.length; r++) {
            for (var g = 0; g < groups[i].length; g++) {
              next.push(result[r].concat([groups[i][g]]));
            }
          }
          result = next;
        }
        return result;
      }

      function initSelect2(el) {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;
        var $el = window.jQuery(el);
        if ($el.data('select2')) return;
        var placeholder = $el.data('placeholder') || $el.attr('data-placeholder') || 'Select...';
        var allowClear = String($el.data('allow-clear') || $el.attr('data-allow-clear') || 'false') === 'true';
        $el.select2({ width: '100%', closeOnSelect: false, placeholder: placeholder, allowClear: allowClear });
      }

      function initProductVariantUi() {
        var attrsSelect = document.getElementById('variant_attributes');
        var attrsJsonEl = document.getElementById('variant_attributes_json');
        var valsJsonEl = document.getElementById('variant_attribute_values_json');
        var valuesWrap = document.getElementById('variant_attribute_values_container');
        var tableWrap = document.getElementById('variants_table');
        var tbody = document.getElementById('variants_container');

        if (!attrsSelect || !attrsJsonEl || !valsJsonEl || !valuesWrap || !tableWrap || !tbody) return;

        initSelect2(attrsSelect);

        var attributes = safeJsonParse((attrsJsonEl.value || '').trim(), []);
        var attributeValues = safeJsonParse((valsJsonEl.value || '').trim(), []);

        function getSelectedAttributeIds() {
          return Array.prototype.slice.call(attrsSelect.selectedOptions || []).map(function (o) {
            return parseInt(o.value, 10);
          }).filter(function (n) { return !isNaN(n); });
        }

        // Rebuilding the value <select>s from scratch drops the current picks
        // (and Select2's state with them), so snapshot them first and mark the
        // same options selected again. Keyed by attribute id.
        function captureValueSelections() {
          var picked = {};
          valuesWrap.querySelectorAll('.js-attr-values').forEach(function (sel) {
            picked[sel.getAttribute('data-attr-id')] =
              Array.prototype.slice.call(sel.selectedOptions || []).map(function (o) {
                return String(o.value);
              });
          });
          return picked;
        }

        function renderValueSelectors() {
          var selectedAttrIds = getSelectedAttributeIds();
          var previousSelections = captureValueSelections();
          valuesWrap.innerHTML = '';

          if (!selectedAttrIds.length) return;

          var rows = [];
          selectedAttrIds.forEach(function (attrId) {
            var attr = attributes.find(function (a) { return parseInt(a.id, 10) === attrId; });
            if (!attr) return;

            var values = attributeValues.filter(function (v) {
              return parseInt(v.attribute_id, 10) === attrId;
            });

            values = uniqBy(values, function (v) { return String(v.id); });

            var html = '';
            html += '<div class="col-12 col-lg-6">';
            html += '  <div class="form-floating form-floating-outline">';
            html += '    <select class="select2 form-select form-select-sm js-attr-values" multiple data-placeholder="Select values" data-allow-clear="true" data-attr-id="' + attrId + '" data-attr-slug="' + (attr.slug || ('attr_' + attrId)) + '" data-attr-name="' + (attr.name || '') + '">';
            html += '      <option value=""></option>';
            values.forEach(function (v) {
              var label = (v.value !== undefined && v.value !== null) ? String(v.value) : (v.name ? String(v.name) : String(v.id));
              // Restore a value this attribute already had selected.
              var isPicked = (previousSelections[String(attrId)] || []).indexOf(String(v.id)) !== -1;
              html += '    <option value="' + v.id + '"' + (isPicked ? ' selected' : '') + '>' + label + '</option>';
            });
            html += '    </select>';
            html += '    <label>' + (attr.name || 'Attribute') + ' Values</label>';
            html += '  </div>';
            html += '</div>';

            rows.push(html);
          });

          if (!rows.length) return;

          valuesWrap.innerHTML = '<div class="row g-3">' + rows.join('') + '</div>';
          valuesWrap.querySelectorAll('.js-attr-values').forEach(function (el) {
            initSelect2(el);
            el.addEventListener('change', function () {
              buildVariants();
            });

            if (window.jQuery) {
              try {
                window.jQuery(el).on('select2:select select2:unselect change', function () {
                  buildVariants();
                });
              } catch (e) {
                // ignore
              }
            }
          });
        }

        // A rebuild recreates every row, so numbers already typed for a
        // combination are kept in this store, keyed by that combination's ids
        // JSON, and restored afterwards. The store outlives a temporary empty
        // table (e.g. an attribute was just added but has no value yet).
        // File inputs cannot be refilled by script and are left empty.
        var rowValuesStore = {};

        function captureRowValues() {
          Array.prototype.slice.call(tbody.querySelectorAll('tr')).forEach(function (tr) {
            var idsInput = tr.querySelector('input[name="variant_attrs_ids[]"]');
            if (!idsInput) return;
            var read = function (name) {
              var el = tr.querySelector('input[name="' + name + '"]');
              return el ? el.value : '';
            };
            rowValuesStore[idsInput.value] = {
              purchase: read('variant_purchase_price[]'),
              sale: read('variant_sale_price[]'),
              discount: read('variant_discount_price[]'),
              stock: read('variant_initial_stock[]')
            };
          });
        }

        function buildVariants() {
          var selects = Array.prototype.slice.call(valuesWrap.querySelectorAll('.js-attr-values'));
          // Snapshot before any early return clears the table.
          captureRowValues();

          if (!selects.length) {
            tbody.innerHTML = '';
            tableWrap.style.display = 'none';
            return;
          }

          var groups = [];
          var incomplete = false;
          selects.forEach(function (sel) {
            var opts = Array.prototype.slice.call(sel.selectedOptions || []);
            var attrSlug = sel.getAttribute('data-attr-slug') || 'attr';
            var attrName = sel.getAttribute('data-attr-name') || attrSlug;

            if (!opts.length) {
              incomplete = true;
              return;
            }

            var picked = opts.map(function (o) {
              return {
                attrSlug: attrSlug,
                attrName: attrName,
                valueId: parseInt(o.value, 10),
                valueText: (o.textContent || '').trim()
              };
            }).filter(function (x) { return x.valueId && x.valueText; });

            if (!picked.length) {
              incomplete = true;
              return;
            }

            groups.push(picked);
          });

          // Every selected attribute needs at least one value. Building rows
          // from a partial selection would post a combination that is missing
          // an attribute, so wait for a complete pick instead.
          if (incomplete || !groups.length) {
            tbody.innerHTML = '';
            tableWrap.style.display = 'none';
            return;
          }

          var combos = cartesianProduct(groups);
          tbody.innerHTML = '';

          combos.forEach(function (combo, idx) {
            var attrsTextMap = {};
            var attrsIdsMap = {};
            var titleParts = [];

            combo.forEach(function (item) {
              attrsTextMap[item.attrSlug] = item.valueText;
              attrsIdsMap[item.attrSlug] = item.valueId;
              titleParts.push(item.attrName + ': ' + item.valueText);
            });

            var idsJson = JSON.stringify({ ids: attrsIdsMap });
            var textJson = JSON.stringify({ attrs: attrsTextMap });
            // Values typed before this rebuild, keyed by the same ids JSON.
            var prev = rowValuesStore[idsJson] || {};

            var tr = document.createElement('tr');
            tr.innerHTML = ''
              + '<td>'
              + '  <div class="fw-semibold">' + titleParts.join(' | ') + '</div>'
              + '  <input type="hidden" name="variant_attrs_ids[]" value="' + idsJson.replace(/\"/g, '&quot;') + '">' 
              + '  <input type="hidden" name="variant_attrs_text[]" value="' + textJson.replace(/\"/g, '&quot;') + '">' 
              + '</td>'
              + '<td><input type="number" min="0" step="1" class="form-control rounded-0" name="variant_purchase_price[]" value="' + (prev.purchase || '') + '" placeholder="0"></td>'
              + '<td><input type="number" min="0" step="1" class="form-control rounded-0" name="variant_sale_price[]" value="' + (prev.sale || '') + '" placeholder="0"></td>'
              + '<td><input type="number" min="0" step="1" class="form-control rounded-0" name="variant_discount_price[]" value="' + (prev.discount || '') + '" placeholder="0"></td>'
              + '<td><input type="file" class="form-control rounded-0" name="variant_image[]" accept="image/*"></td>'
              + '<td><input type="number" min="0" step="1" class="form-control rounded-0" name="variant_initial_stock[]" value="' + (prev.stock || '') + '" placeholder="0"></td>';

            tbody.appendChild(tr);
          });

          tableWrap.style.display = '';
        }

        attrsSelect.addEventListener('change', function () {
          renderValueSelectors();
          buildVariants();
        });

        if (window.jQuery) {
          try {
            window.jQuery(attrsSelect).on('select2:select select2:unselect change', function () {
              renderValueSelectors();
              buildVariants();
            });
          } catch (e) {
            // ignore
          }
        }

        renderValueSelectors();
        buildVariants();
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductVariantUi);
      } else {
        initProductVariantUi();
      }
    })();
</script>
@endpush
