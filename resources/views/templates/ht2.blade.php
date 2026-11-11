@extends('layouts.templates')

@section('content')
@php
    $brandName = $tpl->text('brand_name', config('app.name', 'ZEvo'));
    $productName = $tpl->text('hero_title', $tpl->productName());
    $currentPrice = $tpl->priceCurrentNumber();
    $oldPrice = $tpl->priceOldNumber();
    $heroImage = $tpl->text('hero_image');
    $heroCta = $tpl->text('hero_cta', 'এখনই অর্ডার করুন');
    $countdownEnd = $tpl->text('announce_countdown_end');
    $hotline = $tpl->text('hotline_number');
    $trustRows = array_slice($tpl->rows('trust_items'), 0, 4);
    $galleryImages = $tpl->raw('gallery_image');
    $galleryImages = is_array($galleryImages) ? array_slice(array_values($galleryImages), 0, 4) : [];
    $variantRows = $tpl->rows('variants');
    $comparisonRows = $tpl->rows('comparison');
    $reviewRows = $tpl->rows('reviews');
    $faqRows = $tpl->rows('faqs');
@endphp

@if($tpl->isEnabled('announce_text') || $tpl->isEnabled('announce_highlight') || $tpl->isEnabled('announce_countdown_end'))
    <aside class="bg-gradient-to-r from-brand-slate to-brand-dark text-white text-xs sm:text-sm py-2 px-3" data-purpose="urgency-banner">
        <div class="zombie-container flex flex-wrap items-center justify-between gap-2 text-center md:text-left">
            <div class="flex items-center gap-2 mx-auto md:mx-0 font-medium">
                @if($tpl->isEnabled('announce_highlight'))
                    <span class="inline-flex items-center justify-center bg-red-500/20 text-red-400 px-2 py-0.5 rounded text-xs">
                        {{ $tpl->text('announce_highlight', '🔥 সীমিত অফার') }}
                    </span>
                @endif
                @if($tpl->isEnabled('announce_text'))
                    <span>{{ $tpl->text('announce_text', 'ঢাকার ভিতরে ২৪ ঘণ্টায় ও সারা দেশে ৪৮ ঘণ্টায় ফ্রি হোম ডেলিভারি!') }}</span>
                @endif
            </div>
            <div class="flex items-center gap-4 mx-auto md:mx-0 text-xs">
                @if($tpl->isEnabled('announce_countdown_end') && $countdownEnd !== '')
                    <div class="flex items-center gap-1.5 font-semibold text-amber-400">
                        <i class="fa-regular fa-clock"></i>
                        <span>অফারের বাকি:</span>
                        <span class="bg-slate-800 px-2 py-0.5 rounded border border-slate-700 tracking-wider" id="countdown-timer">--:--:--</span>
                    </div>
                @endif
                @if($tpl->isEnabled('hotline_number') && $hotline !== '')
                    <a class="hidden sm:inline-flex items-center gap-1.5 text-slate-300 hover:text-white transition"
                       href="tel:{{ preg_replace('/[^\d+]/', '', $hotline) }}">
                        <i class="fa-solid fa-phone text-xs"></i> হেল্পলাইন: {{ $hotline }}
                    </a>
                @endif
            </div>
        </div>
    </aside>
@endif

<main>
    @if($tpl->isEnabled('hero_title') || $tpl->isEnabled('hero_image'))
        <section class="relative pt-8 pb-14 md:pt-14 md:pb-20 overflow-hidden bg-gradient-to-b from-slate-50 via-white to-white" id="hero">
            <div class="zombie-container">
                <div class="text-center max-w-3xl mx-auto space-y-4 mb-8">
                    @if($tpl->isEnabled('hero_badge'))
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-200/80 text-brand-red text-xs sm:text-sm font-semibold">
                            <i class="fa-solid fa-certificate"></i>
                            <span>{{ $tpl->text('hero_badge', '১০০% অরিজিনাল প্রোডাক্ট') }}</span>
                        </div>
                    @endif

                    <h1 class="text-2xl sm:text-4xl md:text-5xl font-extrabold text-slate-900 leading-tight">
                        {{ $productName }}
                    </h1>

                    @if($tpl->isEnabled('hero_subtitle'))
                        <p class="text-base sm:text-lg text-slate-600 leading-relaxed font-normal">
                            {{ $tpl->text('hero_subtitle') }}
                        </p>
                    @endif

                    @if($tpl->isEnabled('price_current') || $tpl->isEnabled('price_old'))
                        <div class="inline-flex flex-wrap items-center justify-center gap-3 sm:gap-4 p-2 bg-slate-100/90 rounded-2xl border border-slate-200">
                            @if($tpl->isEnabled('price_old') && $oldPrice > 0)
                                <span class="text-slate-400 line-through text-base font-semibold">
                                    পূর্বের মূল্য: {{ $tpl->moneyBn($oldPrice) }}
                                </span>
                            @endif
                            @if($tpl->isEnabled('price_current'))
                                <div class="bg-brand-red text-white px-3 py-1 rounded-xl text-lg sm:text-xl font-bold flex items-center gap-1.5 shadow-sm">
                                    <span>অফার মূল্য: {{ $tpl->moneyBn($currentPrice) }}</span>
                                </div>
                            @endif
                            @if($tpl->isEnabled('discount_label') && $tpl->text('discount_label') !== '')
                                <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-1 rounded-lg">
                                    {{ $tpl->text('discount_label') }} + ফ্রি ডেলিভারি
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="max-w-4xl mx-auto relative">
                    <div class="zombie-card p-2 sm:p-3 bg-white rounded-3xl overflow-hidden shadow-2xl border-slate-200">
                        <div class="relative w-full aspect-video rounded-2xl overflow-hidden bg-slate-900 group">
                            @if($heroImage !== '')
                                <img alt="{{ $tpl->text('hero_image_alt', $productName) }}"
                                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                     loading="eager" src="{{ $heroImage }}">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-slate-800 to-slate-950"></div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-black/20 to-transparent flex flex-col justify-between p-4 sm:p-6 text-white">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="bg-slate-900/80 backdrop-blur-sm text-xs font-semibold px-3 py-1 rounded-full border border-slate-700 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                        {{ $brandName }} প্রোডাক্ট ভিজ্যুয়াল
                                    </span>
                                    @if($tpl->text('hero_badge') !== '')
                                        <div class="bg-amber-500/90 text-slate-950 text-xs font-extrabold px-3 py-1 rounded-full uppercase tracking-wider">
                                            {{ $tpl->text('hero_badge') }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg sm:text-2xl font-bold text-white mb-1">{{ $productName }}</h3>
                                        <p class="text-xs sm:text-sm text-slate-300">প্রিমিয়াম ফিনিশিং, টেকসই ম্যাটেরিয়াল ও নিখুঁত হস্তনির্মিত ডিজাইন।</p>
                                    </div>
                                    <a class="zombie-btn-primary zombie-pulse px-6 py-3 text-sm sm:text-base font-bold shadow-lg shrink-0" href="#landing-order">
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
                                @if($tpl->isEnabled('trust_icon'))
                                    <i class="{{ $trust['icon'] ?? 'fa-solid fa-circle-check' }} text-brand-red text-xl sm:text-2xl mb-1.5"></i>
                                @endif
                                @if($tpl->isEnabled('trust_title'))
                                    <span class="text-xs sm:text-sm font-bold text-slate-800">{{ $trust['title'] ?? '' }}</span>
                                @endif
                                @if($tpl->isEnabled('trust_text'))
                                    <span class="text-[11px] text-slate-500">{{ $trust['text'] ?? '' }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if(($tpl->isEnabled('trust_icon') || $tpl->isEnabled('trust_title') || $tpl->isEnabled('trust_text')) && $trustRows !== [])
        <section class="py-12 bg-brand-bgLight border-y border-slate-200/80" id="features">
            <div class="zombie-container">
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-2">
                        কেন {{ $brandName }} থেকে {{ $productName }} কিনবেন?
                    </h2>
                    <p class="text-sm sm:text-base text-slate-600">
                        গ্রাহকের সন্তুষ্টি ও পণ্যের বিশ্বস্ততা বজায় রাখতে প্রতিটি অর্ডারে সর্বোচ্চ গুণগত মান নিশ্চিত করা হয়।
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($trustRows as $trust)
                        <div class="zombie-card p-6 flex flex-col justify-between">
                            <div>
                                @if($tpl->isEnabled('trust_icon'))
                                    <div class="w-12 h-12 rounded-xl bg-red-100 text-brand-red flex items-center justify-center text-xl mb-4 font-bold">
                                        <i class="{{ $trust['icon'] ?? 'fa-solid fa-circle-check' }}"></i>
                                    </div>
                                @endif
                                @if($tpl->isEnabled('trust_title'))
                                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $trust['title'] ?? '' }}</h3>
                                @endif
                                @if($tpl->isEnabled('trust_text'))
                                    <p class="text-sm text-slate-600 leading-relaxed">{{ $trust['text'] ?? '' }}</p>
                                @endif
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center text-xs font-semibold text-emerald-600">
                                <i class="fa-solid fa-circle-check mr-1.5"></i> {{ $brandName }} গ্যারান্টি
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($tpl->isEnabled('gallery_image') && $galleryImages !== [])
        <section class="py-16 bg-white" id="gallery">
            <div class="zombie-container">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <div class="inline-flex items-center gap-1.5 text-xs text-brand-red font-bold uppercase tracking-wider mb-1">
                        <i class="fa-regular fa-eye"></i> গ্যালারি ও ফিনিশিং
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-3">কাছ থেকে দেখে নিন</h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        পণ্যের বিভিন্ন অ্যাঙ্গেল ও ফিনিশিং দেখুন। অর্ডারের আগে পছন্দের ভ্যারিয়েন্ট নির্বাচন করুন।
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($galleryImages as $index => $galleryImage)
                        @php
                            $variant = $variantRows[$index] ?? [];
                            $variantPrice = isset($variant['price']) ? (float) $variant['price'] : $currentPrice;
                            $variantLabel = $variant['label'] ?? ($productName . ' ' . ($index + 1));
                            $variantBadge = $variant['badge'] ?? '';
                        @endphp
                        <div class="zombie-card group overflow-hidden flex flex-col justify-between p-3">
                            <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-slate-100 mb-3">
                                <img alt="{{ $tpl->text('gallery_image_alt', $productName) }} {{ $index + 1 }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                     loading="lazy" src="{{ $tpl->imageUrl((string) $galleryImage) }}">
                                @if($tpl->isEnabled('variant_badge') && $variantBadge !== '')
                                    <span class="absolute top-2.5 left-2.5 bg-slate-900 text-white text-[11px] font-bold px-2.5 py-1 rounded-md shadow">
                                        {{ $variantBadge }}
                                    </span>
                                @endif
                            </div>
                            <div class="px-1 pb-1">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    @if($tpl->isEnabled('variant_label'))
                                        <h4 class="font-bold text-slate-900 text-base">{{ $variantLabel }}</h4>
                                    @endif
                                    <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded whitespace-nowrap">স্টকে আছে</span>
                                </div>
                                <p class="text-xs text-slate-500 mb-3">প্রিমিয়াম ফিনিশিং, মজবুত সেলাই ও আরামদায়ক ডিজাইন।</p>
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                    @if($tpl->isEnabled('variant_price'))
                                        <span class="text-brand-red font-bold text-base">{{ $tpl->moneyBn($variantPrice) }}</span>
                                    @endif
                                    <a class="text-xs font-bold text-slate-700 hover:text-brand-red flex items-center gap-1" href="#landing-order">
                                        সিলেক্ট করুন <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
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
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <span class="text-xs font-bold text-brand-red uppercase tracking-wider">তুলনামূলক পার্থক্য</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 mb-2">
                        কেন {{ $brandName }}-এর {{ $productName }} সেরা?
                    </h2>
                    <p class="text-sm sm:text-base text-slate-600">নকল বা সস্তা পণ্যের ফাঁদে পা না দিয়ে আসল ও টেকসই পণ্য ব্যবহার করুন।</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden text-sm sm:text-base">
                        <thead>
                            <tr class="border-b border-slate-200">
                                @if($tpl->isEnabled('compare_feature'))
                                    <th class="p-4 sm:p-5 bg-slate-100/80 font-bold text-slate-900 w-1/3">ফিচার / বৈশিষ্ট্য</th>
                                @endif
                                @if($tpl->isEnabled('compare_ours'))
                                    <th class="p-4 sm:p-5 bg-red-50/80 font-bold text-brand-red text-center w-1/3 border-x border-red-100">
                                        <div class="inline-flex items-center gap-1.5 font-extrabold">
                                            <i class="fa-solid fa-circle-check text-emerald-600"></i> {{ $brandName }}
                                        </div>
                                    </th>
                                @endif
                                @if($tpl->isEnabled('compare_theirs'))
                                    <th class="p-4 sm:p-5 bg-slate-100/50 font-semibold text-slate-500 text-center w-1/3">
                                        <div class="inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-circle-xmark text-rose-500"></i> সাধারণ বাজার
                                        </div>
                                    </th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach($comparisonRows as $row)
                                <tr>
                                    @if($tpl->isEnabled('compare_feature'))
                                        <td class="p-4 sm:p-5 font-semibold text-slate-900">{{ $row['feature'] ?? '' }}</td>
                                    @endif
                                    @if($tpl->isEnabled('compare_ours'))
                                        <td class="p-4 sm:p-5 text-center font-bold text-emerald-700 bg-red-50/30 border-x border-red-50">{{ $row['ours'] ?? '' }}</td>
                                    @endif
                                    @if($tpl->isEnabled('compare_theirs'))
                                        <td class="p-4 sm:p-5 text-center text-slate-500">{{ $row['theirs'] ?? '' }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    @if(($tpl->isEnabled('review_text') || $tpl->isEnabled('review_rating') || $tpl->isEnabled('review_name') || $tpl->isEnabled('review_avatar') || $tpl->isEnabled('review_location') || $tpl->isEnabled('review_verified')) && $reviewRows !== [])
        <section class="py-14 bg-white">
            <div class="zombie-container">
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <span class="text-xs font-bold text-brand-red uppercase tracking-wider">গ্রাহকদের অভিজ্ঞতা</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1 mb-2">হাজারো সন্তুষ্ট গ্রাহকের বিশ্বস্ত ভালোবাসা</h2>
                    <p class="text-sm sm:text-base text-slate-600">আমাদের পণ্য হাতে পেয়ে ক্রেতারা কি বলছেন তা সরাসরি জেনে নিন।</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach(array_slice($reviewRows, 0, 3) as $review)
                        @php
                            $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
                            $reviewer = $review['name'] ?? 'গ্রাহক';
                            $location = $review['location'] ?? '';
                            $verified = $review['verified'] ?? 'ভেরিফায়েড বায়ার';
                        @endphp
                        <div class="zombie-card p-6 flex flex-col justify-between">
                            <div>
                                @if($tpl->isEnabled('review_rating'))
                                    <div class="flex items-center text-amber-400 mb-3 gap-1 text-sm" aria-label="{{ $rating }} out of 5">
                                        @for($star = 1; $star <= 5; $star++)
                                            <i class="fa-solid fa-star {{ $star <= $rating ? '' : 'opacity-25' }}"></i>
                                        @endfor
                                    </div>
                                @endif
                                @if($tpl->isEnabled('review_text'))
                                    <p class="text-slate-700 text-sm leading-relaxed mb-4">"{{ $review['text'] ?? '' }}"</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                                @if($tpl->isEnabled('review_avatar'))
                                    @if(!empty($review['avatar']) && preg_match('~^(https?://|frontend/|public/)~', (string) $review['avatar']))
                                        <img class="w-10 h-10 rounded-full object-cover bg-slate-100" src="{{ $tpl->imageUrl((string) $review['avatar']) }}" alt="{{ $reviewer }}">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm">
                                            {{ mb_substr($reviewer, 0, 1) }}
                                        </div>
                                    @endif
                                @endif
                                <div>
                                    @if($tpl->isEnabled('review_name'))
                                        <h4 class="font-bold text-slate-900 text-sm">{{ $reviewer }}</h4>
                                    @endif
                                    @if($tpl->isEnabled('review_verified') || $tpl->isEnabled('review_location'))
                                        <span class="text-[11px] text-emerald-600 flex items-center gap-1 font-medium">
                                            @if($tpl->isEnabled('review_verified'))
                                                <i class="fa-solid fa-circle-check text-[10px]"></i> {{ $verified }}
                                            @endif
                                            @if($tpl->isEnabled('review_location') && $location !== '') ({{ $location }})@endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if(($tpl->isEnabled('faq_question') || $tpl->isEnabled('faq_answer')) && $faqRows !== [])
        <section class="py-14 bg-white border-t border-slate-200" id="faq">
            <div class="zombie-container max-w-3xl">
                <div class="text-center mb-8">
                    <span class="text-xs font-bold text-brand-red uppercase tracking-wider">সচরাচর জিজ্ঞাসা</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">সাধারণ প্রশ্ন ও উত্তর</h2>
                </div>
                <div class="space-y-3">
                    @foreach($faqRows as $faq)
                        <details class="group zombie-card p-4 rounded-xl cursor-pointer">
                            <summary class="font-bold text-slate-900 text-sm sm:text-base flex justify-between items-center list-none">
                                @if($tpl->isEnabled('faq_question'))
                                    <span>{{ $faq['question'] ?? '' }}</span>
                                @endif
                                <span class="text-slate-400 group-open:rotate-180 transition-transform">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </span>
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
</main>

@if($tpl->isEnabled('sticky_cta'))
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-3 shadow-[0_-4px_12px_rgba(0,0,0,0.08)]" data-purpose="mobile-sticky-bar">
        <div class="flex items-center justify-between gap-3">
            <div>
                @if($tpl->isEnabled('sticky_price') && $oldPrice > 0)
                    <span class="block text-[11px] text-slate-500 line-through">পূর্বের মূল্য: {{ $tpl->moneyBn($oldPrice) }}</span>
                @endif
                <span class="text-lg font-extrabold text-brand-red" id="sticky-price-display">{{ $tpl->moneyBn($currentPrice) }}</span>
            </div>
            <a class="zombie-btn-primary flex-1 py-3 text-sm font-bold shadow-md" href="#landing-order">
                <i class="fa-solid fa-cart-shopping mr-1.5"></i> {{ $tpl->text('sticky_cta', 'এখনই অর্ডার করুন') }}
            </a>
        </div>
    </div>
@endif

@push('scripts')
<script>
    (function () {
        var end = @json($countdownEnd);
        var display = document.getElementById('countdown-timer');

        if (!end || !display) {
            return;
        }

        var target = new Date(String(end).replace(' ', 'T')).getTime();
        if (isNaN(target)) {
            return;
        }

        function pad(value) {
            return value < 10 ? '0' + value : String(value);
        }

        function tick() {
            var remaining = Math.max(0, Math.floor((target - Date.now()) / 1000));
            var hours = Math.floor(remaining / 3600);
            var minutes = Math.floor((remaining % 3600) / 60);
            var seconds = remaining % 60;

            display.textContent = pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);

            if (remaining <= 0) {
                window.clearInterval(timer);
            }
        }

        tick();
        var timer = window.setInterval(tick, 1000);
    })();
</script>
@endpush
@endsection
