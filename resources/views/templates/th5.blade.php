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
    $comparisonRows = $tpl->rows('comparison');
    $reviewRows = $tpl->rows('reviews');
    $faqRows = $tpl->rows('faqs');
    $videoUrl = $tpl->text('video_url');
    $videoCaption = $tpl->text('video_caption');
@endphp

@if($tpl->isEnabled('announce_text') || $tpl->isEnabled('announce_highlight') || $tpl->isEnabled('announce_countdown_end'))
    <div class="zombie-notice-bar">
        <i class="fa-solid fa-leaf me-2"></i>
        @if($tpl->isEnabled('announce_text')){{ $tpl->text('announce_text') }}@endif
        @if($tpl->isEnabled('announce_highlight'))<span class="badge bg-success ms-2">{{ $tpl->text('announce_highlight') }}</span>@endif
        @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
            <span class="ms-2"><strong data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</strong></span>
        @endif
    </div>
@endif

@if($tpl->isEnabled('hero_title') || $tpl->isEnabled('hero_image'))
    <section class="zombie-hero-section">
        <div class="container">
            @if($tpl->isEnabled('hero_badge'))
                <div class="zombie-hero-badge"><i class="fa-solid fa-certificate"></i> {{ $tpl->text('hero_badge') }}</div>
            @endif
            <h1 class="zombie-hero-title">{{ $heroTitle }}</h1>
            @if($tpl->isEnabled('hero_subtitle'))
                <p class="zombie-hero-subtitle">{{ $heroSubtitle }}</p>
            @endif

            <div class="zombie-price-strip">
                @if($tpl->isEnabled('price_old') && $oldPrice > 0)
                    <span class="zombie-price-old">রেগুলার মূল্য {{ $tpl->moneyBn($oldPrice) }}</span>
                @endif
                @if($tpl->isEnabled('price_current'))
                    <span class="zombie-price-current">অফার মূল্য {{ $tpl->moneyBn($currentPrice) }}</span>
                @endif
                @if($tpl->isEnabled('discount_label') && $discountLabel !== '')
                    <span class="zombie-delivery-tag"><i class="fa-solid fa-tag me-1"></i> {{ $discountLabel }}</span>
                @endif
            </div>

            @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                <div class="zombie-timer-wrap">
                    <span class="zombie-time-box"><span class="zombie-time-num" data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span><span class="zombie-time-lbl">বাকি সময়</span></span>
                </div>
            @endif

            <div>
                <a href="#landing-order" class="zombie-cta-btn">
                    <i class="fa-solid fa-cart-arrow-down"></i> {{ $heroCta }}
                </a>
            </div>

            @if($tpl->isEnabled('hero_image') && $heroImage !== '')
                <div class="zombie-hero-visual">
                    <img src="{{ $heroImage }}" alt="{{ $tpl->text('hero_image_alt', $productName) }}" loading="eager">
                </div>
            @endif

            @if(($tpl->isEnabled('trust_icon') || $tpl->isEnabled('trust_title') || $tpl->isEnabled('trust_text')) && $trustRows !== [])
                <div class="zombie-trust-bar">
                    <div class="row g-4 text-start">
                        @foreach($trustRows as $trust)
                            <div class="col-6 col-md-3">
                                <div class="zombie-trust-item">
                                    @if($tpl->isEnabled('trust_icon'))
                                        <div class="zombie-trust-icon green"><i class="{{ $trust['icon'] ?? 'fa-solid fa-circle-check' }}"></i></div>
                                    @endif
                                    <div>
                                        @if($tpl->isEnabled('trust_title'))<div class="zombie-trust-title">{{ $trust['title'] ?? '' }}</div>@endif
                                        @if($tpl->isEnabled('trust_text'))<div class="zombie-trust-desc">{{ $trust['text'] ?? '' }}</div>@endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif

@if($tpl->isEnabled('video_url') && $videoUrl !== '')
    <section class="zombie-video-container">
        <div class="container">
            @if($tpl->isEnabled('video_caption') && $videoCaption !== '')
                <h2 class="zombie-section-title text-white">{{ $videoCaption }}</h2>
            @endif
            <div class="ratio ratio-16x9 rounded-4 overflow-hidden">
                <iframe src="{{ $videoUrl }}" title="{{ $productName }}" allowfullscreen></iframe>
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('gallery_image') && $galleryImages !== [])
    <section class="zombie-section">
        <div class="container">
            <h2 class="zombie-section-title">কাছ থেকে দেখে নিন আমাদের কালেকশন</h2>
            <div class="row g-4">
                @foreach(array_slice($galleryImages, 0, 6) as $index => $galleryImage)
                    @php
                        $variant = $variantRows[$index] ?? [];
                        $variantPrice = isset($variant['price']) ? (float) $variant['price'] : $currentPrice;
                        $variantLabel = $variant['label'] ?? ($productName . ' ' . ($index + 1));
                        $variantBadge = $variant['badge'] ?? '';
                    @endphp
                    <div class="col-md-4">
                        <div class="zombie-gallery-card">
                            <div class="zombie-gallery-img">
                                <img src="{{ $tpl->imageUrl((string) $galleryImage) }}" alt="{{ $tpl->text('gallery_image_alt', $productName) }} {{ $index + 1 }}" loading="lazy">
                                @if($tpl->isEnabled('variant_badge') && $variantBadge !== '')<span class="zombie-gallery-tag">{{ $variantBadge }}</span>@endif
                            </div>
                            <div class="zombie-gallery-body">
                                @if($tpl->isEnabled('variant_label'))<h3 class="zombie-gallery-title">{{ $variantLabel }}</h3>@endif
                                @if($tpl->isEnabled('variant_price'))<span class="zombie-gallery-price">{{ $tpl->moneyBn($variantPrice) }}</span>@endif
                                <a class="btn btn-success btn-sm rounded-pill mt-2" href="#landing-order">{{ $heroCta }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if(($tpl->isEnabled('compare_feature') || $tpl->isEnabled('compare_ours') || $tpl->isEnabled('compare_theirs')) && $comparisonRows !== [])
    <section class="zombie-section bg-white">
        <div class="container">
            <h2 class="zombie-section-title">কেন আমাদের পণ্য বাজারের সাধারণ পণ্যের চেয়ে আলাদা?</h2>
            <div class="table-responsive">
                <table class="zombie-compare-table">
                    <thead>
                        <tr>
                            @if($tpl->isEnabled('compare_feature'))<th>বৈশিষ্ট্য</th>@endif
                            @if($tpl->isEnabled('compare_ours'))<th>{{ $brandName }}</th>@endif
                            @if($tpl->isEnabled('compare_theirs'))<th>সাধারণ বাজার</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparisonRows as $row)
                            <tr>
                                @if($tpl->isEnabled('compare_feature'))<td>{{ $row['feature'] ?? '' }}</td>@endif
                                @if($tpl->isEnabled('compare_ours'))<td class="text-success fw-bold">{{ $row['ours'] ?? '' }}</td>@endif
                                @if($tpl->isEnabled('compare_theirs'))<td class="text-muted">{{ $row['theirs'] ?? '' }}</td>@endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('review_text') && $reviewRows !== [])
    <section class="zombie-section">
        <div class="container">
            <h2 class="zombie-section-title">আমাদের নিয়মিত ক্রেতারা যা বলছেন</h2>
            <div class="row g-4">
                @foreach(array_slice($reviewRows, 0, 3) as $review)
                    @php
                        $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
                        $reviewer = $review['name'] ?? 'গ্রাহক';
                        $location = $review['location'] ?? '';
                    @endphp
                    <div class="col-md-4">
                        <div class="zombie-review-card">
                            @if($tpl->isEnabled('review_rating'))
                                <div class="zombie-review-stars">
                                    @for($star = 1; $star <= 5; $star++)
                                        <i class="fa-solid fa-star {{ $star <= $rating ? '' : 'opacity-25' }}"></i>
                                    @endfor
                                </div>
                            @endif
                            @if($tpl->isEnabled('review_text'))<p class="zombie-review-text">"{{ $review['text'] ?? '' }}"</p>@endif
                            <div class="zombie-reviewer">
                                @if($tpl->isEnabled('review_avatar'))<div class="zombie-reviewer-avatar">{{ mb_substr($reviewer, 0, 1) }}</div>@endif
                                <div>
                                    @if($tpl->isEnabled('review_name'))<div class="zombie-reviewer-name">{{ $reviewer }}</div>@endif
                                    @if($tpl->isEnabled('review_location'))<div class="zombie-reviewer-loc">{{ $location }}</div>@endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('faq_question') && $faqRows !== [])
    <section class="zombie-section bg-white">
        <div class="container">
            <h2 class="zombie-section-title">প্রায়শই জিজ্ঞাসিত প্রশ্নাবলী</h2>
            @foreach($faqRows as $faq)
                <div class="zombie-faq-card">
                    <details>
                        <summary class="zombie-faq-btn">
                            <span>{{ $faq['question'] ?? '' }}</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </summary>
                        @if($tpl->isEnabled('faq_answer'))<div class="zombie-faq-body">{{ $faq['answer'] ?? '' }}</div>@endif
                    </details>
                </div>
            @endforeach
        </div>
    </section>
@endif

@if($tpl->isEnabled('sticky_cta'))
    <div class="zombie-mobile-sticky d-md-none">
        <div>
            <small class="text-muted d-block lh-1">অফার মূল্য:</small>
            <span class="text-success fw-bold fs-5">{{ $tpl->moneyBn($currentPrice) }}</span>
        </div>
        <a class="btn btn-success fw-bold rounded-pill px-4 py-2 shadow" href="#landing-order">{{ $tpl->text('sticky_cta', $heroCta) }}</a>
    </div>
@endif
@endsection
