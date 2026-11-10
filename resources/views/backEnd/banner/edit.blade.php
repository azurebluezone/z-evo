@extends('backEnd.layouts.app')
@section('title','Banner Edit')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Banner Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('banners.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="bannersForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
            <h4 class="mb-0">Banner Edit</h4>
            <a href="{{ route('banners.index') }}" class="btn btn-outline-secondary waves-effect">
                <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('banners.update') }}" method="POST" class="row g-3" data-parsley-validate="" enctype="multipart/form-data" id="bannersForm">
                    @csrf
                    <input type="hidden" value="{{ $edit_data->id }}" name="id">

                    <div class="col-12">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ $edit_data->title }}" id="title">
                        @error('title')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="link" class="form-label">Link *</label>
                        <input type="text" class="form-control @error('link') is-invalid @enderror" name="link" value="{{ $edit_data->link }}" id="link">
                        @error('link')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="media_type" class="form-label">Media Type</label>
                        <select class="form-control @error('media_type') is-invalid @enderror" name="media_type" id="media_type" data-has-video='@json(!empty($edit_data->video))'>
                            <option value="image" {{ ($edit_data->media_type ?? 'image') === 'image' ? 'selected' : '' }}>Image</option>
                            <option value="video" {{ ($edit_data->media_type ?? 'image') === 'video' ? 'selected' : '' }}>Video</option>
                        </select>
                        @error('media_type')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12" id="imageWrap">
                        <label for="image" class="form-label">Image *</label>
                        <input type="file" class="form-control js-dropify @error('image') is-invalid @enderror" name="image" id="image" data-default-file="{{ !empty($edit_data->image) ? asset($edit_data->image) : '' }}">
                        @error('image')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12" id="videoFileWrap" style="display:none;">
                        <label for="video" class="form-label">Video File</label>
                        <input type="file" class="form-control js-dropify @error('video') is-invalid @enderror" name="video" id="video" accept="video/*">
                        @error('video')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    @if(!empty($edit_data->video))
                    <div class="col-12">
                        <label class="form-label d-block">Current Video</label>
                        <a href="{{ asset(ltrim(preg_replace('~^public/~', '', (string) $edit_data->video), '/')) }}" target="_blank">{{ $edit_data->video }}</a>
                    </div>
                    @endif

                    <div class="col-12 col-md-6">
                        <label class="form-label d-block">Status</label>
                        <label class="switch">
                            <input type="checkbox" class="switch-input" value="1" name="status" @if((int) $edit_data->status === 1) checked @endif>
                            <span class="switch-toggle-slider">
                                <span class="switch-on"></span>
                                <span class="switch-off"></span>
                            </span>
                        </label>
                        @error('status')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <div class="col-12">
                        @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
                            @php
                                $model = $edit_data;
                            @endphp
                            @include('backEnd.layouts.partials.seo-meta-form')
                        @endcomponent
                    </div>

                    <div class="col-12">
                        <x-admin.button variant="success" ripple="true" type="submit">
                            <i class="ri ri-save-line me-1"></i> Update Banner
                        </x-admin.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    </div>
  </div>
</div>
@endsection

@push('script')
{{-- banner media toggle + has-video validation live in admin-app-shared.js (read data-has-video below) --}}
@endpush
