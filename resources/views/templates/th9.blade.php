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
    <div class="bg-rose-100/80 text-rose-900 border-b border-rose-200/70 py-2 px-4 text-center text-xs md:text-sm">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-center gap-3">
            @if($tpl->isEnabled('announce_highlight'))<span class="font-bold">{{ $tpl->text('announce_highlight') }}</span>@endif
            @if($tpl->isEnabled('announce_text'))<span>{{ $tpl->text('announce_text') }}</span>@endif
            @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                <span class="font-mono bg-white/70 px-2 py-0.5 rounded" data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span>
            @endif
        </div>
    </div>
@endif

@if($tpl->isEnabled('hero_title') || $tpl->isEnabled('hero_image'))
    <section class="relative pt-6 pb-12 overflow-hidden" data-purpose="hero-section">
        <div class="max-w-6xl mx-auto px-4">
            @if($tpl->isEnabled('hero_badge'))
                <div class="flex justify-center mb-4">
                    <div class="inline-flex items-center gap-2 bg-rose-100/90 text-rose-800 border border-rose-200/80 px-4 py-1.5 rounded-full text-xs md:text-sm font-semibold">
                        <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>{{ $tpl->text('hero_badge') }}
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-6 text-center lg:text-left space-y-4">
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 leading-tight">{{ $heroTitle }}</h1>
                    @if($tpl->isEnabled('hero_subtitle'))
                        <p class="text-slate-600 text-base md:text-lg leading-relaxed max-w-xl mx-auto lg:mx-0">{{ $heroSubtitle }}</p>
                    @endif

                    <div class="pt-2">
                        <div class="inline-flex items-baseline gap-3 bg-white px-5 py-2.5 rounded-2xl border border-rose-100 shadow-sm mb-4">
                            @if($tpl->isEnabled('price_current'))
                                <span class="text-2xl md:text-3xl font-extrabold text-rose-600">{{ $tpl->moneyBn($currentPrice) }}</span>
                            @endif
                            @if($tpl->isEnabled('price_old') && $oldPrice > 0)
                                <span class="text-sm text-slate-400 line-through">{{ $tpl->moneyBn($oldPrice) }}</span>
                            @endif
                            @if($tpl->isEnabled('discount_label') && $discountLabel !== '')
                                <span class="text-xs bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-md font-bold">{{ $discountLabel }}</span>
                            @endif
                        </div>
                        @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                            <p class="text-sm text-rose-600 font-semibold mb-3">অফার শেষ: <span class="font-mono" data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span></p>
                        @endif
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3">
                            <a class="zombie-pulse-btn w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-gradient-to-r from-rose-600 to-rose-700 text-white font-bold text-lg px-8 py-3.5 rounded-2xl shadow-xl" href="#landing-order">
                                <i class="fa-solid fa-bag-shopping"></i> {{ $heroCta }}
                            </a>
                        </div>
                    </div>
                </div>

                @if($tpl->isEnabled('hero_image') && $heroImage !== '')
                    <div class="lg:col-span-6">
                        <div class="relative bg-white p-3 rounded-3xl shadow-xl border border-rose-100 overflow-hidden">
                            <img class="w-full h-full object-cover rounded-2xl" loading="eager" src="{{ $heroImage }}" alt="{{ $tpl->text('hero_image_alt', $productName) }}">
                        </div>
                    </div>
                @endif
            </div>

            @if(($tpl->isEnabled('trust_icon') || $tpl->isEnabled('trust_title') || $tpl->isEnabled('trust_text')) && $trustRows !== [])
                <div class="mt-12 bg-slate-900 text-white rounded-2xl p-5 md:p-6 shadow-xl" data-purpose="trust-badges-strip">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center md:text-left">
                        @foreach($trustRows as $trust)
                            <div class="flex items-center gap-3 justify-center md:justify-start px-2">
                                @if($tpl->isEnabled('trust_icon'))
                                    <div class="w-10 h-10 rounded-xl bg-rose-600/20 text-rose-400 flex items-center justify-center text-xl shrink-0">
                                        <i class="{{ $trust['icon'] ?? 'fa-solid fa-circle-check' }}"></i>
                                    </div>
                                @endif
                                <div>
                                    @if($tpl->isEnabled('trust_title'))<p class="font-bold text-sm">{{ $trust['title'] ?? '' }}</p>@endif
                                    @if($tpl->isEnabled('trust_text'))<p class="text-xs text-slate-400">{{ $trust['text'] ?? '' }}</p>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif

@if($tpl->isEnabled('gallery_image') && $galleryImages !== [])
    <section class="py-12 bg-white" data-purpose="bundle-color-selection">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-800">পছন্দের ডিজাইন বেছে নিন</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach(array_slice($galleryImages, 0, 8) as $index => $galleryImage)
                    @php
                        $variant = $variantRows[$index] ?? [];
                        $variantPrice = isset($variant['price']) ? (float) $variant['price'] : $currentPrice;
                        $variantOld = isset($variant['old_price']) ? (float) $variant['old_price'] : $oldPrice;
                        $variantLabel = $variant['label'] ?? ($productName . ' ' . ($index + 1));
                        $variantBadge = $variant['badge'] ?? '';
                    @endphp
                    <div class="bg-white rounded-3xl border border-rose-100 shadow-sm overflow-hidden">
                        <div class="relative aspect-square bg-rose-50">
                            <img class="w-full h-full object-cover" loading="lazy" src="{{ $tpl->imageUrl((string) $galleryImage) }}" alt="{{ $tpl->text('gallery_image_alt', $productName) }} {{ $index + 1 }}">
                            @if($tpl->isEnabled('variant_badge') && $variantBadge !== '')
                                <span class="absolute top-3 left-3 bg-rose-600 text-white text-xs font-bold px-3 py-1 rounded-full">{{ $variantBadge }}</span>
                            @endif
                        </div>
                        <div class="p-4">
                            @if($tpl->isEnabled('variant_label'))<h3 class="font-bold text-slate-800">{{ $variantLabel }}</h3>@endif
                            <div class="flex items-center gap-2 mt-1">
                                @if($tpl->isEnabled('variant_price'))<span class="text-rose-600 font-extrabold">{{ $tpl->moneyBn($variantPrice) }}</span>@endif
                                @if($tpl->isEnabled('variant_old_price') && $variantOld > 0)
                                    <span class="text-xs text-slate-400 line-through">{{ $tpl->moneyBn($variantOld) }}</span>
                                @endif
                            </div>
                            <a class="mt-3 block w-full py-2.5 rounded-xl text-center text-sm font-bold text-white bg-rose-600" href="#landing-order">{{ $heroCta }}</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('review_text') && $reviewRows !== [])
    <section class="py-12 bg-rose-50/50" data-purpose="customer-reviews-proof">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-slate-800">অভিভাবকদের ভালোবাসা ও বিশ্বাস</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach(array_slice($reviewRows, 0, 3) as $review)
                    @php
                        $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
                        $reviewer = $review['name'] ?? 'গ্রাহক';
                        $location = $review['location'] ?? '';
                    @endphp
                    <div class="p-5 rounded-2xl bg-white border border-rose-100 shadow-sm flex flex-col justify-between">
                        <div>
                            @if($tpl->isEnabled('review_rating'))
                                <div class="flex items-center gap-1 text-amber-400 text-xs mb-3">
                                    @for($star = 1; $star <= 5; $star++)<i class="fa-solid fa-star {{ $star <= $rating ? '' : 'opacity-25' }}"></i>@endfor
                                </div>
                            @endif
                            @if($tpl->isEnabled('review_text'))
                                <p class="text-slate-700 text-sm italic leading-relaxed">"{{ $review['text'] ?? '' }}"</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 mt-4 pt-3 border-t border-rose-100">
                            @if($tpl->isEnabled('review_avatar'))
                                <div class="w-9 h-9 rounded-full bg-rose-200 text-rose-700 flex items-center justify-center font-bold text-sm">{{ mb_substr($reviewer, 0, 1) }}</div>
                            @endif
                            <div>
                                @if($tpl->isEnabled('review_name'))<h5 class="text-sm font-bold text-slate-800">{{ $reviewer }}</h5>@endif
                                @if($tpl->isEnabled('review_location'))<p class="text-[11px] text-slate-400">{{ $location }}</p>@endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('faq_question') && $faqRows !== [])
    <section class="py-12 bg-rose-50/30" data-purpose="faq-accordion">
        <div class="max-w-4xl mx-auto px-4">
            <div class="text-center mb-8">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-800">আপনার কোনো প্রশ্ন আছে?</h2>
            </div>
            <div class="space-y-3">
                @foreach($faqRows as $faq)
                    <div class="bg-white rounded-2xl border border-rose-100 p-4 shadow-sm">
                        <details>
                            <summary class="flex items-center justify-between cursor-pointer">
                                <h4 class="font-bold text-slate-800 text-sm md:text-base">{{ $faq['question'] ?? '' }}</h4>
                                <i class="fa-solid fa-chevron-down text-rose-500 text-xs"></i>
                            </summary>
                            @if($tpl->isEnabled('faq_answer'))
                                <p class="text-xs md:text-sm text-slate-600 mt-2 leading-relaxed">{{ $faq['answer'] ?? '' }}</p>
                            @endif
                        </details>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('sticky_cta'))
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-rose-100 px-4 py-3 shadow-lg">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg font-extrabold text-rose-600">{{ $tpl->moneyBn($currentPrice) }}</span>
            <a class="flex-1 py-3 rounded-xl text-center text-sm font-bold text-white bg-rose-600" href="#landing-order">{{ $tpl->text('sticky_cta', $heroCta) }}</a>
        </div>
    </div>
@endif
@endsection
