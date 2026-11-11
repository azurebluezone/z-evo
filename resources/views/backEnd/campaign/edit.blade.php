@extends('backEnd.layouts.app')
@section('title','Landing Page Edit')

@section('css')
@endsection
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Landing Page Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('campaign.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <a href="{{ route('campaign.preview', $edit_data->id) }}" class="btn btn-outline-primary btn-square waves-effect" target="_blank" rel="noopener">
              <i class="ri ri-eye-line me-1"></i> Preview
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="campaignForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="w-100">

    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
                <h4 class="mb-0">Landing Page Edit</h4>
                <a href="{{ route('campaign.index') }}" class="btn btn-primary">Manage</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('campaign.update') }}" method="POST" class="row g-3" data-parsley-validate=""  enctype="multipart/form-data" name="editForm" id="campaignForm">
                    @csrf
                    <input type="hidden" value="{{$edit_data->id}}" name="hidden_id">


                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="banner_title" class="form-label">Banner Title *</label>
                            <input type="text" class="form-control rounded-0 @error('banner_title') is-invalid @enderror" name="banner_title" value="{{ $edit_data->banner_title}}"  id="banner_title">
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
                            <label for="banner" class="form-label">Image</label>
                            <input type="file" class="form-control rounded-0 @error('banner') is-invalid @enderror" name="banner" value="{{ $edit_data->banner }}"  id="banner" >
                            @php
                                $bannerPath = preg_replace('~^public/~', '', (string) ($edit_data->banner ?? ''));
                                $bannerPath = ltrim($bannerPath, '/');
                            @endphp
                            @if($bannerPath)
                                <img src="{{ asset($bannerPath) }}" alt="" class="edit-image" onerror="this.style.display='none'">
                            @endif
                            @error('banner')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col end -->

                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="banner_title" class="form-label">Banner Title</label>
                            <input type="text" class="form-control rounded-0 @error('banner_title') is-invalid @enderror" name="banner_title" value="{{ $edit_data->banner_title}}"  id="banner_title">
                            @error('banner_title')
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
                            <input type="text" class="form-control rounded-0 @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name}}"  id="name">
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
                                @php $selectedProduct = old('product_id', $edit_data->product_id); @endphp
                                @foreach($products as $value)
                                <option value="{{$value->id}}"
                                    data-price="{{ number_format((float) $value->new_price, 2, '.', '') }}"
                                    data-stock="{{ (int) $value->stock }}"
                                    {{ (string) $selectedProduct === (string) $value->id ? 'selected' : '' }}>
                                    {{$value->name}} — ৳{{ number_format((float) $value->new_price, 2) }} · Stock {{ (int) $value->stock }}{{ $value->status ? '' : ' (inactive)' }}
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
                            <select class="select2 form-select rounded-0 @error('template_id') is-invalid @enderror" name="template_id" id="template_id" data-placeholder="Choose Template...">
                                <option value="">Select Template..</option>
                                @foreach($templates as $template)
                                <option value="{{ $template->id }}" @if((string) old('template_id', $edit_data->template_id) === (string) $template->id) selected @endif>
                                    {{ $template->name }} - {{ $template->description }}
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
                                        <input type="checkbox" class="form-check-input" name="campaign_price_enabled" value="1" id="campaign_price_enabled" {{ old('campaign_price_enabled', $edit_data->campaign_price_enabled) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="campaign_price_enabled">Enable campaign price</label>
                                    </div>
                                    <small class="text-muted">Off means the landing page uses the product price.</small>
                                </div>

                                <div class="col-md-4">
                                    <label for="campaign_price" class="form-label">Offer price (BDT)</label>
                                    <input type="number" step="0.01" min="0" class="form-control rounded-0 @error('campaign_price') is-invalid @enderror" name="campaign_price" value="{{ old('campaign_price', $edit_data->campaign_price) }}" id="campaign_price">
                                    <small class="text-muted">Fixed price for a simple product.</small>
                                    @error('campaign_price')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label for="campaign_discount_percent" class="form-label">Discount percent</label>
                                    <input type="number" step="0.01" min="0.01" max="99.99" class="form-control rounded-0 @error('campaign_discount_percent') is-invalid @enderror" name="campaign_discount_percent" value="{{ old('campaign_discount_percent', $edit_data->campaign_discount_percent) }}" id="campaign_discount_percent">
                                    <small class="text-muted">Percent wins over the fixed price; use it for variant products.</small>
                                    @error('campaign_discount_percent')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" name="allow_coupon" value="1" id="allow_coupon" {{ old('allow_coupon', $edit_data->allow_coupon) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="allow_coupon">Allow coupons on this campaign</label>
                                    </div>
                                    <small class="text-muted">Default off. If enabled, coupons calculate on the campaign price.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- col end -->
                    
                    @include('backEnd.campaign.partials.content-fields', ['edit_data' => $edit_data, 'templates' => $templates])

                    <div class="col-sm-6 mb-3">
                        <div class="form-group">
                            <label for="image_one" class="form-label">Image One</label>
                            <input type="file" class="form-control rounded-0 @error('image_one') is-invalid @enderror" name="image_one" value="{{ $edit_data->image_one }}"  id="image_one" >
                            @php
                                $imageOnePath = preg_replace('~^public/~', '', (string) ($edit_data->image_one ?? ''));
                                $imageOnePath = ltrim($imageOnePath, '/');
                            @endphp
                            @if($imageOnePath)
                                <img src="{{ asset($imageOnePath) }}" alt="" class="edit-image" onerror="this.style.display='none'">
                            @endif
                            @error('image_one')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col end -->
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
                        <div class="product_img mt-3">
                            @foreach($edit_data->images as $image)
                            @php
                                $imgPath = preg_replace('~^public/~', '', (string) ($image->image ?? ''));
                                $imgPath = ltrim($imgPath, '/');
                            @endphp
                            @if($imgPath)
                            <img src="{{ asset($imgPath) }}" class="edit-image border" alt="" onerror="this.style.display='none'" />
                            <button type="submit" form="fmRemoveCampaignImage{{ $image->id }}" class="btn btn-sm btn-danger waves-effect waves-light delete-confirm" data-confirm-title="Remove this photo?" data-confirm-text="The row and its file are deleted immediately." data-confirm-ok="Yes, remove" title="Remove photo"><i class="ri ri-close-line"></i></button>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    <!-- col end -->

                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="review" class="form-label">Review *</label>
                            <input type="text" class="form-control rounded-0 @error('review') is-invalid @enderror" name="review" value="{{ $edit_data->review}}"  id="review">
                            @error('review')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col-end -->
                    <div class="col-sm-12 mb-3">
                        <div class="form-group">
                            <label for="short_description" class="form-label">Short Description</label>
                            <input type="hidden" id="short_description" name="short_description" value="{{ old('short_description', $edit_data->short_description) }}">
                            <div
                                id="campaign-short-description-editor"
                                class="js-quill-editor"
                                data-quill-theme="snow"
                                data-quill-toolbar-preset="full"
                                data-quill-target="#short_description"
                                data-quill-placeholder="Enter short description here..."
                                data-quill-initial-html="{{ e((string) old('short_description', $edit_data->short_description)) }}"
                                style="min-height: 140px;"></div>
                            @error('short_description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col end -->

                    <div class="col-sm-12 mb-3">
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <input type="hidden" id="description" name="description" value="{{ old('description', $edit_data->description) }}">
                            <div
                                id="campaign-description-editor"
                                class="js-quill-editor"
                                data-quill-theme="snow"
                                data-quill-toolbar-preset="full"
                                data-quill-target="#description"
                                data-quill-placeholder="Write campaign description..."
                                data-quill-initial-html="{{ e((string) old('description', $edit_data->description)) }}"
                                style="min-height: 260px;"></div>
                            @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col end -->

                    <div class="col-sm-6 mb-3">
                        <div class="form-group">
                            <label for="status" class="d-block">Status</label>
                            <label class="switch">
                                <input type="checkbox" value="1" name="status" @if($edit_data->status==1)checked @endif>
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

                    <!-- Dynamic Content Section -->
                    <div class="col-12">
                        <h4 class="mt-4 mb-3">Dynamic Content Settings</h4>
                    </div>

                    <!-- Campaign Type -->
                    <div class="col-sm-6 mb-3">
                        <div class="form-group">
                            <label for="campaign_type" class="form-label">Campaign Type *</label>
                            <select class="form-select rounded-0 @error('campaign_type') is-invalid @enderror" name="campaign_type">
                                <option value="product" @if($edit_data->campaign_type == 'product') selected @endif>Product</option>
                                <option value="service" @if($edit_data->campaign_type == 'service') selected @endif>Service</option>
                                <option value="landing" @if($edit_data->campaign_type == 'landing') selected @endif>Landing Page</option>
                                <option value="promotional" @if($edit_data->campaign_type == 'promotional') selected @endif>Promotional</option>
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
                            <input type="date" class="form-control rounded-0 @error('start_date') is-invalid @enderror" name="start_date" value="{{ $edit_data->start_date ? $edit_data->start_date->format('Y-m-d') : '' }}">
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
                            <input type="date" class="form-control rounded-0 @error('end_date') is-invalid @enderror" name="end_date" value="{{ $edit_data->end_date ? $edit_data->end_date->format('Y-m-d') : '' }}">
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
                            <input type="number" class="form-control rounded-0 @error('sort_order') is-invalid @enderror" name="sort_order" value="{{ $edit_data->sort_order ?? 0 }}">
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
                            <input type="text" class="form-control rounded-0 @error('hero_title') is-invalid @enderror" name="hero_title" value="{{ $edit_data->hero_title }}">
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
                            <input type="text" class="form-control rounded-0 @error('hero_button_text') is-invalid @enderror" name="hero_button_text" value="{{ $edit_data->hero_button_text }}">
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
                            <textarea name="hero_subtitle" rows="3" class="form-control rounded-0 @error('hero_subtitle') is-invalid @enderror">{{ $edit_data->hero_subtitle }}</textarea>
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
                                <input type="checkbox" value="1" name="is_featured" @if($edit_data->is_featured)checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-6 mb-3">
                        <div class="form-group">
                            <label for="status" class="d-block">Status</label>
                            <label class="switch">
                                <input type="checkbox" value="1" name="status" @if($edit_data->status==1)checked @endif>
                                <span class="slider round"></span>
                            </label>
                            @error('status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <!-- SEO Meta Tags Section -->
                    <div class="col-sm-12 mt-4">
                        @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
                            @php
                                $model = $edit_data;
                            @endphp
                            @include('backEnd.layouts.partials.seo-meta-form')
                        @endcomponent
                    </div>
                    <!-- End SEO Meta Tags Section -->
                    
                    <div>
                        <x-admin.button variant="success" type="submit">Submit</x-admin.button>
                    </div>

                </form>

                {{-- POST + CSRF delete forms live outside the edit form: the
                     old remove control was a GET <a href>, which a third-party
                     page could trigger with an <img> tag. --}}
                @foreach($edit_data->images as $image)
                    <form id="fmRemoveCampaignImage{{ $image->id }}" method="POST" action="{{ route('campaign.image.destroy') }}" class="d-none">
                        @csrf
                        <input type="hidden" name="id" value="{{ $image->id }}">
                    </form>
                @endforeach

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
