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
    <div class="zombie-top-bar">
        <i class="fa-solid fa-bolt me-1 text-warning"></i>
        @if($tpl->isEnabled('announce_text'))<span>{{ $tpl->text('announce_text') }}</span>@endif
        @if($tpl->isEnabled('announce_highlight'))<span class="badge bg-warning text-dark ms-2">{{ $tpl->text('announce_highlight') }}</span>@endif
        @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
            <span class="ms-2">বাকি <strong data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</strong></span>
        @endif
    </div>
@endif

@if($tpl->isEnabled('hero_title') || $tpl->isEnabled('hero_image'))
    <section class="zombie-hero-section">
        <div class="container">
            @if($tpl->isEnabled('hero_badge'))
                <div class="zombie-pill-badge"><i class="fa-solid fa-certificate"></i> {{ $tpl->text('hero_badge') }}</div>
            @endif
            <h1 class="zombie-hero-title">{{ $heroTitle }}</h1>
            @if($tpl->isEnabled('hero_subtitle'))
                <p class="zombie-hero-subtitle">{{ $heroSubtitle }}</p>
            @endif

            <div class="zombie-pricing-pill-wrap">
                @if($tpl->isEnabled('price_old') && $oldPrice > 0)
                    <span class="zombie-old-price">রেগুলার মূল্য {{ $tpl->moneyBn($oldPrice) }}</span>
                @endif
                @if($tpl->isEnabled('price_current'))
                    <span class="zombie-new-price">অফার মূল্য {{ $tpl->moneyBn($currentPrice) }}</span>
                @endif
                @if($tpl->isEnabled('discount_label') && $discountLabel !== '')
                    <span class="zombie-free-shipping-tag"><i class="fa-solid fa-tag me-1"></i> {{ $discountLabel }}</span>
                @endif
            </div>

            @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                <div class="zombie-countdown-wrap">
                    <span class="zombie-time-box"><span class="zombie-time-val" data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span><span class="zombie-time-lbl">বাকি সময়</span></span>
                </div>
            @endif

            <div>
                <a href="#landing-order" class="zombie-primary-cta">
                    <i class="fa-solid fa-bag-shopping"></i> {{ $heroCta }}
                </a>
            </div>

            @if($tpl->isEnabled('hero_image') && $heroImage !== '')
                <div class="zombie-hero-img-container">
                    <div class="zombie-hero-img-wrap">
                        <img src="{{ $heroImage }}" alt="{{ $tpl->text('hero_image_alt', $productName) }}" loading="eager">
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif

@if(($tpl->isEnabled('trust_icon') || $tpl->isEnabled('trust_title') || $tpl->isEnabled('trust_text')) && $trustRows !== [])
    <section class="zombie-trust-strip">
        <div class="container">
            <div class="row g-4">
                @foreach($trustRows as $trust)
                    <div class="col-6 col-md-3">
                        <div class="zombie-trust-item">
                            @if($tpl->isEnabled('trust_icon'))
                                <div class="zombie-trust-icon"><i class="{{ $trust['icon'] ?? 'fa-solid fa-circle-check' }}"></i></div>
                            @endif
                            <div>
                                @if($tpl->isEnabled('trust_title'))<div class="zombie-trust-title">{{ $trust['title'] ?? '' }}</div>@endif
                                @if($tpl->isEnabled('trust_text'))<p class="zombie-trust-sub">{{ $trust['text'] ?? '' }}</p>@endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('video_url') && $videoUrl !== '')
    <section class="zombie-video-section">
        <div class="container">
            @if($tpl->isEnabled('video_caption') && $videoCaption !== '')
                <h2 class="zombie-section-title mb-3">{{ $videoCaption }}</h2>
            @endif
            <div class="ratio ratio-16x9">
                <iframe src="{{ $videoUrl }}" title="{{ $productName }}" allowfullscreen></iframe>
            </div>
        </div>
    </section>
@endif

@if(($tpl->isEnabled('compare_feature') || $tpl->isEnabled('compare_ours') || $tpl->isEnabled('compare_theirs')) && $comparisonRows !== [])
    <section class="zombie-compare-section">
        <div class="container">
            <h2 class="zombie-section-title">কেন আমাদের {{ $productName }} বাজারের চেয়ে সেরা?</h2>
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

@if($tpl->isEnabled('gallery_image') && $galleryImages !== [])
    <section class="py-5" style="background:#FFFFFF;">
        <div class="container">
            <h2 class="zombie-section-title">কাছ থেকে দেখে নিন</h2>
            <div class="row g-4">
                @foreach(array_slice($galleryImages, 0, 6) as $index => $galleryImage)
                    @php
                        $variant = $variantRows[$index] ?? [];
                        $variantPrice = isset($variant['price']) ? (float) $variant['price'] : $currentPrice;
                        $variantLabel = $variant['label'] ?? ($productName . ' ' . ($index + 1));
                        $variantBadge = $variant['badge'] ?? '';
                    @endphp
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                            <img class="card-img-top" src="{{ $tpl->imageUrl((string) $galleryImage) }}" alt="{{ $tpl->text('gallery_image_alt', $productName) }} {{ $index + 1 }}" loading="lazy">
                            <div class="card-body">
                                @if($tpl->isEnabled('variant_badge') && $variantBadge !== '')<span class="badge bg-danger mb-2">{{ $variantBadge }}</span>@endif
                                @if($tpl->isEnabled('variant_label'))<h5 class="card-title">{{ $variantLabel }}</h5>@endif
                                @if($tpl->isEnabled('variant_price'))<div class="text-danger fw-bold">{{ $tpl->moneyBn($variantPrice) }}</div>@endif
                                <a class="btn btn-dark btn-sm rounded-pill mt-3" href="#landing-order">{{ $heroCta }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('review_text') && $reviewRows !== [])
    <section class="py-5" style="background:#FFFFFF;">
        <div class="container">
            <div class="zombie-section-head">
                <span class="zombie-section-tag">গ্রাহকের প্রতিক্রিয়া</span>
                <h2 class="zombie-section-title">হাজারো পরিবারের বিশ্বস্ত পছন্দ</h2>
            </div>
            <div class="row g-4">
                @foreach(array_slice($reviewRows, 0, 3) as $review)
                    @php
                        $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
                        $reviewer = $review['name'] ?? 'গ্রাহক';
                        $location = $review['location'] ?? '';
                    @endphp
                    <div class="col-md-4">
                        <div class="zombie-review-card">
                            <div class="zombie-review-head">
                                @if($tpl->isEnabled('review_avatar'))
                                    <div class="zombie-rev-avatar">{{ mb_substr($reviewer, 0, 1) }}</div>
                                @endif
                                <div>
                                    @if($tpl->isEnabled('review_name'))<div class="zombie-rev-name">{{ $reviewer }}</div>@endif
                                    @if($tpl->isEnabled('review_location'))
                                        <div class="zombie-rev-city"><i class="fa-solid fa-location-dot me-1 text-danger"></i> {{ $location }}</div>
                                    @endif
                                </div>
                            </div>
                            @if($tpl->isEnabled('review_rating'))
                                <div class="zombie-stars">
                                    @for($star = 1; $star <= 5; $star++)
                                        <i class="fa-solid fa-star {{ $star <= $rating ? '' : 'opacity-25' }}"></i>
                                    @endfor
                                </div>
                            @endif
                            @if($tpl->isEnabled('review_text'))
                                <p class="zombie-rev-text">"{{ $review['text'] ?? '' }}"</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('faq_question') && $faqRows !== [])
    <section class="py-5" style="background:var(--zombie-bg-alt);">
        <div class="container py-3" style="max-width:800px;">
            <div class="zombie-section-head">
                <span class="zombie-section-tag">সাধারণ জিজ্ঞাসা</span>
                <h2 class="zombie-section-title">প্রায়শই জিজ্ঞাসিত প্রশ্নাবলী</h2>
            </div>
            @foreach($faqRows as $faq)
                <div class="zombie-faq-card">
                    <details>
                        <summary class="zombie-faq-btn">
                            <span>{{ $faq['question'] ?? '' }}</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </summary>
                        @if($tpl->isEnabled('faq_answer'))
                            <div class="zombie-faq-content">{{ $faq['answer'] ?? '' }}</div>
                        @endif
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
            <span class="text-danger fw-bold fs-5">{{ $tpl->moneyBn($currentPrice) }}</span>
        </div>
        <a class="btn btn-dark fw-bold rounded-pill px-4 py-2 shadow" href="#landing-order">{{ $tpl->text('sticky_cta', $heroCta) }}</a>
    </div>
@endif
@endsection
