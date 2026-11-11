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
    $videoUrl = $tpl->text('video_url');
    $videoCaption = $tpl->text('video_caption');
@endphp

@if($tpl->isEnabled('announce_text') || $tpl->isEnabled('announce_highlight') || $tpl->isEnabled('announce_countdown_end'))
    <div class="bg-gradient-to-r from-rose-50 via-rose-100/80 to-rose-50 text-slate-800 border-b border-rose-200/50 py-2.5 px-4 text-center text-xs md:text-sm font-medium">
        <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-center gap-2">
            <span class="inline-block animate-pulse">🌸</span>
            @if($tpl->isEnabled('announce_text'))<span>{{ $tpl->text('announce_text') }}</span>@endif
            @if($tpl->isEnabled('announce_highlight'))<span class="font-bold text-rose-700">{{ $tpl->text('announce_highlight') }}</span>@endif
            @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                <span data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span>
            @endif
            <span class="inline-block animate-pulse">🌸</span>
        </div>
    </div>
@endif

@if($tpl->isEnabled('hero_title') || $tpl->isEnabled('hero_image'))
    <section class="relative pt-8 pb-16 md:pt-14 md:pb-24 overflow-hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-8 items-center">
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    @if($tpl->isEnabled('hero_badge'))
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full zombie-pill-badge text-rose-700 text-xs md:text-sm font-semibold">
                            <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>{{ $tpl->text('hero_badge') }}
                        </div>
                    @endif
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 leading-[1.25] tracking-tight">{{ $heroTitle }}</h1>
                    @if($tpl->isEnabled('hero_subtitle'))
                        <p class="text-base sm:text-lg text-slate-600 leading-relaxed font-normal">{{ $heroSubtitle }}</p>
                    @endif

                    <div class="p-4 sm:p-5 rounded-2xl bg-rose-50/70 border border-rose-200/60 inline-flex flex-wrap items-center justify-center lg:justify-start gap-4 sm:gap-6">
                        @if($tpl->isEnabled('price_old') && $oldPrice > 0)
                            <span class="text-xs text-slate-500 line-through block">রেগুলার মূল্য: {{ $tpl->moneyBn($oldPrice) }}</span>
                        @endif
                        @if($tpl->isEnabled('price_current'))
                            <span class="text-2xl sm:text-3xl font-black text-rose-600">{{ $tpl->moneyBn($currentPrice) }}</span>
                        @endif
                        @if($tpl->isEnabled('discount_label') && $discountLabel !== '')
                            <span class="text-xs text-rose-800 font-bold">{{ $discountLabel }}</span>
                        @endif
                    </div>

                    @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                        <div class="flex items-center justify-center lg:justify-start gap-2">
                            <span class="bg-white shadow-sm border border-rose-100 rounded-xl px-3 py-2 text-rose-700 font-bold" data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span>
                        </div>
                    @endif

                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a class="w-full sm:w-auto px-8 py-4 rounded-full text-white text-base md:text-lg font-bold text-center zombie-shimmer-btn shadow-lg" href="#landing-order">
                            🌸 {{ $heroCta }}
                        </a>
                    </div>
                </div>

                @if($tpl->isEnabled('hero_image') && $heroImage !== '')
                    <div class="lg:col-span-5 relative flex justify-center">
                        <div class="zombie-curved-img-frame">
                            <img alt="{{ $tpl->text('hero_image_alt', $productName) }}" class="w-full h-auto object-cover" loading="eager" src="{{ $heroImage }}">
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif

@if(($tpl->isEnabled('trust_icon') || $tpl->isEnabled('trust_title') || $tpl->isEnabled('trust_text')) && $trustRows !== [])
    <section class="py-14 bg-gradient-to-b from-[#FFFDFD] via-rose-50/40 to-[#FFFDFD] border-y border-rose-100/50" id="details">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 mt-2">কেন আমাদের ফর্মুলা সম্পূর্ণ ভিন্ন?</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($trustRows as $trust)
                    <div class="p-6 rounded-3xl bg-white border border-rose-100 shadow-sm hover:shadow-md transition-all duration-300">
                        @if($tpl->isEnabled('trust_icon'))
                            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl mb-4">
                                <i class="{{ $trust['icon'] ?? 'fa-solid fa-circle-check' }}"></i>
                            </div>
                        @endif
                        @if($tpl->isEnabled('trust_title'))<h3 class="font-bold text-slate-800 text-lg mb-2">{{ $trust['title'] ?? '' }}</h3>@endif
                        @if($tpl->isEnabled('trust_text'))<p class="text-sm text-slate-600 leading-relaxed">{{ $trust['text'] ?? '' }}</p>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('video_url') && $videoUrl !== '')
    <section class="py-14 bg-white relative">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 text-center mb-6">{{ $tpl->text('video_caption', 'ভিডিওতে দেখুন') }}</h2>
            <div class="rounded-3xl overflow-hidden aspect-video bg-slate-900 shadow-xl">
                <iframe class="w-full h-full" src="{{ $videoUrl }}" title="{{ $productName }}" allowfullscreen></iframe>
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('gallery_image') && $galleryImages !== [])
    <section class="py-16 bg-rose-50/40 border-t border-rose-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-900 mt-1">পছন্দের প্যাকেজটি নির্বাচন করুন</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach(array_slice($galleryImages, 0, 3) as $index => $galleryImage)
                    @php
                        $variant = $variantRows[$index] ?? [];
                        $variantPrice = isset($variant['price']) ? (float) $variant['price'] : $currentPrice;
                        $variantOld = isset($variant['old_price']) ? (float) $variant['old_price'] : $oldPrice;
                        $variantLabel = $variant['label'] ?? ($productName . ' ' . ($index + 1));
                        $variantBadge = $variant['badge'] ?? '';
                    @endphp
                    <div class="rounded-3xl bg-white border border-rose-100 overflow-hidden shadow-sm flex flex-col justify-between">
                        <div>
                            <div class="relative overflow-hidden aspect-square bg-rose-50/50">
                                <img alt="{{ $tpl->text('gallery_image_alt', $productName) }} {{ $index + 1 }}" class="w-full h-full object-cover" loading="lazy" src="{{ $tpl->imageUrl((string) $galleryImage) }}">
                                @if($tpl->isEnabled('variant_badge') && $variantBadge !== '')
                                    <span class="absolute top-3 left-3 bg-rose-600 text-white text-[11px] font-bold px-3 py-1 rounded-full">{{ $variantBadge }}</span>
                                @endif
                            </div>
                            <div class="p-6">
                                @if($tpl->isEnabled('variant_label'))<h3 class="text-lg font-bold text-slate-900">{{ $variantLabel }}</h3>@endif
                                @if($tpl->isEnabled('variant_price'))<span class="text-xl font-bold text-rose-600">{{ $tpl->moneyBn($variantPrice) }}</span>@endif
                            </div>
                        </div>
                        <div class="px-6 pb-6">
                            <a class="block w-full py-3 rounded-full text-center text-sm font-bold text-white bg-rose-600 hover:bg-rose-700" href="#landing-order">{{ $heroCta }}</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('review_text') && $reviewRows !== [])
    <section class="py-16 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center max-w-xl mx-auto mb-12">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1">আমাদের সম্মানিত ক্রেতাদের মতামত</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach(array_slice($reviewRows, 0, 3) as $review)
                    @php
                        $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
                        $reviewer = $review['name'] ?? 'গ্রাহক';
                        $location = $review['location'] ?? '';
                    @endphp
                    <div class="p-6 rounded-3xl bg-rose-50/30 border border-rose-100 flex flex-col justify-between">
                        <div class="space-y-3">
                            @if($tpl->isEnabled('review_rating'))
                                <div class="flex text-amber-400 text-sm">
                                    @for($star = 1; $star <= 5; $star++)<i class="fa-solid fa-star {{ $star <= $rating ? '' : 'opacity-25' }}"></i>@endfor
                                </div>
                            @endif
                            @if($tpl->isEnabled('review_text'))<p class="text-sm text-slate-700 italic leading-relaxed">"{{ $review['text'] ?? '' }}"</p>@endif
                        </div>
                        <div class="mt-4 pt-4 border-t border-rose-100">
                            @if($tpl->isEnabled('review_name'))<p class="text-sm font-bold text-slate-800">{{ $reviewer }}</p>@endif
                            @if($tpl->isEnabled('review_location'))<p class="text-xs text-slate-500">{{ $location }}</p>@endif
                        </div>
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
            <a class="flex-1 py-3 rounded-full text-center text-sm font-bold text-white bg-rose-600" href="#landing-order">{{ $tpl->text('sticky_cta', $heroCta) }}</a>
        </div>
    </div>
@endif
@endsection
