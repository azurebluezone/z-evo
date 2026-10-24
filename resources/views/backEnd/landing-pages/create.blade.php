@extends('backEnd.layouts.app')
@section('title')
Create Landing Page
@endsection

@section('content')

<div class="row">
  <div class="col-12">
          <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Landing Pages Create.Blade</h5>
          <div class="d-flex align-items-center gap-2">
            <a href="{{ route('landing-pages.index') }}" class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Manage
            </a>
            <x-admin.button variant="primary" shape="square" ripple="true" type="submit" form="landing-pagesForm">
              <i class="ri ri-save-line me-1"></i> Submit
            </x-admin.button>
          </div>
        </div>
      </div>

<div class="app-ecommerce">
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2">
                <h4 class="mb-0">Create Landing Page</h4>
                <a href="{{ route('landing-pages.index') }}" class="btn btn-primary">Manage</a>
            </div>

            <div class="card">
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('landing-pages.store') }}" method="POST" class="row g-3" enctype="multipart/form-data" id="landing-pagesForm">
                        @csrf

                        <div class="col-12 col-lg-8">
                            <label for="title" class="form-label">Page Title *</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}">
                            @error('title')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-4">
                            <label for="template_id" class="form-label">Template *</label>
                            <select class="form-select @error('template_id') is-invalid @enderror" id="template_id" name="template_id">
                                <option value="">Select Template</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                        {{ $template->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('template_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <!-- Template Preview Card -->
                        <div class="col-12" id="template-preview-card" style="display:none;">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Template: <span id="preview-template-name"></span></h5>
                                    <p class="card-text text-muted" id="preview-template-description"></p>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">View File:</small>
                                            <code id="preview-view-name" class="d-block mb-2"></code>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <x-admin.button variant="outline-primary" size="sm" type="button" id="btn-preview-template">
                                                <i class="fas fa-eye"></i> Preview Template
                                            </x-admin.button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="subtitle" class="form-label">Subtitle</label>
                            <input type="text" class="form-control @error('subtitle') is-invalid @enderror" id="subtitle" name="subtitle" value="{{ old('subtitle') }}">
                            @error('subtitle')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="6">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="cta_text" class="form-label">CTA Button Text</label>
                            <input type="text" class="form-control @error('cta_text') is-invalid @enderror" id="cta_text" name="cta_text" value="{{ old('cta_text') }}">
                            @error('cta_text')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="cta_link" class="form-label">CTA Button Link</label>
                            <input type="url" class="form-control @error('cta_link') is-invalid @enderror" id="cta_link" name="cta_link" value="{{ old('cta_link') }}">
                            @error('cta_link')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="image" class="form-label">Hero Image</label>
                            <input type="file" class="form-control js-dropify @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                            @error('image')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">Recommended size: 800x600px, Max: 2MB</small>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label d-block">Status</label>
                            <label class="switch">
                                <input type="checkbox" id="status" name="status" checked>
                                <span class="switch-toggle-slider">
                                    <span class="switch-on"></span>
                                    <span class="switch-off"></span>
                                </span>
                            </label>
                        </div>

                        <!-- Dynamic Extra Fields Section -->
                        <div class="col-12" id="extra-fields-container">
                            <!-- Extra fields will be dynamically inserted here based on template selection -->
                        </div>

                        @php
                            $model = new App\Models\LandingPage();
                        @endphp
                        @component('backEnd.layouts.partials.collapse-card', ['title' => 'SEO Meta Tags', 'collapseId' => 'seoMetaCollapse', 'toggleText' => 'SEO'])
                            @include('backEnd.layouts.partials.seo-meta-form')
                        @endcomponent

                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <x-admin.button variant="success" type="submit">Submit</x-admin.button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Template preview functionality
    $('#template_id').on('change', function() {
        var templateId = $(this).val();
        
        if (templateId) {
            // Fetch template configuration
            $.get('{{ route("landing-pages.template-config", ":id") }}'.replace(':id', templateId), function(data) {
                // Show preview card
                $('#preview-template-name').text(data.name);
                $('#preview-template-description').text(data.description);
                $('#preview-view-name').text('templates.' + data.view_name + '.blade.php');
                $('#template-preview-card').fadeIn();
                
                // Render dynamic extra fields
                renderExtraFields(data.config);
            }).fail(function() {
                $('#template-preview-card').hide();
                $('#extra-fields-container').empty();
            });
        } else {
            $('#template-preview-card').hide();
            $('#extra-fields-container').empty();
        }
    });

    function renderExtraFields(config) {
        var html = '<div class="card"><div class="card-header"><h5 class="mb-0">Template Extra Fields</h5></div><div class="card-body">';
        
        if (!config || !config.extra_fields || config.extra_fields.length === 0) {
            html += '<p class="text-muted mb-0">No extra fields for this template.</p>';
        } else {
            html += '<div class="row">';
            
            config.extra_fields.forEach(function(field) {
                html += '<div class="col-md-6 mb-3">';
                
                if (field === 'old_price' || field === 'new_price') {
                    html += '<label class="form-label">' + field.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</label>';
                    html += '<input type="number" step="0.01" class="form-control" name="extra_fields[' + field + ']" placeholder="Enter ' + field.replace('_', ' ') + '">';
                } else if (field === 'countdown_date') {
                    html += '<label class="form-label">Countdown End Date & Time</label>';
                    html += '<input type="datetime-local" class="form-control" name="extra_fields[' + field + ']">';
                } else if (field === 'contact_phone') {
                    html += '<label class="form-label">Contact Phone Number</label>';
                    html += '<input type="text" class="form-control" name="extra_fields[' + field + ']" placeholder="+880...">';
                } else if (field === 'video_url') {
                    html += '<label class="form-label">Video URL (YouTube/Vimeo)</label>';
                    html += '<input type="url" class="form-control" name="extra_fields[' + field + ']" placeholder="https://youtube.com/...">';
                } else if (field === 'email') {
                    html += '<label class="form-label">Email Address</label>';
                    html += '<input type="email" class="form-control" name="extra_fields[' + field + ']" placeholder="info@example.com">';
                } else if (field === 'show_benefits' || field === 'show_testimonials') {
                    html += '<div class="form-check form-switch">';
                    html += '<input class="form-check-input" type="checkbox" name="extra_fields[' + field + ']" id="' + field + '" checked>';
                    html += '<label class="form-check-label" for="' + field + '">' + field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</label>';
                    html += '</div>';
                } else {
                    html += '<label class="form-label">' + field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</label>';
                    html += '<textarea class="form-control" name="extra_fields[' + field + ']" rows="3" placeholder="Enter ' + field.replace(/_/g, ' ') + '"></textarea>';
                }
                
                html += '</div>';
            });
            
            html += '</div>';
        }
        
        html += '</div></div>';
        $('#extra-fields-container').html(html);
    }

    // Preview template button click
    $('#btn-preview-template').on('click', function() {
        var viewName = $('#preview-view-name').text().replace('.blade.php', '');
        window.open('/templates/' + viewName, '_blank');
    });

    // Preview image before upload
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                console.log('Image selected:', file.name);
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>
@endpush
