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
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="campaignForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="container-xxl flex-grow-1 container-p-y">

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
                            <input type="text" class="form-control @error('banner_title') is-invalid @enderror" name="banner_title" value="{{ $edit_data->banner_title}}"  id="banner_title" required="">
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
                            <input type="file" class="form-control @error('banner') is-invalid @enderror" name="banner" value="{{ $edit_data->banner }}"  id="banner" >
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
                            <input type="text" class="form-control @error('banner_title') is-invalid @enderror" name="banner_title" value="{{ $edit_data->banner_title}}"  id="banner_title">
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
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name}}"  id="name" required="">
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
                             <select class="form-select @error('product_id') is-invalid @enderror" value="{{ old('product_id') }}" name="product_id" data-placeholder="Choose ...">
                                <option value="">Select..</option>
                                @foreach($products as $value)
                                <option value="{{$value->id}}" @if($edit_data->product_id==$value->id) selected @endif>{{$value->name}}</option>
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
                    
                    <div class="col-sm-6 mb-3">
                        <div class="form-group">
                            <label for="image_one" class="form-label">Image One</label>
                            <input type="file" class="form-control @error('image_one') is-invalid @enderror" name="image_one" value="{{ $edit_data->image_one }}"  id="image_one" >
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
                            <input type="text" class="form-control @error('review') is-invalid @enderror" name="review" value="{{ $edit_data->review}}"  id="review" required="">
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
                                data-quill-toolbar-preset="mini"
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
                                data-quill-toolbar-preset="mini"
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
                            <select class="form-select @error('campaign_type') is-invalid @enderror" name="campaign_type">
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
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" name="start_date" value="{{ $edit_data->start_date ? $edit_data->start_date->format('Y-m-d') : '' }}">
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
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" name="end_date" value="{{ $edit_data->end_date ? $edit_data->end_date->format('Y-m-d') : '' }}">
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
                            <input type="number" class="form-control @error('sort_order') is-invalid @enderror" name="sort_order" value="{{ $edit_data->sort_order ?? 0 }}">
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
                            <input type="text" class="form-control @error('hero_title') is-invalid @enderror" name="hero_title" value="{{ $edit_data->hero_title }}">
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
                            <input type="text" class="form-control @error('hero_button_text') is-invalid @enderror" name="hero_button_text" value="{{ $edit_data->hero_button_text }}">
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
                            <textarea name="hero_subtitle" rows="3" class="form-control @error('hero_subtitle') is-invalid @enderror">{{ $edit_data->hero_subtitle }}</textarea>
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
                                <input type="checkbox" value="1" name="show_features" @if($edit_data->show_features)checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-3 mb-3">
                        <div class="form-group">
                            <label class="d-block">Show Benefits</label>
                            <label class="switch">
                                <input type="checkbox" value="1" name="show_benefits" @if($edit_data->show_benefits)checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-3 mb-3">
                        <div class="form-group">
                            <label class="d-block">Show Testimonials</label>
                            <label class="switch">
                                <input type="checkbox" value="1" name="show_testimonials" @if($edit_data->show_testimonials)checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-3 mb-3">
                        <div class="form-group">
                            <label class="d-block">Show FAQ</label>
                            <label class="switch">
                                <input type="checkbox" value="1" name="show_faq" @if($edit_data->show_faq)checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    <div class="col-sm-3 mb-3">
                        <div class="form-group">
                            <label class="d-block">Show CTA</label>
                            <label class="switch">
                                <input type="checkbox" value="1" name="show_cta" @if($edit_data->show_cta)checked @endif>
                                <span class="slider round"></span>
                            </label>
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
                        <input type="submit" class="btn btn-success" value="Submit">
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
