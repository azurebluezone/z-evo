@extends('backEnd.layouts.app')

@section('title', 'AI Prompt Generator')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="mb-0">AI Prompt Generator</h4>
                    <p class="text-muted mb-0">Generate industry-standard prompts with authentic dimensions and optimal quality settings</p>
                </div>
                <a href="{{ route('admin.prompt') }}" class="btn btn-primary btn-sm">Refresh</a>
            </div>

            <!-- Main Container -->
            <div class="card">
                <div class="card-body">
                    <div class="container">
                        <!-- Image Specifications Card -->
                        <div class="mb-4">
                            <h5 class="card-title">📐 Image Specifications</h5>
                            <div class="form-group mb-3">
                                <label for="imageType" class="form-label fw-bold">Select Image Type</label>
                                <select id="imageType" class="form-select form-select-lg">
                                    <option value="">-- Choose Image Type --</option>
                                    <optgroup label="🎯 Banner & Hero Sections">
                                        <option value="Hero Slider Banner" data-width="1920" data-height="600">Hero Slider Banner (1920x600)</option>
                                        <option value="Banner Background Image" data-width="1200" data-height="400">Banner Background Image (1200x400)</option>
                                        <option value="Hero Video Background" data-width="1920" data-height="1080">Hero Video Background (1920x1080 - 16:9)</option>
                                    </optgroup>
                                    <optgroup label="📢 Promo Banners">
                                        <option value="Promo Banner Left" data-width="600" data-height="300">Promo Banner Left (600x300)</option>
                                        <option value="Promo Banner Right" data-width="600" data-height="300">Promo Banner Right (600x300)</option>
                                    </optgroup>
                                    <optgroup label="🏷️ Product Categories">
                                        <option value="Category Banner Icon" data-width="150" data-height="150">Category Banner Icon (150x150)</option>
                                        <option value="Category Card Image" data-width="300" data-height="200">Category Card Image (300x200)</option>
                                        <option value="Collection Banner" data-width="800" data-height="400">Collection Banner (800x400)</option>
                                    </optgroup>
                                    <optgroup label="🛍️ Products & Collections">
                                        <option value="Product Main Image" data-width="800" data-height="800">Product Main Image (800x800)</option>
                                        <option value="Product Thumbnail" data-width="300" data-height="300">Product Thumbnail (300x300)</option>
                                        <option value="Product Gallery Image" data-width="1200" data-height="1200">Product Gallery Image (1200x1200)</option>
                                        <option value="Featured Product Banner" data-width="800" data-height="400">Featured Product Banner (800x400)</option>
                                        <option value="Flash Sale Banner" data-width="1200" data-height="300">Flash Sale Banner (1200x300)</option>
                                    </optgroup>
                                    <optgroup label="🎥 Product Videos">
                                        <option value="Product Video Thumbnail" data-width="1280" data-height="720">Product Video Thumbnail (1280x720 - 16:9)</option>
                                        <option value="Product Demo Video" data-width="1920" data-height="1080">Product Demo Video (1920x1080 - 16:9)</option>
                                        <option value="Campaign Hero Video" data-width="1920" data-height="1080">Campaign Hero Video (1920x1080 - 16:9)</option>
                                    </optgroup>
                                    <optgroup label="ℹ️ About & Company">
                                        <option value="About Background Image" data-width="1200" data-height="600">About Background Image (1200x600)</option>
                                        <option value="Team Member Image" data-width="150" data-height="150">Team Member Image (150x150)</option>
                                        <option value="Company Logo" data-width="300" data-height="150">Company Logo (300x150)</option>
                                        <option value="Brand Logo" data-width="200" data-height="100">Brand Logo (200x100)</option>
                                    </optgroup>
                                    <optgroup label="💼 Plans & Pricing">
                                        <option value="Plan Background Image" data-width="800" data-height="400">Plan Background Image (800x400)</option>
                                    </optgroup>
                                    <optgroup label="📊 Statistics & Counters">
                                        <option value="Counter Background Image" data-width="1200" data-height="300">Counter Background Image (1200x300)</option>
                                        <option value="Achievement Badge" data-width="200" data-height="200">Achievement Badge (200x200)</option>
                                    </optgroup>
                                    <optgroup label="💬 Testimonials & Reviews">
                                        <option value="Testimonial Image" data-width="80" data-height="80">Testimonial Image (80x80)</option>
                                        <option value="Customer Review Avatar" data-width="100" data-height="100">Customer Review Avatar (100x100)</option>
                                        <option value="Trust Badge Icon" data-width="120" data-height="120">Trust Badge Icon (120x120)</option>
                                    </optgroup>
                                    <optgroup label="⭐ Featured Collections">
                                        <option value="Featured Collection Banner" data-width="1200" data-height="400">Featured Collection Banner (1200x400)</option>
                                        <option value="Best Seller Badge" data-width="150" data-height="150">Best Seller Badge (150x150)</option>
                                    </optgroup>
                                    <optgroup label="📧 Newsletter & Subscribe">
                                        <option value="Subscribe Background Image" data-width="1200" data-height="400">Subscribe Background Image (1200x400)</option>
                                        <option value="Newsletter Popup Banner" data-width="800" data-height="600">Newsletter Popup Banner (800x600)</option>
                                    </optgroup>
                                    <optgroup label="🎁 Coupons & Offers">
                                        <option value="Coupon Banner" data-width="800" data-height="300">Coupon Banner (800x300)</option>
                                        <option value="Offer Badge" data-width="200" data-height="200">Offer Badge (200x200)</option>
                                    </optgroup>
                                    <optgroup label="📝 Blog & Content">
                                        <option value="Blog Image" data-width="850" data-height="450">Blog Image (850x450)</option>
                                        <option value="Blog Thumbnail Image" data-width="300" data-height="200">Blog Thumbnail Image (300x200)</option>
                                        <option value="Blog Detail Image" data-width="770" data-height="450">Blog Detail Image (770x450)</option>
                                        <option value="Blog Detail Thumbnail" data-width="150" data-height="150">Blog Detail Thumbnail (150x150)</option>
                                        <option value="Article Featured Image" data-width="1200" data-height="630">Article Featured Image (1200x630)</option>
                                    </optgroup>
                                    <optgroup label="📰 News & Updates">
                                        <option value="News Banner" data-width="1200" data-height="400">News Banner (1200x400)</option>
                                        <option value="Update Announcement" data-width="800" data-height="400">Update Announcement (800x400)</option>
                                    </optgroup>
                                    <optgroup label="📞 Contact & Support">
                                        <option value="Contact Background Image" data-width="1200" data-height="600">Contact Background Image (1200x600)</option>
                                        <option value="Support Agent Avatar" data-width="150" data-height="150">Support Agent Avatar (150x150)</option>
                                        <option value="FAQ Icon" data-width="100" data-height="100">FAQ Icon (100x100)</option>
                                    </optgroup>
                                    <optgroup label="🔗 Navigation">
                                        <option value="Breadcrumb Background Image" data-width="1200" data-height="200">Breadcrumb Background Image (1200x200)</option>
                                        <option value="Menu Icon" data-width="48" data-height="48">Menu Icon (48x48)</option>
                                    </optgroup>
                                    <optgroup label="🔐 Authentication">
                                        <option value="Login Background Image" data-width="600" data-height="500">Login Background Image (600x500)</option>
                                        <option value="Register Background Image" data-width="500" data-height="700">Register Background Image (500x700)</option>
                                    </optgroup>
                                    <optgroup label="👤 User Profiles">
                                        <option value="User Profile Image" data-width="200" data-height="200">User Profile Image (200x200)</option>
                                        <option value="Admin Profile Image" data-width="300" data-height="300">Admin Profile Image (300x300)</option>
                                        <option value="Customer Avatar" data-width="150" data-height="150">Customer Avatar (150x150)</option>
                                    </optgroup>
                                    <optgroup label="💳 Financial">
                                        <option value="Withdraw Method Image" data-width="600" data-height="600">Withdraw Method Image (600x600)</option>
                                        <option value="Payment Gateway Logo" data-width="200" data-height="100">Payment Gateway Logo (200x100)</option>
                                        <option value="Currency Icon" data-width="64" data-height="64">Currency Icon (64x64)</option>
                                    </optgroup>
                                    <optgroup label="🚚 Shipping & Delivery">
                                        <option value="Shipping Method Icon" data-width="120" data-height="120">Shipping Method Icon (120x120)</option>
                                        <option value="Delivery Truck Illustration" data-width="400" data-height="300">Delivery Truck Illustration (400x300)</option>
                                    </optgroup>
                                    <optgroup label="🔒 Security & Trust">
                                        <option value="Security Badge" data-width="150" data-height="150">Security Badge (150x150)</option>
                                        <option value="Guarantee Icon" data-width="120" data-height="120">Guarantee Icon (120x120)</option>
                                        <option value="SSL Certificate Seal" data-width="100" data-height="100">SSL Certificate Seal (100x100)</option>
                                    </optgroup>
                                    <optgroup label="🔧 System & Technical">
                                        <option value="Extension Image" data-width="32" data-height="32">Extension Image (32x32)</option>
                                        <option value="SEO Image" data-width="1200" data-height="630">SEO Image (1200x630)</option>
                                        <option value="Favicon Image" data-width="32" data-height="32">Favicon Image (32x32)</option>
                                        <option value="Maintenance Image" data-width="500" data-height="300">Maintenance Image (500x300)</option>
                                    </optgroup>
                                    <optgroup label="🦶 Footer Sections">
                                        <option value="Footer Logo" data-width="200" data-height="80">Footer Logo (200x80)</option>
                                        <option value="Footer Background Pattern" data-width="1920" data-height="400">Footer Background Pattern (1920x400)</option>
                                        <option value="Social Media Icon" data-width="48" data-height="48">Social Media Icon (48x48)</option>
                                        <option value="Payment Method Footer" data-width="100" data-height="60">Payment Method Footer (100x60)</option>
                                        <option value="App Download QR Code" data-width="300" data-height="300">App Download QR Code (300x300)</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="width" class="form-label fw-bold">Width (px)</label>
                                    <input type="number" class="form-control form-control-lg" id="width" placeholder="1920" min="1">
                                </div>
                                <div class="col-md-6">
                                    <label for="height" class="form-label fw-bold">Height (px)</label>
                                    <input type="number" class="form-control form-control-lg" id="height" placeholder="1080" min="1">
                                </div>
                            </div>
                        </div>

                        <!-- Quality & Style Settings Card -->
                        <div class="mb-4">
                            <h5 class="card-title">✨ Quality & Style Settings</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block">Quality Enhancement Options</label>
                                <div class="row g-2">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check p-3 border rounded-3 bg-light">
                                            <input class="form-check-input" type="checkbox" id="highRes" checked>
                                            <label class="form-check-label fw-medium" for="highRes">High Resolution</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check p-3 border rounded-3 bg-light">
                                            <input class="form-check-input" type="checkbox" id="professionalLighting" checked>
                                            <label class="form-check-label fw-medium" for="professionalLighting">Professional Lighting</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check p-3 border rounded-3 bg-light">
                                            <input class="form-check-input" type="checkbox" id="sharpDetails" checked>
                                            <label class="form-check-label fw-medium" for="sharpDetails">Sharp Details</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check p-3 border rounded-3 bg-light">
                                            <input class="form-check-input" type="checkbox" id="colorGrading" checked>
                                            <label class="form-check-label fw-medium" for="colorGrading">Color Grading</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check p-3 border rounded-3 bg-light">
                                            <input class="form-check-input" type="checkbox" id="webOptimized" checked>
                                            <label class="form-check-label fw-medium" for="webOptimized">Web Optimized</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-check p-3 border rounded-3 bg-light">
                                            <input class="form-check-input" type="checkbox" id="modernDesign" checked>
                                            <label class="form-check-label fw-medium" for="modernDesign">Modern Design</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="stylePreset" class="form-label fw-bold">Style Preset</label>
                                <select id="stylePreset" class="form-select form-select-lg">
                                    <option value="professional">Professional & Corporate</option>
                                    <option value="modern">Modern & Minimalist</option>
                                    <option value="vibrant">Vibrant & Colorful</option>
                                    <option value="elegant">Elegant & Sophisticated</option>
                                    <option value="tech">Tech & Futuristic</option>
                                    <option value="natural">Natural & Organic</option>
                                </select>
                            </div>
                        </div>

                        <!-- Image Description Card -->
                        <div class="mb-4">
                            <h5 class="card-title">📝 Image Description</h5>
                            <div class="mb-3">
                                <label for="promptDescription" class="form-label fw-bold">Describe Your Image (Be Specific)</label>
                                <textarea class="form-control form-control-lg" id="promptDescription" rows="5" placeholder="Example: A modern corporate banner featuring abstract geometric shapes in blue and white, with a subtle gradient background, clean professional look, and copy space for text overlay..."></textarea>
                                <div class="alert alert-info mt-2 mb-0">
                                    <i class="ri ri-lightbulb-line me-1"></i>
                                    <strong>Tip:</strong> Be specific about colors, composition, mood, and any text or elements you want included. The more detail you provide, the better the result.
                                </div>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <div class="row g-2 mb-4">
                            <div class="col-12">
                                <x-admin.button variant="primary" size="lg" class="w-100" id="generateBtn">
                                    <i class="ri ri-rocket-line me-2"></i>Generate Professional Prompt
                                </x-admin.button>
                                <x-admin.button variant="secondary" size="lg" class="w-100 mt-2" id="clearBtn">
                                    <i class="ri ri-refresh-line me-2"></i>Clear All Fields
                                </x-admin.button>
                            </div>
                        </div>

                        <!-- Generated Prompt Result -->
                        <div>
                            <label class="form-label fw-bold">Generated Prompt (Copy & Use)</label>
                            <div class="position-relative">
                                <div class="card bg-light border-primary">
                                    <div class="card-body">
                                        <pre id="generatedPrompt" class="mb-0" style="white-space: pre-wrap; word-break: break-word; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6;">Your professional AI prompt will appear here...</pre>
                                    </div>
                                </div>
                                <x-admin.button variant="success" class="position-absolute top-0 end-0 m-2" id="copyBtn">
                                    <i class="ri ri-file-copy-line me-1"></i>Copy
                                </x-admin.button>
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
            alert('⚠️ Please fill in all fields: Image Type, Dimensions, and Description');
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

        const prompt = `Create a ${imageType.toLowerCase()} with the following specifications:

DESCRIPTION: ${description}

TECHNICAL SPECS:
- Dimensions: ${width}x${height} pixels (exact aspect ratio required)
- File Location: ${state.location}
- Format: High-quality web-ready image
- DPI: 72 (web standard) or 300 (print quality)

STYLE & AESTHETIC:
- Style Preset: ${styleDescriptions[stylePreset]}
- Design Approach: ${imageType} optimized for modern web applications

QUALITY REQUIREMENTS:
${qualityEnhancements.map(q => `- ${q}`).join('\n')}
- No artifacts, noise, or compression issues
- Perfect composition and framing
- Optimal contrast and saturation
- Professional post-processing applied

BEST PRACTICES:
- Follow modern UI/UX design principles
- Ensure accessibility (WCAG compliant colors)
- Maintain brand consistency
- Optimized for fast web loading
- Responsive design consideration
- Cross-browser compatible appearance

OUTPUT: Professional, production-ready image suitable for immediate deployment in a commercial web application.`;

        document.getElementById('generatedPrompt').textContent = prompt;
        
        // Scroll to result
        document.getElementById('generatedPrompt').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    document.getElementById('copyBtn').addEventListener('click', function() {
        const promptText = document.getElementById('generatedPrompt').textContent;
        navigator.clipboard.writeText(promptText).then(() => {
            const btn = document.getElementById('copyBtn');
            btn.innerHTML = '<i class="ri ri-check-line me-1"></i>Copied!';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
            setTimeout(() => {
                btn.innerHTML = '<i class="ri ri-file-copy-line me-1"></i>Copy';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
            }, 2000);
        });
    });

    document.getElementById('clearBtn').addEventListener('click', function() {
        document.getElementById('imageType').value = '';
        document.getElementById('width').value = '';
        document.getElementById('height').value = '';
        document.getElementById('promptDescription').value = '';
        document.getElementById('stylePreset').value = 'professional';
        document.getElementById('generatedPrompt').textContent = 'Your professional AI prompt will appear here...';
        
        // Reset checkboxes
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
@endpush
