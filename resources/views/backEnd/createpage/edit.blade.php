@extends('backEnd.layouts.app')
@section('title','Page Edit')
@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Page Edit</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('pages.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="pagesForm">
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
                    <a href="{{route('pages.index')}}" class="btn btn-primary rounded-pill">Manage</a>
                </div>
                <h4 class="page-title">Page Edit</h4>
            </div>
        </div>
    </div>       
    <!-- end page title --> 
   <div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form action="{{route('pages.update')}}" method="POST" class=row data-parsley-validate=""  enctype="multipart/form-data" id="pagesForm">
                    @csrf
                    <input type="hidden" value="{{$edit_data->id}}" name="hidden_id">
                    <div class="col-sm-6">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Name *</label>
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
                            <label for="title" class="form-label">Title  *</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ $edit_data->title}}"  id="title" required="">
                            @error('title')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col-end -->
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label for="description" class="form-label">Description*</label>
                            <textarea class="form-control d-none @error('description')  is-invalid @enderror" name="description" rows="6" id="description" required="">{{ old('description', $edit_data->description) }}</textarea>
                            <div
                                id="description-editor"
                                class="js-quill-editor"
                                data-quill-theme="snow"
                                data-quill-toolbar-preset="full"
                                data-quill-target="#description"
                                data-quill-placeholder="Write page description..."
                                data-quill-initial-html="{{ e((string) old('description', $edit_data->description)) }}"
                                style="min-height: 260px;"></div>
                            @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                    <!-- col-end -->
                    @php
                        $model = $edit_data;
                    @endphp
                    @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
                        @include('backEnd.layouts.partials.seo-meta-form')
                    @endcomponent
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
                    <div>
                        {{-- Was <input type="submit" class="btn btn-success">. Node-waves
                             rewrites input buttons to class "waves-button-input" and
                             strips the btn styling, so it rendered as an unstyled box.
                             A <button> element is not touched by that path. --}}
                        <x-admin.button variant="success" type="submit">
                            <i class="ri ri-save-line me-1"></i> Submit
                        </x-admin.button>
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
