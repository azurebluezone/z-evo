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
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('campaign.store') }}" method="POST" class="row g-3" data-parsley-validate="" enctype="multipart/form-data" id="campaignForm">
                        @csrf

                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label for="banner_title" class="form-label">Banner Title *</label>
                                <input type="text" class="form-control @error('banner_title') is-invalid @enderror" name="banner_title" value="{{ old('banner_title') }}" id="banner_title" required="">

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
                                <input type="file" class="form-control @error('banner') is-invalid @enderror " name="banner" value="{{ old('banner') }}" id="banner" required="">
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
                                <input type="text" class="form-control @error('video') is-invalid @enderror" name="video" value="{{ old('video') }}" id="video">
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
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" id="name" required="">
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
                                <select class="select2 form-control @error('product_id') is-invalid @enderror" value="{{ old('product_id') }}" name="product_id" data-placeholder="Choose ...">

                                    <option value="">Select..</option>
                                    @foreach($products as $value)
                                    <option value="{{$value->id}}">{{$value->name}}</option>
                                    @endforeach

                                </select>
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
                                <select class="select2 form-control @error('template_id') is-invalid @enderror" value="{{ old('template_id') }}" name="template_id" data-placeholder="Choose Template...">
                                    <option value="">Select Template..</option>
                                    @foreach($templates as $template)
                                    <option value="{{$template->id}}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                        {{$template->name}} - {{$template->description}}
                                    </option>
                                    @endforeach
                                </select>
                                @error('template_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->

                        <div class="col-sm-12 mb-3">
                            <div class="form-group">
                                <label for="image_one" class="form-label">Image One*</label>
                                <input type="file" class="form-control @error('image_one') is-invalid @enderror " name="image_one" value="{{ old('image_one') }}" id="image_one" required="">
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
                                <input type="file" class="form-control @error('image_two') is-invalid @enderror " name="image_two" value="{{ old('image_two') }}" id="image_two">
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
                                <input type="file" class="form-control @error('image_three') is-invalid @enderror " name="image_three" value="{{ old('image_three') }}" id="image_three">
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
                                <input type="text" class="form-control @error('review') is-invalid @enderror" name="review" value="{{ old('review') }}" id="review" required="">
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
                                <input type="file" name="image[]" class="form-control @error('image') is-invalid @enderror" />
                                <x-admin.button variant="outline-success" class="btn-increment" type="button"><i class="ri ri-add-line"></i></x-admin.button>
                                @error('image')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="clone hide" style="display: none;">
                                <div class="input-group mt-2">
                                    <input type="file" name="image[]" class="form-control" />
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
                                    data-quill-toolbar-preset="mini"
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
                                    data-quill-toolbar-preset="mini"
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
                                <select class="form-select @error('campaign_type') is-invalid @enderror" name="campaign_type">
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
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror" name="start_date" value="{{ old('start_date') }}">
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
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror" name="end_date" value="{{ old('end_date') }}">
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
                                <input type="number" class="form-control @error('sort_order') is-invalid @enderror" name="sort_order" value="{{ old('sort_order', 0) }}">
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
                                <input type="text" class="form-control @error('hero_title') is-invalid @enderror" name="hero_title" value="{{ old('hero_title') }}" placeholder="Enter hero title">
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
                                <input type="text" class="form-control @error('hero_button_text') is-invalid @enderror" name="hero_button_text" value="{{ old('hero_button_text') }}" placeholder="e.g., Order Now">
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
                                <textarea name="hero_subtitle" rows="3" class="form-control @error('hero_subtitle') is-invalid @enderror" placeholder="Enter hero subtitle">{{ old('hero_subtitle') }}</textarea>
                                @error('hero_subtitle')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Section Visibility -->
                        <div class="col-12">
                            <h5 class="mt-3 mb-2">Section Visibility</h5>
                        </div>

                        <div class="col-sm-3 mb-3">
                            <div class="form-group">
                                <label class="d-block">Show Features</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="show_features" {{ old('show_features') ? 'checked' : 'checked' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-3">
                            <div class="form-group">
                                <label class="d-block">Show Benefits</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="show_benefits" {{ old('show_benefits') ? 'checked' : 'checked' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-3">
                            <div class="form-group">
                                <label class="d-block">Show Testimonials</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="show_testimonials" {{ old('show_testimonials') ? 'checked' : 'checked' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-3">
                            <div class="form-group">
                                <label class="d-block">Show FAQ</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="show_faq" {{ old('show_faq') ? 'checked' : 'checked' }}>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>

                        <div class="col-sm-3 mb-3">
                            <div class="form-group">
                                <label class="d-block">Show CTA</label>
                                <label class="switch">
                                    <input type="checkbox" value="1" name="show_cta" {{ old('show_cta') ? 'checked' : 'checked' }}>
                                    <span class="slider round"></span>
                                </label>
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
