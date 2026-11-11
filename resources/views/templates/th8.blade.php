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
@endphp

@if($tpl->isEnabled('announce_text') || $tpl->isEnabled('announce_highlight') || $tpl->isEnabled('announce_countdown_end'))
    <div class="bg-black text-amber-300 border-b border-white/10 py-2 px-4 text-center text-xs sm:text-sm">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-center gap-3">
            @if($tpl->isEnabled('announce_highlight'))<span class="font-bold">{{ $tpl->text('announce_highlight') }}</span>@endif
            @if($tpl->isEnabled('announce_text'))<span class="text-gray-300">{{ $tpl->text('announce_text') }}</span>@endif
            @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                <span class="font-mono bg-white/10 px-2 py-0.5 rounded" data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span>
            @endif
        </div>
    </div>
@endif

@if($tpl->isEnabled('hero_title') || $tpl->isEnabled('hero_image'))
    <section class="relative pt-6 pb-16 lg:py-16 overflow-hidden" data-purpose="hero-section">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">
                <div class="lg:col-span-6 flex flex-col items-center">
                    <div class="relative w-full max-w-lg rounded-2xl overflow-hidden zombie-border-glow bg-gradient-to-b from-gray-900 to-black p-2 shadow-2xl">
                        @if($tpl->isEnabled('discount_label') && $discountLabel !== '')
                            <div class="absolute top-5 left-5 z-20 bg-gradient-to-r from-red-600 to-rose-700 text-white font-bold text-xs uppercase px-3.5 py-1.5 rounded-md shadow-lg">
                                <i class="fa-solid fa-tag text-[10px]"></i> {{ $discountLabel }}
                            </div>
                        @endif
                        <div class="relative overflow-hidden rounded-xl aspect-[3/4] bg-neutral-900">
                            @if($tpl->isEnabled('hero_image') && $heroImage !== '')
                                <img alt="{{ $tpl->text('hero_image_alt', $productName) }}" class="w-full h-full object-cover object-top" loading="eager" src="{{ $heroImage }}">
                            @endif
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-6 space-y-6">
                    @if($tpl->isEnabled('hero_badge'))
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-medium">
                            <i class="fa-solid fa-wand-magic-sparkles text-zombie-gold"></i><span>{{ $tpl->text('hero_badge') }}</span>
                        </div>
                    @endif
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold leading-tight tracking-tight text-white">{{ $heroTitle }}</h1>
                    @if($tpl->isEnabled('hero_subtitle'))
                        <p class="text-gray-300 text-base sm:text-lg leading-relaxed font-light">{{ $heroSubtitle }}</p>
                    @endif

                    <div class="p-5 rounded-2xl bg-zombie-surface/80 border border-zombie-gold/20 backdrop-blur-md">
                        <div class="flex flex-wrap items-baseline gap-4 mb-2">
                            @if($tpl->isEnabled('price_current'))
                                <span class="text-3xl sm:text-4xl font-extrabold text-white font-cinzel">{{ $tpl->moneyBn($currentPrice) }}</span>
                            @endif
                            @if($tpl->isEnabled('price_old') && $oldPrice > 0)
                                <span class="text-lg sm:text-xl text-gray-500 line-through font-cinzel">{{ $tpl->moneyBn($oldPrice) }}</span>
                            @endif
                        </div>
                        @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                            <p class="text-xs sm:text-sm text-gray-400">অফার শেষ হতে বাকি <span class="text-amber-400 font-mono" data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span></p>
                        @endif
                    </div>

                    <div class="pt-2">
                        <a class="zombie-shimmer-effect block w-full py-4 text-center rounded-xl bg-gradient-to-r from-amber-400 via-zombie-gold to-yellow-500 text-black font-bold text-lg shadow-gold-btn" href="#landing-order">
                            <span class="flex items-center justify-center gap-2"><span>{{ $heroCta }}</span><i class="fa-solid fa-arrow-right"></i></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

@if(($tpl->isEnabled('trust_icon') || $tpl->isEnabled('trust_title') || $tpl->isEnabled('trust_text')) && $trustRows !== [])
    <section class="bg-zombie-cream py-6 text-neutral-900 border-y-2 border-zombie-gold/50 shadow-inner" data-purpose="trust-badges">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                @foreach($trustRows as $trust)
                    <div class="flex flex-col items-center justify-center space-y-1">
                        @if($tpl->isEnabled('trust_icon'))
                            <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-800 text-xl mb-1 shadow-sm">
                                <i class="{{ $trust['icon'] ?? 'fa-solid fa-circle-check' }}"></i>
                            </div>
                        @endif
                        @if($tpl->isEnabled('trust_title'))<span class="font-bold text-sm sm:text-base">{{ $trust['title'] ?? '' }}</span>@endif
                        @if($tpl->isEnabled('trust_text'))<span class="text-xs text-neutral-600">{{ $trust['text'] ?? '' }}</span>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('gallery_image') && $galleryImages !== [])
    <section class="py-16 sm:py-24 bg-zombie-midnight border-t border-white/5" data-purpose="closer-look-gallery">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <span class="text-xs uppercase tracking-widest text-zombie-gold font-semibold">Exquisite Craftsmanship</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-white mt-1">আরও কাছ থেকে দেখুন</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach(array_slice($galleryImages, 0, 8) as $index => $galleryImage)
                    @php
                        $variant = $variantRows[$index] ?? [];
                        $variantPrice = isset($variant['price']) ? (float) $variant['price'] : $currentPrice;
                        $variantLabel = $variant['label'] ?? ($productName . ' ' . ($index + 1));
                        $variantBadge = $variant['badge'] ?? '';
                    @endphp
                    <div class="group relative rounded-xl overflow-hidden aspect-[3/4] bg-neutral-900 zombie-border-glow">
                        <img alt="{{ $tpl->text('gallery_image_alt', $productName) }} {{ $index + 1 }}" class="w-full h-full object-cover" loading="lazy" src="{{ $tpl->imageUrl((string) $galleryImage) }}">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent p-4 flex flex-col justify-end">
                            @if($tpl->isEnabled('variant_badge') && $variantBadge !== '')<span class="text-[11px] text-zombie-gold font-semibold mb-1">{{ $variantBadge }}</span>@endif
                            @if($tpl->isEnabled('variant_label'))<span class="text-xs text-white font-medium">{{ $variantLabel }}</span>@endif
                            @if($tpl->isEnabled('variant_price'))<span class="text-sm text-amber-300 font-bold">{{ $tpl->moneyBn($variantPrice) }}</span>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('review_text') && $reviewRows !== [])
    <section class="py-16 sm:py-24 bg-zombie-deep text-white" data-purpose="customer-reviews">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-14">
                <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs px-3 py-1 rounded-full font-semibold">১০০% ভেরিফাইড রিভিউ</span>
                <h2 class="text-3xl sm:text-4xl font-bold mt-2">ক্রেতাদের সন্তুষ্টির প্রমাণ</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach(array_slice($reviewRows, 0, 3) as $review)
                    @php
                        $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
                        $reviewer = $review['name'] ?? 'গ্রাহক';
                        $location = $review['location'] ?? '';
                    @endphp
                    <div class="bg-[#0b141a] rounded-2xl p-5 border border-emerald-900/40 shadow-xl flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-3 border-b border-gray-800 pb-3 mb-4">
                                @if($tpl->isEnabled('review_avatar'))
                                    <div class="w-10 h-10 rounded-full bg-emerald-700 flex items-center justify-center font-bold text-white text-sm">{{ mb_substr($reviewer, 0, 1) }}</div>
                                @endif
                                <div>
                                    @if($tpl->isEnabled('review_name'))<h4 class="text-sm font-semibold text-gray-100">{{ $reviewer }}</h4>@endif
                                    @if($tpl->isEnabled('review_location'))<p class="text-[11px] text-emerald-400">{{ $location }}</p>@endif
                                </div>
                            </div>
                            @if($tpl->isEnabled('review_text'))
                                <div class="bg-[#202c33] p-3 rounded-xl rounded-tl-none text-xs text-gray-200">"{{ $review['text'] ?? '' }}"</div>
                            @endif
                        </div>
                        @if($tpl->isEnabled('review_rating'))
                            <div class="mt-4 pt-3 border-t border-gray-800 text-amber-400 text-xs">
                                @for($star = 1; $star <= 5; $star++)<i class="fa-solid fa-star {{ $star <= $rating ? '' : 'opacity-25' }}"></i>@endfor
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('faq_question') && $faqRows !== [])
    <section class="py-16 sm:py-20 bg-zombie-midnight border-t border-white/5" data-purpose="faq-accordion">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-white">সচরাচর জিজ্ঞাসিত প্রশ্ন (FAQ)</h2>
            </div>
            <div class="space-y-3">
                @foreach($faqRows as $faq)
                    <div class="rounded-xl bg-zombie-card border border-white/10 overflow-hidden">
                        <details>
                            <summary class="w-full p-4 sm:p-5 text-left flex justify-between items-center font-medium text-white cursor-pointer">
                                <span>{{ $faq['question'] ?? '' }}</span>
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </summary>
                            @if($tpl->isEnabled('faq_answer'))
                                <div class="px-5 pb-5 text-sm text-gray-300 border-t border-white/5 pt-3">{{ $faq['answer'] ?? '' }}</div>
                            @endif
                        </details>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('sticky_cta'))
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-black/95 border-t border-white/10 px-4 py-3">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg font-extrabold text-zombie-gold">{{ $tpl->moneyBn($currentPrice) }}</span>
            <a class="flex-1 py-3 rounded-lg text-center text-sm font-bold text-black bg-gradient-to-r from-amber-400 to-yellow-500" href="#landing-order">{{ $tpl->text('sticky_cta', $heroCta) }}</a>
        </div>
    </div>
@endif
@endsection
