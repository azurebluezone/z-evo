@extends('layouts.templates')

@section('content')
@php
    $brandName = $tpl->text('brand_name', config('app.name', 'ZEvo'));
    $productName = $tpl->productName();
    $heroTitle = $tpl->text('hero_title', $productName);
    $heroSubtitle = $tpl->text('hero_subtitle');
    $heroImage = $tpl->text('hero_image');
    $heroCta = $tpl->text('hero_cta', 'এখনই অর্ডার করুন');
    $currentPrice = $tpl->priceCurrentNumber();
    $oldPrice = $tpl->priceOldNumber();
    $discountLabel = $tpl->text('discount_label');
    $countdownEnd = $tpl->text('announce_countdown_end');
    $trustRows = array_slice($tpl->rows('trust_items'), 0, 4);
    $galleryImages = $tpl->raw('gallery_image');
    $galleryImages = is_array($galleryImages) ? array_values($galleryImages) : [];
    $variantRows = $tpl->rows('variants');
    $reviewRows = $tpl->rows('reviews');
    $faqRows = $tpl->rows('faqs');
    $videoUrl = $tpl->text('video_url');
    $videoCaption = $tpl->text('video_caption');
@endphp

@if($tpl->isEnabled('announce_text') || $tpl->isEnabled('announce_highlight') || $tpl->isEnabled('announce_countdown_end'))
    <div class="zombie-topbar">
        @if($tpl->isEnabled('announce_highlight'))
            <span class="badge-pill"><i class="fa-solid fa-sparkles"></i> {{ $tpl->text('announce_highlight') }}</span>
        @endif
        @if($tpl->isEnabled('announce_text'))<span>{{ $tpl->text('announce_text') }}</span>@endif
        @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
            <span class="badge-pill"><span data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span></span>
        @endif
    </div>
@endif

@if($tpl->isEnabled('hero_title') || $tpl->isEnabled('hero_image'))
    <section class="zombie-hero-section" id="hero">
        <div class="zombie-container">
            <div class="zombie-hero-grid">
                <div class="zombie-hero-content">
                    @if($tpl->isEnabled('hero_badge'))
                        <div class="zombie-hero-tag"><i class="fa-solid fa-award"></i> {{ $tpl->text('hero_badge') }}</div>
                    @endif
                    <h2 class="zombie-hero-title">{{ $heroTitle }}</h2>
                    @if($tpl->isEnabled('hero_subtitle'))
                        <p class="zombie-hero-desc">{{ $heroSubtitle }}</p>
                    @endif

                    <div class="zombie-hero-badges">
                        @if($tpl->isEnabled('price_old') && $oldPrice > 0)
                            <div class="zombie-badge-item"><i class="fa-solid fa-tag"></i> রেগুলার {{ $tpl->moneyBn($oldPrice) }}</div>
                        @endif
                        @if($tpl->isEnabled('price_current'))
                            <div class="zombie-badge-item"><i class="fa-solid fa-bangladeshi-taka-sign"></i> অফার {{ $tpl->moneyBn($currentPrice) }}</div>
                        @endif
                        @if($tpl->isEnabled('discount_label') && $discountLabel !== '')
                            <div class="zombie-badge-item"><i class="fa-solid fa-percent"></i> {{ $discountLabel }}</div>
                        @endif
                    </div>

                    <div class="zombie-hero-cta-box">
                        <a href="#landing-order" class="zombie-btn-primary-hero">
                            <i class="fa-solid fa-bag-shopping"></i> {{ $heroCta }}
                        </a>
                        @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                            <div class="zombie-hero-price-tag">বাকি <strong data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</strong></div>
                        @endif
                    </div>
                </div>

                @if($tpl->isEnabled('hero_image') && $heroImage !== '')
                    <div class="zombie-hero-img-card">
                        <img src="{{ $heroImage }}" alt="{{ $tpl->text('hero_image_alt', $productName) }}" loading="eager">
                        <div class="zombie-hero-img-overlay">
                            <div class="zombie-overlay-text">
                                <h4>{{ $productName }}</h4>
                                <p>{{ $brandName }}</p>
                            </div>
                            <div class="zombie-overlay-stars">
                                @for($star = 1; $star <= 5; $star++)<i class="fa-solid fa-star"></i>@endfor
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('video_url') && $videoUrl !== '')
    <section class="zombie-video-section">
        <div class="zombie-container">
            @if($tpl->isEnabled('video_caption') && $videoCaption !== '')
                <h3 class="zombie-section-title">{{ $videoCaption }}</h3>
            @endif
            <div class="ratio ratio-16x9 rounded-4 overflow-hidden">
                <iframe src="{{ $videoUrl }}" title="{{ $productName }}" allowfullscreen></iframe>
            </div>
        </div>
    </section>
@endif

@if(($tpl->isEnabled('trust_icon') || $tpl->isEnabled('trust_title') || $tpl->isEnabled('trust_text')) && $trustRows !== [])
    <section class="zombie-trust-section">
        <div class="zombie-container">
            <div class="zombie-trust-grid">
                @foreach($trustRows as $trust)
                    <div class="zombie-trust-card">
                        @if($tpl->isEnabled('trust_icon'))
                            <div class="zombie-trust-icon"><i class="{{ $trust['icon'] ?? 'fa-solid fa-circle-check' }}"></i></div>
                        @endif
                        @if($tpl->isEnabled('trust_title'))<h4>{{ $trust['title'] ?? '' }}</h4>@endif
                        @if($tpl->isEnabled('trust_text'))<p>{{ $trust['text'] ?? '' }}</p>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('gallery_image') && $galleryImages !== [])
    <section class="zombie-section-tabs" id="collections">
        <div class="zombie-container">
            <div class="zombie-section-heading">
                <span class="zombie-sub-title">এক্সক্লুসিভ কালেকশন</span>
                <h3 class="zombie-main-title">পছন্দের পণ্য থেকে সহজে বেছে নিন</h3>
            </div>
            <div class="zombie-products-grid">
                @foreach(array_slice($galleryImages, 0, 6) as $index => $galleryImage)
                    @php
                        $variant = $variantRows[$index] ?? [];
                        $variantPrice = isset($variant['price']) ? (float) $variant['price'] : $currentPrice;
                        $variantLabel = $variant['label'] ?? ($productName . ' ' . ($index + 1));
                        $variantBadge = $variant['badge'] ?? '';
                    @endphp
                    <div class="zombie-product-card">
                        @if($tpl->isEnabled('variant_badge') && $variantBadge !== '')
                            <span class="zombie-card-badge badge-gold"><i class="fa-solid fa-star"></i> {{ $variantBadge }}</span>
                        @endif
                        <div class="zombie-card-img-wrap">
                            <img src="{{ $tpl->imageUrl((string) $galleryImage) }}" alt="{{ $tpl->text('gallery_image_alt', $productName) }} {{ $index + 1 }}" loading="lazy">
                        </div>
                        <div class="zombie-card-content">
                            @if($tpl->isEnabled('variant_label'))<h4 class="zombie-card-title">{{ $variantLabel }}</h4>@endif
                            <div class="zombie-card-footer">
                                @if($tpl->isEnabled('variant_price'))
                                    <div class="zombie-card-price">{{ $tpl->moneyBn($variantPrice) }}</div>
                                @endif
                                <a href="#landing-order" class="zombie-card-cta-btn">{{ $heroCta }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('review_text') && $reviewRows !== [])
    <section class="zombie-reviews-section">
        <div class="zombie-container">
            <div class="zombie-section-heading">
                <span class="zombie-sub-title">ক্রেতাদের ভালোবাসা</span>
                <h3 class="zombie-main-title">আমাদের ক্রেতারা যা বলছেন</h3>
            </div>
            <div class="zombie-reviews-grid">
                @foreach(array_slice($reviewRows, 0, 3) as $review)
                    @php
                        $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
                        $reviewer = $review['name'] ?? 'গ্রাহক';
                        $location = $review['location'] ?? '';
                    @endphp
                    <div class="zombie-review-card">
                        <div class="zombie-review-header">
                            @if($tpl->isEnabled('review_avatar'))
                                <div class="zombie-reviewer-avatar">{{ mb_substr($reviewer, 0, 1) }}</div>
                            @endif
                            <div class="zombie-reviewer-info">
                                @if($tpl->isEnabled('review_name'))<h4>{{ $reviewer }}</h4>@endif
                                @if($tpl->isEnabled('review_location'))<span>{{ $location }}</span>@endif
                            </div>
                        </div>
                        @if($tpl->isEnabled('review_rating'))
                            <div class="zombie-review-rating">
                                @for($star = 1; $star <= 5; $star++)<i class="fa-solid fa-star {{ $star <= $rating ? '' : 'opacity-25' }}"></i>@endfor
                            </div>
                        @endif
                        @if($tpl->isEnabled('review_text'))<p class="zombie-review-text">"{{ $review['text'] ?? '' }}"</p>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('faq_question') && $faqRows !== [])
    <section class="zombie-faq-section">
        <div class="zombie-container">
            <div class="zombie-section-heading">
                <span class="zombie-sub-title">সচরাচর জিজ্ঞাসা</span>
                <h3 class="zombie-main-title">প্রশ্ন ও উত্তর</h3>
            </div>
            <div class="zombie-faq-grid">
                @foreach($faqRows as $faq)
                    <div class="zombie-faq-card">
                        <details>
                            <summary class="zombie-faq-question">{{ $faq['question'] ?? '' }} <i class="fa-solid fa-chevron-down"></i></summary>
                            @if($tpl->isEnabled('faq_answer'))
                                <div class="zombie-faq-answer">{{ $faq['answer'] ?? '' }}</div>
                            @endif
                        </details>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('sticky_cta'))
    <div class="zombie-mobile-sticky d-md-none">
        <div>
            <span class="text-dark fw-bold fs-5">{{ $tpl->moneyBn($currentPrice) }}</span>
        </div>
        <a class="btn btn-dark fw-bold rounded-pill px-4 py-2 shadow" href="#landing-order">{{ $tpl->text('sticky_cta', $heroCta) }}</a>
    </div>
@endif
@endsection
