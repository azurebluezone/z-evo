@extends('backEnd.layouts.app')
@section('title','General Setting Update')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">General Setting Update</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="settingsForm">
              <i class="ri ri-save-line me-1"></i> Update
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{route('settings.index')}}" class="btn btn-primary rounded-pill">Manage</a>
                </div>
                <h4 class="page-title">General Setting Update</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('settings.update')}}" method="POST" class=row data-parsley-validate="" enctype="multipart/form-data" id="settingsForm">
                        @csrf
                        <input type="hidden" name="id" value="{{$edit_data->id}}">
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="name" class="form-label">Name *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ $edit_data->name}}" id="name" required="">
                                @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col-end -->
                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="white_logo" class="form-label">White Logo *</label>
                                <input type="file" class="form-control @error('white_logo') is-invalid @enderror" name="white_logo" value="{{ old('white_logo') }}" id="white_logo">
                                @php
                                    $whiteLogoPath = preg_replace('~^public/~', '', (string) ($edit_data->white_logo ?? ''));
                                    $whiteLogoPath = ltrim($whiteLogoPath, '/');
                                @endphp
                                @if($whiteLogoPath)
                                    <img src="{{ asset($whiteLogoPath) }}" class="edit-image" alt="" onerror="this.style.display='none'">
                                @endif
                                @error('white_logo')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->
                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="dark_logo" class="form-label">Dark Logo *</label>
                                <input type="file" class="form-control @error('dark_logo') is-invalid @enderror" name="dark_logo" value="{{ old('dark_logo') }}" id="dark_logo">
                                @php
                                    $darkLogoPath = preg_replace('~^public/~', '', (string) ($edit_data->dark_logo ?? ''));
                                    $darkLogoPath = ltrim($darkLogoPath, '/');
                                @endphp
                                @if($darkLogoPath)
                                    <img src="{{ asset($darkLogoPath) }}" class="edit-image" alt="" onerror="this.style.display='none'">
                                @endif
                                @error('dark_logo')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>
                        <!-- col end -->

                        <div class="col-sm-6 mb-3">
                            <div class="form-group">
                                <label for="favicon" class="form-label">Favicon Logo *</label>
                                <input type="file" class="form-control @error('favicon') is-invalid @enderror" name="favicon" value="{{ old('favicon') }}" id="favicon">
                                @php
                                    $faviconPath = preg_replace('~^public/~', '', (string) ($edit_data->favicon ?? ''));
                                    $faviconPath = ltrim($faviconPath, '/');
                                @endphp
                                @if($faviconPath)
                                    <img src="{{ asset($faviconPath) }}" class="edit-image" alt="" onerror="this.style.display='none'">
                                @endif
                                @error('favicon')
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

                        <!-- SEO Meta Tags Section -->
                        <div class="col-sm-12">
                            @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
                                @php
                                    $model = $edit_data;
                                @endphp
                                @include('backEnd.layouts.partials.seo-meta-form')
                            @endcomponent
                        </div>
                        <!-- End SEO Meta Tags Section -->

                        <!-- Facebook Tracking Notice -->


                        <div>
                            <input type="submit" class="btn btn-success" value="Submit">
                        </div>

                    </form>

                </div> <!-- end card-body-->
            </div> <!-- end card-->
        </div> <!-- end col-->
    </div>
</div>

    </div>
  </div>
</div>
@endsection

