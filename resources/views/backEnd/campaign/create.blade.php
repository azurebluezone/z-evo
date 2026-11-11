@extends('backEnd.layouts.app')
@section('title','Landing Page Create')
@section('css')

@endsection
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Landing Page Create</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('campaign.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="campaignForm">
              <i class="ri ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="w-100">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('campaign.store') }}" method="POST" class="row g-3" data-parsley-validate="" enctype="multipart/form-data" id="campaignForm">
                        @csrf

                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label for="banner_title" class="form-label">Banner Title *</label>
                                <input type="text" class="form-control rounded-0 @error('banner_title') is-invalid @enderror" name="banner_title" value="{{ old('banner_title') }}" id="banner_title">

                                @error('banner_title')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col-end -->

                        <div class="col-sm-12 mb-3">
                            <div class="form-group">
                                <label for="banner" class="form-label">Banner *</label>
                                <input type="file" class="form-control rounded-0 @error('banner') is-invalid @enderror " name="banner" value="{{ old('banner') }}" id="banner">
                                @error('banner')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label for="video" class="form-label">Video </label>
                                <input type="text" class="form-control rounded-0 @error('video') is-invalid @enderror" name="video" value="{{ old('video') }}" id="video">
                                @error('video')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col-end -->

                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label for="name" class="form-label">Landing Page Title *</label>
                                <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" id="name">
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col-end -->

                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="product_id" class="form-label">Products *</label>
                                <select class="select2 form-select rounded-0 @error('product_id') is-invalid @enderror" id="product_id" name="product_id" data-placeholder="Choose ...">

                                    <option value="">Select..</option>
                                    @foreach($products as $value)
                                    <option value="{{$value->id}}"
                                        data-price="{{ number_format((float) $value->new_price, 2, '.', '') }}"
                                        data-stock="{{ (int) $value->stock }}"
                                        {{ (string) old('product_id') === (string) $value->id ? 'selected' : '' }}>
                                        {{$value->name}} — ৳{{ number_format((float) $value->new_price, 2) }} · Stock {{ (int) $value->stock }}
                                    </option>
                                    @endforeach

                                </select>
                                <div class="form-text" id="campaign-product-summary" aria-live="polite">Select a product to see its current price and stock.</div>
                                @error('product_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->

                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="template_id" class="form-label">Template Design *</label>
                                <select class="select2 form-select rounded-0 @error('template_id') is-invalid @enderror" id="template_id" name="template_id" data-placeholder="Choose Template...">
                                    <option value="">Select Template..</option>
                                    @foreach($templates as $template)
                                    <option value="{{$template->id}}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                        {{$template->name}} - {{$template->description}}
                                    </option>
                                    @endforeach
                                </select>
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                    <a href="#" id="campaign-template-preview" class="btn btn-outline-primary btn-sm disabled" target="_blank" rel="noopener" aria-disabled="true" data-preview-url="{{ route('campaign.template-preview', ['id' => '__ID__']) }}">
                                        <i class="ri ri-eye-line me-1"></i> Preview template
                                    </a>
                                    <small class="text-muted">Opens the selected design in a new tab.</small>
                                </div>
                                @error('template_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->

                        <div class="col-sm-12">
                            <div class="border rounded p-3 mb-3">
                                <h6 class="text-uppercase fw-bold mb-3">Campaign Pricing</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" name="campaign_price_enabled" value="1" id="campaign_price_enabled" {{ old('campaign_price_enabled') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="campaign_price_enabled">Enable campaign price</label>
                                        </div>
                                        <small class="text-muted">Off means the landing page uses the product price.</small>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="campaign_price" class="form-label">Offer price (BDT)</label>
                                        <input type="number" step="0.01" min="0" class="form-control rounded-0 @error('campaign_price') is-invalid @enderror" name="campaign_price" value="{{ old('campaign_price') }}" id="campaign_price">
                                        <small class="text-muted">Fixed price for a simple product.</small>
                                        @error('campaign_price')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <label for="campaign_discount_percent" class="form-label">Discount percent</label>
                                        <input type="number" step="0.01" min="0.01" max="99.99" class="form-control rounded-0 @error('campaign_discount_percent') is-invalid @enderror" name="campaign_discount_percent" value="{{ old('campaign_discount_percent') }}" id="campaign_discount_percent">
                                        <small class="text-muted">Percent wins over the fixed price; use it for variant products.</small>
                                        @error('campaign_discount_percent')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" name="allow_coupon" value="1" id="allow_coupon" {{ old('allow_coupon') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="allow_coupon">Allow coupons on this campaign</label>
                                        </div>
                                        <small class="text-muted">Default off. If enabled, coupons calculate on the campaign price.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- col end -->

                        @include('backEnd.campaign.partials.content-fields', ['edit_data' => null, 'templates' => $templates])

                        <div class="col-sm-12 mb-3">
                            <div class="form-group">
                                <label for="image_one" class="form-label">Image One*</label>
                                <input type="file" class="form-control rounded-0 @error('image_one') is-invalid @enderror " name="image_one" value="{{ old('image_one') }}" id="image_one">
                                @error('image_one')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->

                        <div class="col-sm-12 mb-3">
                            <div class="form-group">
                                <label for="image_two" class="form-label">Image Two</label>
                                <input type="file" class="form-control rounded-0 @error('image_two') is-invalid @enderror " name="image_two" value="{{ old('image_two') }}" id="image_two">
                                @error('image_two')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->

                        <div class="col-sm-12 mb-3">
                            <div class="form-group">
                                <label for="image_three" class="form-label">Image Three</label>
                                <input type="file" class="form-control rounded-0 @error('image_three') is-invalid @enderror " name="image_three" value="{{ old('image_three') }}" id="image_three">
                                @error('image_three')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="review" class="form-label">Review Images *</label>
                                <input type="text" class="form-control rounded-0 @error('review') is-invalid @enderror" name="review" value="{{ old('review') }}" id="review">
                                @error('review')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label for="image">Review Image *</label>
                            <div class="input-group">
                                <input type="file" name="image[]" class="form-control rounded-0 @error('image') is-invalid @enderror" />
                                <x-admin.button variant="outline-success" class="btn-increment" type="button"><i class="ri ri-add-line"></i></x-admin.button>
                                @error('image')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="clone hide" style="display: none;">
                                <div class="input-group mt-2">
                                    <input type="file" name="image[]" class="form-control rounded-0" />
                                    <x-admin.button variant="outline-danger" type="button"><i class="ri ri-delete-bin-line"></i></x-admin.button>
                                </div>
                            </div>
                        </div>
                        <!-- col end -->
                        <!-- col-end -->
                        <div class="col-sm-12 my-3">
                            <div class="form-group">
                                <label for="short_description" class="form-label">Short Description</label>
                                <input type="hidden" id="short_description" name="short_description" value="{{ old('short_description') }}">
                                <div
                                    id="campaign-short-description-editor"
                                    class="js-quill-editor"
                                    data-quill-theme="snow"
                                    data-quill-toolbar-preset="full"
                                    data-quill-target="#short_description"
                                    data-quill-placeholder="Enter short description here..."
                                    data-quill-initial-html="{{ e((string) old('short_description')) }}"
                                    style="min-height: 140px;"></div>
                                @error('short_description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- clone_price end -->
                        <div class="col-sm-12 my-3">
                            <div class="form-group">
                                <label for="description" class="form-label">Description</label>
                                <input type="hidden" id="description" name="description" value="{{ old('description') }}">
                                <div
                                    id="campaign-description-editor"
                                    class="js-quill-editor"
                                    data-quill-theme="snow"
                                    data-quill-toolbar-preset="full"
                                    data-quill-target="#description"
                                    data-quill-placeholder="Write campaign description..."
                                    data-quill-initial-html="{{ e((string) old('description')) }}"
                                    style="min-height: 260px;"></div>
                                @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- clone_price end -->

                        <!-- Dynamic Content Section -->
                        <div class="col-12">
                            <h4 class="mt-4 mb-3">Dynamic Content Settings</h4>
                        </div>

                        <!-- Campaign Type -->
                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="campaign_type" class="form-label">Campaign Type *</label>
                                <select class="form-select rounded-0 @error('campaign_type') is-invalid @enderror" name="campaign_type">
                                    <option value="">Select Campaign Type</option>
                                    <option value="product" {{ old('campaign_type') == 'product' ? 'selected' : '' }}>Product</option>
                                    <option value="service" {{ old('campaign_type') == 'service' ? 'selected' : '' }}>Service</option>
                                    <option value="landing" {{ old('campaign_type') == 'landing' ? 'selected' : '' }}>Landing Page</option>
                                    <option value="promotional" {{ old('campaign_type') == 'promotional' ? 'selected' : '' }}>Promotional</option>
                                </select>
                                @error('campaign_type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Campaign Dates -->
                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control rounded-0 @error('start_date') is-invalid @enderror" name="start_date" value="{{ old('start_date') }}">
                                @error('start_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-4 mb-3">
                            <div class="form-group">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control rounded-0 @error('end_date') is-invalid @enderror" name="end_date" value="{{ old('end_date') }}">
                                @error('end_date')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-2 mb-3">
                            <div class="form-group">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control rounded-0 @error('sort_order') is-invalid @enderror" name="sort_order" value="{{ old('sort_order', 0) }}">
                                @error('sort_order')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Hero Section -->
                        <div class="col-12">
                            <h5 class="mt-3 mb-2">Hero Section</h5>
                        </div>

                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="hero_title" class="form-label">Hero Title</label>
                                <input type="text" class="form-control rounded-0 @error('hero_title') is-invalid @enderror" name="hero_title" value="{{ old('hero_title') }}" placeholder="Enter hero title">
                                @error('hero_title')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="hero_button_text" class="form-label">Hero Button Text</label>
                                <input type="text" class="form-control rounded-0 @error('hero_button_text') is-invalid @enderror" name="hero_button_text" value="{{ old('hero_button_text') }}" placeholder="e.g., Order Now">
                                @error('hero_button_text')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-12 mb-3">
                            <div class="form-group">
                                <label for="hero_subtitle" class="form-label">Hero Subtitle</label>
                                <textarea name="hero_subtitle" rows="3" class="form-control rounded-0 @error('hero_subtitle') is-invalid @enderror" placeholder="Enter hero subtitle">{{ old('hero_subtitle') }}</textarea>
                                @error('hero_subtitle')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>


                        <div class="col-sm-3 mb-3">
                            <div class="form-group">
                                <label class="d-block">Featured Campaign</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="is_featured" {{ old('is_featured') ? 'checked' : '' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="status" class="d-block">Status</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="status" checked>
                                    <span class="slider round"></span>
                                </label>
                                @error('status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->
                        
                        <!-- SEO Meta Tags Section -->
                        <div class="col-12">
                            @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
                                @php
                                    $model = new App\Models\Campaign();
                                @endphp
                                @include('backEnd.layouts.partials.seo-meta-form')
                            @endcomponent
                        </div>
                        <!-- End SEO Meta Tags Section -->

                    </form>

                </div> <!-- end card-body-->
            </div> <!-- end card-->
        </div>
    </div>
</div>

    </div>
  </div>
</div>
@endsection

@section('script')
<script src="{{ asset('admin/assets/js/admin-campaign-preview.js') }}"></script>
<script src="{{ asset('admin/assets/js/admin-campaign-content.js') }}"></script>
@endsection
