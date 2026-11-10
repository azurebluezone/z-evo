@extends('backEnd.layouts.app')

@section('title', 'Prompt')

@section('content')
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h4 class="mb-0">AI Prompt Generator</h4>
    <div class="d-flex flex-wrap gap-2">
      <x-admin.button variant="outline-secondary" size="sm" type="button" id="clearBtn">Clear</x-admin.button>
      <x-admin.button variant="primary" size="sm" type="button" id="generateBtn">Generate</x-admin.button>
    </div>
  </div>

  <div class="row">
    <div class="col-12 col-xl-7">
      <div class="card mb-3">
        <div class="card-body">
          <div class="row">
            <div class="col-12">
              <div class="mb-3">
                <label for="imageType" class="form-label">Image Type</label>
                <select id="imageType" class="form-select">
                  <option value="">Select..</option>
                  <optgroup label="Banner & Hero">
                    <option value="Hero Slider Banner" data-width="1920" data-height="600">Hero Slider Banner (1920x600)</option>
                    <option value="Banner Background Image" data-width="1200" data-height="400">Banner Background Image (1200x400)</option>
                    <option value="Hero Video Background" data-width="1920" data-height="1080">Hero Video Background (1920x1080)</option>
                  </optgroup>
                  <optgroup label="Promo">
                    <option value="Promo Banner Left" data-width="600" data-height="300">Promo Banner Left (600x300)</option>
                    <option value="Promo Banner Right" data-width="600" data-height="300">Promo Banner Right (600x300)</option>
                  </optgroup>
                  <optgroup label="Categories">
                    <option value="Category Banner Icon" data-width="150" data-height="150">Category Banner Icon (150x150)</option>
                    <option value="Category Card Image" data-width="300" data-height="200">Category Card Image (300x200)</option>
                    <option value="Collection Banner" data-width="800" data-height="400">Collection Banner (800x400)</option>
                  </optgroup>
                  <optgroup label="Products">
                    <option value="Product Main Image" data-width="800" data-height="800">Product Main Image (800x800)</option>
                    <option value="Product Thumbnail" data-width="300" data-height="300">Product Thumbnail (300x300)</option>
                    <option value="Product Gallery Image" data-width="1200" data-height="1200">Product Gallery Image (1200x1200)</option>
                    <option value="Featured Product Banner" data-width="800" data-height="400">Featured Product Banner (800x400)</option>
                    <option value="Flash Sale Banner" data-width="1200" data-height="300">Flash Sale Banner (1200x300)</option>
                  </optgroup>
                  <optgroup label="Video">
                    <option value="Product Video Thumbnail" data-width="1280" data-height="720">Product Video Thumbnail (1280x720)</option>
                    <option value="Product Demo Video" data-width="1920" data-height="1080">Product Demo Video (1920x1080)</option>
                    <option value="Campaign Hero Video" data-width="1920" data-height="1080">Campaign Hero Video (1920x1080)</option>
                  </optgroup>
                  <optgroup label="Company">
                    <option value="About Background Image" data-width="1200" data-height="600">About Background Image (1200x600)</option>
                    <option value="Team Member Image" data-width="150" data-height="150">Team Member Image (150x150)</option>
                    <option value="Company Logo" data-width="300" data-height="150">Company Logo (300x150)</option>
                    <option value="Brand Logo" data-width="200" data-height="100">Brand Logo (200x100)</option>
                  </optgroup>
                  <optgroup label="Other">
                    <option value="SEO Image" data-width="1200" data-height="630">SEO Image (1200x630)</option>
                    <option value="Favicon Image" data-width="32" data-height="32">Favicon Image (32x32)</option>
                  </optgroup>
                </select>
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="width" class="form-label">Width (px)</label>
                <input type="number" class="form-control" id="width" min="1" placeholder="1920">
              </div>
            </div>

            <div class="col-md-6">
              <div class="mb-3">
                <label for="height" class="form-label">Height (px)</label>
                <input type="number" class="form-control" id="height" min="1" placeholder="600">
              </div>
            </div>

            <div class="col-12">
              <div class="mb-3">
                <label class="form-label">Quality Options</label>
                <div class="row g-2">
                  <div class="col-12 col-sm-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="highRes" checked>
                      <label class="form-check-label" for="highRes">High Resolution</label>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="professionalLighting" checked>
                      <label class="form-check-label" for="professionalLighting">Professional Lighting</label>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="sharpDetails" checked>
                      <label class="form-check-label" for="sharpDetails">Sharp Details</label>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="colorGrading" checked>
                      <label class="form-check-label" for="colorGrading">Color Grading</label>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="webOptimized" checked>
                      <label class="form-check-label" for="webOptimized">Web Optimized</label>
                    </div>
                  </div>
                  <div class="col-12 col-sm-6">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="modernDesign" checked>
                      <label class="form-check-label" for="modernDesign">Modern Design</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="mb-3">
                <label for="stylePreset" class="form-label">Style Preset</label>
                <select id="stylePreset" class="form-select">
                  <option value="professional">Professional & Corporate</option>
                  <option value="modern">Modern & Minimalist</option>
                  <option value="vibrant">Vibrant & Colorful</option>
                  <option value="elegant">Elegant & Sophisticated</option>
                  <option value="tech">Tech & Futuristic</option>
                  <option value="natural">Natural & Organic</option>
                </select>
              </div>
            </div>

            <div class="col-12">
              <div class="mb-0">
                <label for="promptDescription" class="form-label">Description</label>
                <textarea class="form-control" id="promptDescription" rows="5" placeholder="Describe your image..."></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-5">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <h5 class="card-title mb-0">Generated Prompt</h5>
            <x-admin.button variant="outline-success" size="sm" type="button" id="copyBtn">Copy</x-admin.button>
          </div>

          <pre id="generatedPrompt" class="mb-0" style="white-space: pre-wrap; word-break: break-word; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 13px; line-height: 1.6;">Your prompt will appear here...</pre>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('script')
<script src="{{ asset('admin/assets/js/admin-page-prompt-index.js') }}"></script>
@endpush
