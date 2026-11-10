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
