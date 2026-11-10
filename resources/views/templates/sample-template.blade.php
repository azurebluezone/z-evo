<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $landingPage->title ?? 'Landing Page' }}</title>
    @include('frontend.partials.event-layer-head')
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('frontend/landing/sample-template.css') }}?v={{ filemtime(public_path('frontend/landing/sample-template.css')) }}">
    <link rel="stylesheet" href="{{ asset('frontend/landing/responsive.css') }}?v={{ filemtime(public_path('frontend/landing/responsive.css')) }}">
</head>

<body>
    @include('frontend.partials.event-layer-body')
    @php
        $extra = $landingPage->extra_fields ?? [];
        $showFeatures = !empty($extra['show_features']);
        $showBenefits = !empty($extra['show_benefits']);
        $showTestimonials = !empty($extra['show_testimonials']);
        $showFaq = !empty($extra['show_faq']);
    @endphp
    <!-- Hero Section -->
    <section class="hero-section" style="--hero-image: url('{{ $landingPage->image_url ? asset($landingPage->image_url) : 'https://sjc.microlink.io/DkZFF8NxGvYFVv4ZKiqbl_5t9iGTIHy3R5uGkmwZZX_I0XI7xJiD0eX-g2Z0bsYFJjn3wsOqHqLCfkv--5AkTw.jpeg' }}');">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <div class="hero-content">
                        <h1 class="hero-title">{{ $landingPage->title ?? 'Welcome to Our Landing Page' }}</h1>
                        @if($landingPage->subtitle)
                        <p class="hero-subtitle">{{ $landingPage->subtitle }}</p>
                        @endif
                        @if($landingPage->cta_text && $landingPage->cta_link)
                        @if(!empty($landingPage->extra_fields['product_id']))
                        <form method="POST" action="{{ $landingPage->cta_link }}" class="d-inline-block">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $landingPage->extra_fields['product_id'] }}">
                            @if(!empty($landingPage->extra_fields['campaign_id']))
                                <input type="hidden" name="campaign_id" value="{{ $landingPage->extra_fields['campaign_id'] }}">
                            @endif
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="cta-button" style="border:0;cursor:pointer">
                                {{ $landingPage->cta_text }}
                            </button>
                        </form>
                        @else
                        <a href="{{ $landingPage->cta_link }}" class="cta-button">
                            {{ $landingPage->cta_text }}
                        </a>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Wave Divider -->
        <div class="wave-divider">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
        </div>
    </section>

    <!-- Content Section -->
    @if($landingPage->description)
    <section class="content-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="content-text">
                        {!! $landingPage->description !!}
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Features Section -->
    @if($showFeatures && !empty($extra['features']))
    <section class="content-section bg-white">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="fw-bold">Our Features</h2>
                </div>
            </div>
            <div class="row">
                @foreach($extra['features'] as $feature)
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card" data-aos="fade-up">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4>{{ is_array($feature) ? ($feature['title'] ?? 'Feature') : $feature }}</h4>
                        @if(is_array($feature) && !empty($feature['description']))
                        <p>{{ $feature['description'] }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Benefits Section -->
    @if($showBenefits && !empty($extra['benefits']))
    <section class="content-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="fw-bold">Why Choose Us</h2>
                </div>
            </div>
            <div class="row">
                @foreach($extra['benefits'] as $benefit)
                @php
                    $benefitTitle = is_array($benefit) ? ($benefit['title'] ?? 'Benefit') : (string) $benefit;
                    $benefitText = is_array($benefit) ? ($benefit['text'] ?? ($benefit['description'] ?? '')) : '';
                @endphp
                <div class="col-lg-6">
                    <div class="benefit-item" data-aos="fade-right">
                        <h5><i class="fas fa-check-circle text-success me-2"></i>{{ $benefitTitle }}</h5>
                        @if($benefitText !== '')
                        <p>{{ $benefitText }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Testimonials Section -->
    @if($showTestimonials && !empty($extra['testimonials']))
    <section class="content-section bg-white">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="fw-bold">What Our Customers Say</h2>
                </div>
            </div>
            <div class="row">
                @foreach($extra['testimonials'] as $testimonial)
                @php
                    $testimonialText = is_array($testimonial)
                        ? ($testimonial['text'] ?? ($testimonial['comment'] ?? 'Great service!'))
                        : (string) $testimonial;
                @endphp
                <div class="col-lg-4 col-md-6">
                    <div class="testimonial-card" data-aos="fade-up">
                        <div class="testimonial-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <p class="mb-3">{{ $testimonialText }}</p>
                        <h6 class="fw-bold">{{ is_array($testimonial) ? ($testimonial['name'] ?? 'Customer') : 'Customer' }}</h6>
                        <small class="text-muted">{{ is_array($testimonial) ? ($testimonial['position'] ?? 'Client') : 'Client' }}</small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- FAQ Section -->
    @if($showFaq && !empty($extra['faqs']))
    <section class="content-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="fw-bold">Frequently Asked Questions</h2>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @foreach($extra['faqs'] as $faq)
                    @continue(!is_array($faq))
                    <div class="faq-item" data-aos="fade-up">
                        <button class="faq-question" onclick="toggleFAQ(this)">
                            {{ $faq['question'] ?? 'Question' }}
                            <i class="fas fa-chevron-down float-end"></i>
                        </button>
                        <div class="faq-answer">
                            <p>{{ $faq['answer'] ?? 'Answer' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Contact Section -->
    @if(!empty($extra['contact_form']))
    <section class="content-section bg-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="contact-form" data-aos="fade-up">
                        <h3 class="text-center mb-4">Contact Us</h3>
                        <form>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input type="text" class="form-control" placeholder="Your Name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="email" class="form-control" placeholder="Your Email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" placeholder="Subject" required>
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" rows="5" placeholder="Your Message" required></textarea>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="cta-button">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p>&copy; {{ date('Y') }} {{ $landingPage->title ?? 'Landing Page' }}. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script src="{{ asset('frontend/landing/sample-template.js') }}?v={{ filemtime(public_path('frontend/landing/sample-template.js')) }}"></script>
</body>

</html>