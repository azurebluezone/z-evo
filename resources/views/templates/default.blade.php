<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $landingPage->title }}</title>
    <meta name="description" content="{{ $landingPage->subtitle }}">
    @include('frontend.partials.event-layer-head')
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="{{ asset('frontend/landing/default.css') }}?v={{ filemtime(public_path('frontend/landing/default.css')) }}">
    <link rel="stylesheet" href="{{ asset('frontend/landing/responsive.css') }}?v={{ filemtime(public_path('frontend/landing/responsive.css')) }}">
</head>
<body>
    @include('frontend.partials.event-layer-body')
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="hero-title">{{ $landingPage->title }}</h1>
                    @if($landingPage->subtitle)
                        <p class="hero-subtitle">{{ $landingPage->subtitle }}</p>
                    @endif
                    @if($landingPage->cta_text && $landingPage->cta_link)
                        <form method="POST" action="{{ $landingPage->cta_link }}" class="d-inline-block">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $landingPage->extra_fields['product_id'] ?? '' }}">
                            @if(!empty($landingPage->extra_fields['campaign_id']))
                                <input type="hidden" name="campaign_id" value="{{ $landingPage->extra_fields['campaign_id'] }}">
                            @endif
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="cta-button" style="border:0;cursor:pointer">
                                {{ $landingPage->cta_text }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
