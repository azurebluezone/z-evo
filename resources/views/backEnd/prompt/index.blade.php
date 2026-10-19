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
<script>
  const state = {
    imageType: null,
    width: null,
    height: null,
    description: '',
    location: '',
    qualityOptions: {},
    stylePreset: 'professional'
  };

  const predefinedPrompts = {
    'Hero Slider Banner': 'Professional hero slider banner with modern gradient design, featuring dynamic composition and eye-catching visuals, ample copy space for text overlay, high-end e-commerce aesthetic, engaging call-to-action area',
    'Banner Background Image': 'A stunning hero banner background with modern gradient design, featuring abstract flowing shapes and dynamic composition, professional color palette with blue and white tones, ample negative space for text overlay, high-end corporate aesthetic',
    'Hero Video Background': 'Cinematic hero section video background with smooth motion graphics, professional color grading, seamless loop capability, brand storytelling elements, captivating visual narrative, 16:9 aspect ratio optimized for web display',
    'Product Video Thumbnail': 'Engaging product video thumbnail with play button overlay, compelling frame selection, preview of product in action, click-inducing composition, 16:9 video format',
    'Product Demo Video': 'Professional product demonstration video with clear step-by-step visuals, benefit-focused storytelling, high production quality, customer pain point resolution, 16:9 cinematic format',
    'Campaign Hero Video': 'Impactful campaign launch video with emotional hook, brand narrative visualization, call-to-action integration, multi-platform compatibility, 16:9 widescreen optimization',
  };

  const locationMap = {
    'Hero Slider Banner': 'uploads/slider',
    'Banner Background Image': 'assets/images/frontend/banner',
    'Hero Video Background': 'uploads/videos/hero',
    'Product Video Thumbnail': 'uploads/videos/products',
    'Product Demo Video': 'uploads/videos/products',
    'Campaign Hero Video': 'uploads/videos/campaigns',
  };

  document.getElementById('imageType').addEventListener('change', function(e) {
    const selected = e.target.options[e.target.selectedIndex];
    if (selected.value) {
      state.imageType = selected.value;
      state.width = selected.getAttribute('data-width');
      state.height = selected.getAttribute('data-height');
      state.location = locationMap[selected.value] || 'uploads/general';

      document.getElementById('width').value = state.width;
      document.getElementById('height').value = state.height;

      if (!document.getElementById('promptDescription').value.trim()) {
        document.getElementById('promptDescription').value = predefinedPrompts[selected.value] || '';
      }
    }
  });

  document.getElementById('generateBtn').addEventListener('click', function() {
    const imageType = document.getElementById('imageType').value;
    const width = document.getElementById('width').value;
    const height = document.getElementById('height').value;
    const description = document.getElementById('promptDescription').value.trim();
    const stylePreset = document.getElementById('stylePreset').value;

    if (!imageType || !width || !height || !description) {
      alert('Please fill in: Image Type, Width, Height, and Description');
      return;
    }

    const qualityEnhancements = [];
    if (document.getElementById('highRes').checked) qualityEnhancements.push('ultra high resolution');
    if (document.getElementById('professionalLighting').checked) qualityEnhancements.push('professional studio lighting');
    if (document.getElementById('sharpDetails').checked) qualityEnhancements.push('razor-sharp details');
    if (document.getElementById('colorGrading').checked) qualityEnhancements.push('professional color grading');
    if (document.getElementById('webOptimized').checked) qualityEnhancements.push('web-optimized quality');
    if (document.getElementById('modernDesign').checked) qualityEnhancements.push('contemporary modern design');

    const styleDescriptions = {
      'professional': 'professional corporate aesthetic, clean business design, trustworthy appearance',
      'modern': 'modern minimalist style, clean lines, contemporary design language',
      'vibrant': 'vibrant energetic colors, dynamic visual impact, bold design choices',
      'elegant': 'elegant sophisticated look, refined aesthetic, premium quality feel',
      'tech': 'futuristic tech aesthetic, cutting-edge design, innovative visual language',
      'natural': 'natural organic style, warm authentic feel, earth-tone palette'
    };

    const prompt = `Create a ${imageType.toLowerCase()} with the following specifications:\n\nDESCRIPTION: ${description}\n\nTECHNICAL SPECS:\n- Dimensions: ${width}x${height} pixels (exact aspect ratio required)\n- File Location: ${state.location}\n- Format: High-quality web-ready image\n- DPI: 72 (web standard) or 300 (print quality)\n\nSTYLE & AESTHETIC:\n- Style Preset: ${styleDescriptions[stylePreset]}\n- Design Approach: ${imageType} optimized for modern web applications\n\nQUALITY REQUIREMENTS:\n${qualityEnhancements.map(q => `- ${q}`).join('\n')}\n- No artifacts, noise, or compression issues\n- Perfect composition and framing\n- Optimal contrast and saturation\n- Professional post-processing applied\n\nBEST PRACTICES:\n- Follow modern UI/UX design principles\n- Ensure accessibility (WCAG compliant colors)\n- Maintain brand consistency\n- Optimized for fast web loading\n- Responsive design consideration\n- Cross-browser compatible appearance\n\nOUTPUT: Professional, production-ready image suitable for immediate deployment in a commercial web application.`;

    document.getElementById('generatedPrompt').textContent = prompt;
  });

  document.getElementById('copyBtn').addEventListener('click', function() {
    const promptText = document.getElementById('generatedPrompt').textContent;
    navigator.clipboard.writeText(promptText);
  });

  document.getElementById('clearBtn').addEventListener('click', function() {
    document.getElementById('imageType').value = '';
    document.getElementById('width').value = '';
    document.getElementById('height').value = '';
    document.getElementById('promptDescription').value = '';
    document.getElementById('stylePreset').value = 'professional';
    document.getElementById('generatedPrompt').textContent = 'Your prompt will appear here...';
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
</script>
@endpush
