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
    $videoUrl = $tpl->text('video_url');
    $videoCaption = $tpl->text('video_caption');
@endphp

@if($tpl->isEnabled('announce_text') || $tpl->isEnabled('announce_highlight') || $tpl->isEnabled('announce_countdown_end'))
    <div class="zombie-topbar">
        <div class="container text-center">
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2">
                @if($tpl->isEnabled('announce_highlight'))
                    <span class="badge bg-danger">{{ $tpl->text('announce_highlight') }}</span>
                @endif
                @if($tpl->isEnabled('announce_text'))
                    <span>{{ $tpl->text('announce_text') }}</span>
                @endif
                @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                    <span class="zombie-timer-unit" data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span>
                @endif
            </div>
        </div>
    </div>
@endif

@if($tpl->isEnabled('hero_title') || $tpl->isEnabled('hero_image'))
    <section class="zombie-hero-hook">
        <div class="container">
            @if($tpl->isEnabled('hero_badge'))
                <div class="zombie-urgency-badge">
                    <i class="fa-solid fa-fire text-danger"></i> {{ $tpl->text('hero_badge') }}
                </div>
            @endif
            <h1 class="zombie-hero-headline">{{ $heroTitle }}</h1>
            @if($tpl->isEnabled('hero_subtitle'))
                <p class="zombie-hero-sub">{{ $heroSubtitle }}</p>
            @endif
            <div class="zombie-offer-banner">
                @if($tpl->isEnabled('price_old') && $oldPrice > 0)
                    <div>
                        <span class="text-muted d-block small">রেগুলার মূল্য:</span>
                        <span class="zombie-original-price">{{ $tpl->moneyBn($oldPrice) }}</span>
                    </div>
                    <div class="vr bg-secondary opacity-50"></div>
                @endif
                @if($tpl->isEnabled('price_current'))
                    <div>
                        <span class="text-warning d-block small fw-bold">আজকের বিশেষ অফার মূল্য:</span>
                        <span class="zombie-current-price">{{ $tpl->moneyBn($currentPrice) }}</span>
                    </div>
                @endif
                @if($tpl->isEnabled('discount_label') && $discountLabel !== '')
                    <div class="badge bg-danger fs-6 px-3 py-2 rounded-pill">{{ $discountLabel }} + ফ্রি ডেলিভারি</div>
                @endif
            </div>
            <div class="mb-4">
                <a class="zombie-btn-primary" href="#landing-order">
                    <i class="fa-solid fa-cart-shopping"></i> {{ $heroCta }}
                </a>
                <div class="mt-2 text-warning small fw-semibold">
                    <i class="fa-solid fa-circle-check text-success me-1"></i> ডেলিভারি ম্যানের সামনে প্যাকেট খুলে চেক করে টাকা পরিশোধ করতে পারবেন
                </div>
            </div>
            @if($tpl->isEnabled('hero_image') && $heroImage !== '')
                <div class="zombie-offer-banner mt-3">
                    <img class="img-fluid rounded-3" src="{{ $heroImage }}" alt="{{ $tpl->text('hero_image_alt', $productName) }}" loading="eager">
                </div>
            @endif
        </div>
    </section>
@endif

@if($tpl->isEnabled('video_url') && $videoUrl !== '')
    <section class="zombie-video-section">
        <div class="container">
            <div class="zombie-video-wrapper">
                @if($tpl->isEnabled('video_caption') && $videoCaption !== '')
                    <div class="zombie-video-badge"><i class="fa-solid fa-circle-play"></i> {{ $videoCaption }}</div>
                @endif
                <div class="zombie-video-container">
                    <iframe allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen src="{{ $videoUrl }}" title="{{ $productName }}"></iframe>
                </div>
            </div>
            <div class="text-center mt-3">
                <a class="btn btn-danger btn-lg rounded-pill px-5 fw-bold shadow mt-2" href="#landing-order">
                    ভিডিও দেখে পছন্দ হলে এখনই অর্ডার করুন <i class="fa-solid fa-arrow-down ms-1"></i>
                </a>
            </div>
        </div>
    </section>
@endif

@if(($tpl->isEnabled('trust_icon') || $tpl->isEnabled('trust_title') || $tpl->isEnabled('trust_text')) && $trustRows !== [])
    <section class="zombie-trust-grid">
        <div class="container">
            <div class="row g-3">
                @foreach($trustRows as $trust)
                    <div class="col-md-3 col-6">
                        <div class="zombie-trust-card">
                            @if($tpl->isEnabled('trust_icon'))
                                <div class="zombie-trust-icon">
                                    <i class="{{ $trust['icon'] ?? 'fa-solid fa-circle-check' }}"></i>
                                </div>
                            @endif
                            <div>
                                @if($tpl->isEnabled('trust_title'))
                                    <h5>{{ $trust['title'] ?? '' }}</h5>
                                @endif
                                @if($tpl->isEnabled('trust_text'))
                                    <p>{{ $trust['text'] ?? '' }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if(($tpl->isEnabled('compare_feature') || $tpl->isEnabled('compare_ours') || $tpl->isEnabled('compare_theirs')) && $comparisonRows !== [])
    <section class="zombie-compare-section">
        <div class="container">
            <h2 class="zombie-section-title">কেন এটি <span>সাধারণ মানিব্যাগের চেয়ে সম্পূর্ণ আলাদা?</span></h2>
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="table-responsive">
                        <table class="zombie-compare-table">
                            <thead>
                                <tr>
                                    @if($tpl->isEnabled('compare_feature'))<th class="zombie-th-feature">বৈশিষ্ট্যসমূহ</th>@endif
                                    @if($tpl->isEnabled('compare_ours'))<th class="zombie-th-our">{{ $brandName }}</th>@endif
                                    @if($tpl->isEnabled('compare_theirs'))<th class="zombie-th-market">বাজারের সাধারণ</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comparisonRows as $row)
                                    <tr>
                                        @if($tpl->isEnabled('compare_feature'))<td>{{ $row['feature'] ?? '' }}</td>@endif
                                        @if($tpl->isEnabled('compare_ours'))<td class="zombie-td-highlight">{{ $row['ours'] ?? '' }}</td>@endif
                                        @if($tpl->isEnabled('compare_theirs'))<td class="zombie-td-bad">{{ $row['theirs'] ?? '' }}</td>@endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-4">
                        <a class="zombie-btn-primary" href="#landing-order">
                            <i class="fa-solid fa-circle-check"></i> {{ $heroCta }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('gallery_image') && $galleryImages !== [])
    <section class="zombie-showcase-section">
        <div class="container">
            <h2 class="zombie-section-title">আমাদের <span>প্রিমিয়াম কালেকশন</span></h2>
            <div class="row g-4">
                @foreach(array_slice($galleryImages, 0, 6) as $index => $galleryImage)
                    @php
                        $variant = $variantRows[$index] ?? [];
                        $variantPrice = isset($variant['price']) ? (float) $variant['price'] : $currentPrice;
                        $variantLabel = $variant['label'] ?? ($productName . ' ' . ($index + 1));
                        $variantBadge = $variant['badge'] ?? '';
                    @endphp
                    <div class="col-md-4">
                        <div class="zombie-product-card">
                            <img alt="{{ $tpl->text('gallery_image_alt', $productName) }} {{ $index + 1 }}"
                                 src="{{ $tpl->imageUrl((string) $galleryImage) }}" loading="lazy" />
                            @if($tpl->isEnabled('variant_badge') && $variantBadge !== '')
                                <span class="zombie-product-badge">{{ $variantBadge }}</span>
                            @endif
                            @if($tpl->isEnabled('variant_label'))
                                <h4>{{ $variantLabel }}</h4>
                            @endif
                            @if($tpl->isEnabled('variant_price'))
                                <div class="zombie-gallery-price">{{ $tpl->moneyBn($variantPrice) }}</div>
                            @endif
                            <a class="btn btn-warning btn-sm rounded-pill px-4 fw-bold" href="#landing-order">
                                {{ $heroCta }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('review_text') && $reviewRows !== [])
    <section class="zombie-reviews-section">
        <div class="container">
            <h2 class="zombie-section-title">সন্তুষ্ট গ্রাহকদের <span>বাস্তব অভিজ্ঞতা</span></h2>
            <div class="row g-4">
                @foreach(array_slice($reviewRows, 0, 3) as $review)
                    @php
                        $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
                        $reviewer = $review['name'] ?? 'গ্রাহক';
                        $location = $review['location'] ?? '';
                        $verified = $review['verified'] ?? 'ভেরিফায়েড ক্রেতা';
                    @endphp
                    <div class="col-md-4">
                        <div class="zombie-review-box">
                            @if($tpl->isEnabled('review_rating'))
                                <div class="zombie-review-stars">
                                    @for($star = 1; $star <= 5; $star++)
                                        <i class="fa-solid fa-star {{ $star <= $rating ? '' : 'opacity-25' }}"></i>
                                    @endfor
                                </div>
                            @endif
                            @if($tpl->isEnabled('review_text'))
                                <p class="zombie-review-text">"{{ $review['text'] ?? '' }}"</p>
                            @endif
                            <div class="zombie-reviewer-meta">
                                @if($tpl->isEnabled('review_avatar'))
                                    <div class="zombie-review-avatar">{{ mb_substr($reviewer, 0, 1) }}</div>
                                @endif
                                <div>
                                    @if($tpl->isEnabled('review_name'))
                                        <strong class="text-white d-block">{{ $reviewer }}</strong>
                                    @endif
                                    <small class="text-muted">
                                        @if($tpl->isEnabled('review_location') && $location !== ''){{ $location }}@endif
                                        @if($tpl->isEnabled('review_verified')) ({{ $verified }})@endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('sticky_cta'))
    <div class="zombie-mobile-sticky d-md-none">
        <div>
            <small class="text-muted d-block lh-1">ধামাকা অফার মূল্য:</small>
            <span class="text-warning fw-bold fs-5">{{ $tpl->moneyBn($currentPrice) }}</span>
        </div>
        <a class="btn btn-danger fw-bold rounded-pill px-4 py-2 shadow" href="#landing-order">
            {{ $tpl->text('sticky_cta', $heroCta) }} <i class="fa-solid fa-arrow-down ms-1"></i>
        </a>
    </div>
@endif
@endsection
