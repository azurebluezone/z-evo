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
    <aside class="bg-gradient-to-r from-slate-900 to-slate-800 text-white text-xs sm:text-sm py-2 px-3">
        <div class="zombie-container flex flex-wrap items-center justify-between gap-2 text-center md:text-left">
            <div class="flex items-center gap-2 mx-auto md:mx-0 font-medium">
                @if($tpl->isEnabled('announce_highlight'))
                    <span class="inline-flex items-center justify-center bg-red-500/20 text-red-400 px-2 py-0.5 rounded text-xs">{{ $tpl->text('announce_highlight') }}</span>
                @endif
                @if($tpl->isEnabled('announce_text'))<span>{{ $tpl->text('announce_text') }}</span>@endif
            </div>
            @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                <div class="flex items-center gap-1.5 font-semibold text-amber-400 mx-auto md:mx-0 text-xs">
                    <i class="fa-regular fa-clock"></i>
                    <span class="bg-slate-950 px-2 py-0.5 rounded border border-slate-700 tracking-wider" data-countdown-end="{{ $countdownEnd }}" data-countdown-output>--:--:--</span>
                </div>
            @endif
        </div>
    </aside>
@endif

@if($tpl->isEnabled('hero_title') || $tpl->isEnabled('hero_image'))
    <section class="relative pt-8 pb-14 md:pt-14 md:pb-20 overflow-hidden bg-gradient-to-b from-slate-50 via-white to-white" id="hero">
        <div class="zombie-container">
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-8">
                @if($tpl->isEnabled('hero_badge'))
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-200/80 text-brand-red text-xs sm:text-sm font-semibold">
                        <i class="fa-solid fa-certificate"></i><span>{{ $tpl->text('hero_badge') }}</span>
                    </div>
                @endif
                <h1 class="text-2xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight">{{ $heroTitle }}</h1>
                @if($tpl->isEnabled('hero_subtitle'))
                    <p class="text-base sm:text-lg text-slate-600 leading-relaxed font-normal">{{ $heroSubtitle }}</p>
                @endif
                <div class="inline-flex flex-wrap items-center justify-center gap-3 sm:gap-4 p-2 bg-slate-100/90 rounded-2xl border border-slate-200">
                    @if($tpl->isEnabled('price_old') && $oldPrice > 0)
                        <span class="text-slate-400 line-through text-base font-semibold">পূর্বের মূল্য: {{ $tpl->moneyBn($oldPrice) }}</span>
                    @endif
                    @if($tpl->isEnabled('price_current'))
                        <div class="bg-brand-red text-white px-3 py-1 rounded-xl text-lg sm:text-xl font-bold">
                            <span>অফার মূল্য: {{ $tpl->moneyBn($currentPrice) }}</span>
                        </div>
                    @endif
                    @if($tpl->isEnabled('discount_label') && $discountLabel !== '')
                        <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded-lg">{{ $discountLabel }}</span>
                    @endif
                </div>
            </div>

            <div class="max-w-4xl mx-auto relative">
                <div class="zombie-card p-2 sm:p-3 bg-white rounded-3xl overflow-hidden shadow-2xl border-slate-200">
                    <div class="relative w-full aspect-video rounded-2xl overflow-hidden bg-slate-900 group">
                        @if($tpl->isEnabled('hero_image') && $heroImage !== '')
                            <img alt="{{ $tpl->text('hero_image_alt', $productName) }}" class="w-full h-full object-cover" loading="eager" src="{{ $heroImage }}">
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-black/20 to-transparent flex flex-col justify-between p-4 sm:p-6 text-white">
                            <span class="bg-slate-900/80 backdrop-blur-sm text-xs font-semibold px-3 py-1 rounded-full border border-slate-700 w-max">{{ $brandName }} প্রোডাক্ট ভিজ্যুয়াল</span>
                            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                                <h3 class="text-lg sm:text-2xl font-bold text-white">{{ $productName }}</h3>
                                <a class="zombie-btn-primary zombie-pulse px-6 py-3 text-sm sm:text-base font-bold shadow-lg" href="#landing-order">
                                    <i class="fa-solid fa-bag-shopping mr-2"></i> {{ $heroCta }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if(($tpl->isEnabled('trust_icon') || $tpl->isEnabled('trust_title') || $tpl->isEnabled('trust_text')) && $trustRows !== [])
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-5 mt-10 max-w-4xl mx-auto">
                    @foreach($trustRows as $trust)
                        <div class="bg-white p-3 sm:p-4 rounded-xl border border-slate-200 text-center flex flex-col items-center justify-center">
                            @if($tpl->isEnabled('trust_icon'))<i class="{{ $trust['icon'] ?? 'fa-solid fa-circle-check' }} text-brand-red text-xl sm:text-2xl mb-1.5"></i>@endif
                            @if($tpl->isEnabled('trust_title'))<span class="text-xs sm:text-sm font-bold text-slate-800">{{ $trust['title'] ?? '' }}</span>@endif
                            @if($tpl->isEnabled('trust_text'))<span class="text-[11px] text-slate-500">{{ $trust['text'] ?? '' }}</span>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif

@if($tpl->isEnabled('video_url') && $videoUrl !== '')
    <section class="py-16 md:py-24 bg-slate-950 text-white">
        <div class="zombie-container">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold">{{ $tpl->text('video_caption', 'প্রোডাক্ট ভিডিও') }}</h2>
            </div>
            <div class="max-w-4xl mx-auto rounded-3xl overflow-hidden aspect-video bg-slate-900">
                <iframe class="w-full h-full" src="{{ $videoUrl }}" title="{{ $productName }}" allowfullscreen></iframe>
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('gallery_image') && $galleryImages !== [])
    <section class="py-16 bg-white" id="gallery">
        <div class="zombie-container">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-3">কাছ থেকে দেখে নিন</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach(array_slice($galleryImages, 0, 8) as $index => $galleryImage)
                    @php
                        $variant = $variantRows[$index] ?? [];
                        $variantPrice = isset($variant['price']) ? (float) $variant['price'] : $currentPrice;
                        $variantLabel = $variant['label'] ?? ($productName . ' ' . ($index + 1));
                        $variantBadge = $variant['badge'] ?? '';
                    @endphp
                    <div class="zombie-card group overflow-hidden flex flex-col justify-between p-3">
                        <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-slate-100 mb-3">
                            <img class="w-full h-full object-cover" alt="{{ $tpl->text('gallery_image_alt', $productName) }} {{ $index + 1 }}" loading="lazy" src="{{ $tpl->imageUrl((string) $galleryImage) }}">
                            @if($tpl->isEnabled('variant_badge') && $variantBadge !== '')
                                <span class="absolute top-2.5 left-2.5 bg-slate-900 text-white text-[11px] font-bold px-2.5 py-1 rounded-md">{{ $variantBadge }}</span>
                            @endif
                        </div>
                        <div class="px-1 pb-1">
                            @if($tpl->isEnabled('variant_label'))<h4 class="font-bold text-slate-900 text-base mb-1">{{ $variantLabel }}</h4>@endif
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                @if($tpl->isEnabled('variant_price'))<span class="text-brand-red font-bold text-base">{{ $tpl->moneyBn($variantPrice) }}</span>@endif
                                <a class="text-xs font-bold text-slate-700 hover:text-brand-red" href="#landing-order">{{ $heroCta }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if(($tpl->isEnabled('compare_feature') || $tpl->isEnabled('compare_ours') || $tpl->isEnabled('compare_theirs')) && $comparisonRows !== [])
    <section class="py-14 bg-slate-50 border-y border-slate-200" id="comparison">
        <div class="zombie-container">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 mb-6 text-center">কেন {{ $brandName }} সেরা?</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden text-sm sm:text-base">
                    <thead>
                        <tr class="border-b border-slate-200">
                            @if($tpl->isEnabled('compare_feature'))<th class="p-4 sm:p-5 bg-slate-100/80 font-bold text-slate-900 w-1/3">ফিচার</th>@endif
                            @if($tpl->isEnabled('compare_ours'))<th class="p-4 sm:p-5 bg-red-50/80 font-bold text-brand-red text-center w-1/3">{{ $brandName }}</th>@endif
                            @if($tpl->isEnabled('compare_theirs'))<th class="p-4 sm:p-5 bg-slate-100/50 font-semibold text-slate-500 text-center w-1/3">সাধারণ বাজার</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($comparisonRows as $row)
                            <tr>
                                @if($tpl->isEnabled('compare_feature'))<td class="p-4 sm:p-5 font-semibold text-slate-900">{{ $row['feature'] ?? '' }}</td>@endif
                                @if($tpl->isEnabled('compare_ours'))<td class="p-4 sm:p-5 text-center font-bold text-emerald-700 bg-red-50/30">{{ $row['ours'] ?? '' }}</td>@endif
                                @if($tpl->isEnabled('compare_theirs'))<td class="p-4 sm:p-5 text-center text-slate-500">{{ $row['theirs'] ?? '' }}</td>@endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('review_text') && $reviewRows !== [])
    <section class="py-14 bg-white">
        <div class="zombie-container">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 mb-8 text-center">হাজারো সন্তুষ্ট গ্রাহকের ভালোবাসা</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach(array_slice($reviewRows, 0, 3) as $review)
                    @php
                        $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
                        $reviewer = $review['name'] ?? 'গ্রাহক';
                        $location = $review['location'] ?? '';
                    @endphp
                    <div class="zombie-card p-6 flex flex-col justify-between">
                        <div>
                            @if($tpl->isEnabled('review_rating'))
                                <div class="flex items-center text-amber-400 mb-3 gap-1 text-sm">
                                    @for($star = 1; $star <= 5; $star++)<i class="fa-solid fa-star {{ $star <= $rating ? '' : 'opacity-25' }}"></i>@endfor
                                </div>
                            @endif
                            @if($tpl->isEnabled('review_text'))<p class="text-slate-700 text-sm leading-relaxed mb-4">"{{ $review['text'] ?? '' }}"</p>@endif
                        </div>
                        <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                            @if($tpl->isEnabled('review_avatar'))
                                <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm">{{ mb_substr($reviewer, 0, 1) }}</div>
                            @endif
                            <div>
                                @if($tpl->isEnabled('review_name'))<h4 class="font-bold text-slate-900 text-sm">{{ $reviewer }}</h4>@endif
                                @if($tpl->isEnabled('review_location') && $location !== '')
                                    <span class="text-[11px] text-emerald-600 flex items-center gap-1 font-medium"><i class="fa-solid fa-circle-check text-[10px]"></i> {{ $location }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('faq_question') && $faqRows !== [])
    <section class="py-14 bg-slate-50 border-t border-slate-200" id="faq">
        <div class="zombie-container max-w-3xl">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 mb-8 text-center">সাধারণ প্রশ্ন ও উত্তর</h2>
            <div class="space-y-3">
                @foreach($faqRows as $faq)
                    <details class="group zombie-card p-4 rounded-xl cursor-pointer">
                        <summary class="font-bold text-slate-900 text-sm sm:text-base flex justify-between items-center list-none">
                            <span>{{ $faq['question'] ?? '' }}</span>
                            <span class="text-slate-400 group-open:rotate-180 transition-transform"><i class="fa-solid fa-chevron-down text-xs"></i></span>
                        </summary>
                        @if($tpl->isEnabled('faq_answer'))
                            <p class="text-xs sm:text-sm text-slate-600 mt-2.5 leading-relaxed">{{ $faq['answer'] ?? '' }}</p>
                        @endif
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if($tpl->isEnabled('sticky_cta'))
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-3 shadow-lg">
        <div class="flex items-center justify-between gap-3">
            <span class="text-lg font-extrabold text-brand-red">{{ $tpl->moneyBn($currentPrice) }}</span>
            <a class="zombie-btn-primary flex-1 py-3 text-sm font-bold" href="#landing-order">{{ $tpl->text('sticky_cta', $heroCta) }}</a>
        </div>
    </div>
@endif
@endsection
