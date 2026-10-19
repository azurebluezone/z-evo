{{--
    FM PAGE home (redesigned body)
    =========================================================================
    The home page body rebuilt to work/home.html. The header and the footer
    are NOT part of this change - the layout pulls them in from
    frontend/fm/partials/header and frontend/fm/partials/footer.

    FrontendController::frontendHome() is unchanged too. It already supplies
    every list this page draws:

        banners, featured_categories, featured_brands, featured_products,
        best_selling_products, discount_products, recommended_products,
        collections, flash_sale, flash_sale_products, testimonials, sections

    so the controller, the routes and the database are all as they were.

    WHAT MOVED
    -------------------------------------------------------------------------
    old order                          new order (work/home.html)
    ---------------------------------  -------------------------------------
    slider                            home/hero
    categories (brands inside it)     home/categories
    hero                              home/flash-sale     <- new section
    service-strip                     home/brands         <- new section
    recommended                       3 x home/product-rail
    product-feeds
    promo-banner
    collections                       home/trust-row
    testimonials                      home/events
    guarantees
    events
    newsletter                        home/newsletter

    Nothing was deleted. sections/service-strip, sections/hero,
    sections/promo-banner, sections/collections, sections/testimonials and
    sections/guarantees are all still on disk and still used elsewhere:

        service-strip   products.blade.php
        guarantees      about-us, contact, search

    They are simply no longer on the home page, because the new design has
    no block for them.

    VISIBILITY
    -------------------------------------------------------------------------
    The admin "Home Settings" screen still decides what shows. The same
    $fmShow()/$fmTitle() helpers are used, against the same $sections keys
    the classic page reads, so every existing toggle keeps working and the
    page cannot 500 on a fresh install where a setting is null.
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@section('title', 'Home')

{{-- MetaText::plain() because the description field can hold markup: @section
     escapes once and seo-meta escapes again, and crawlers would read the raw
     tags as text. --}}
@section('meta_description', \App\Support\MetaText::plain($generalsetting->description) ?: 'Trusted online shopping platform of Bangladesh. Thousands of products in one place, fast delivery and easy payment.')

@push('css')
    {{--
        Page sheet. Pushed on the 'css' stack, so it loads after every shared
        file in layouts.blade.php and before fm-product-card.css (which is
        linked last and only styles the v1 card this page no longer uses).
    --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-home-v2.css') }}">
@endpush

@section('content')

    @php
        // Section visibility is decided by the admin "Home Settings" screen.
        // Both helpers mirror the previous home page exactly: an unknown key
        // falls back to the given default instead of throwing.
        $fmShow = static function (string $key, bool $default = true) use ($sections) {
            return (bool) ($sections[$key]['show'] ?? $default);
        };
        $fmTitle = static function (string $key, string $fallback = '') use ($sections) {
            return (string) ($sections[$key]['title'] ?? $fallback);
        };
    @endphp

    {{-- Every block below carries .fm-page-home's siblings inside .fm-page-home
         itself; the wrapper scopes fm-home-v2.css to this page alone. --}}
    <div class="fm-page-home">

        {{-- FM:SECTION hero ================================================
             Full-width banner slider, first block under the header.
             ============================================================== --}}
        @include('frontend.fm.sections.home.hero')
        {{-- /FM:SECTION hero --}}

        {{-- FM:SECTION categories ======================================== --}}
        @if($fmShow('category'))
            @include('frontend.fm.sections.home.categories')
        @endif
        {{-- /FM:SECTION categories --}}

        {{-- FM:SECTION flash-sale =========================================
             New in this design: the gradient campaign banner with a live
             countdown over the deal cards. Self-hides when the shop has no
             sale running or scheduled.
             ============================================================== --}}
        @if($fmShow('flash_sale'))
            @include('frontend.fm.sections.home.flash-sale')
        @endif
        {{-- /FM:SECTION flash-sale --}}

        {{-- FM:SECTION recommended ======================================
             One rail, three cards' worth of copy, four columns wide.
             ============================================================== --}}
        @include('frontend.fm.sections.home.product-rail', [
            'title'    => $fmTitle('recommended', 'Handpicked products'),
            'products' => $recommended_products ?? collect(),
            'link'     => route('products.page'),
            'linkText' => 'View all products',
        ])

        {{-- FM:SECTION featured ========================================= --}}
        @include('frontend.fm.sections.home.product-rail', [
            'title'    => $fmTitle('featured', 'Featured products'),
            'products' => $featured_products ?? collect(),
            'link'     => route('products.page', ['type' => 'featured']),
            'linkText' => 'View all',
        ])

        {{-- FM:SECTION best-selling ======================================= --}}
        @include('frontend.fm.sections.home.product-rail', [
            'title'    => $fmTitle('best_selling', 'Top sellers'),
            'products' => $best_selling_products ?? collect(),
            'link'     => route('products.page', ['type' => 'best-selling']),
            'linkText' => 'View all',
        ])

        {{-- FM:SECTION discounted ========================================= --}}
        @include('frontend.fm.sections.home.product-rail', [
            'title'    => $fmTitle('discount_products', 'Discounted products'),
            'products' => $discount_products ?? collect(),
            'link'     => route('products.page', ['type' => 'discount']),
            'linkText' => 'View all',
        ])
      {{-- FM:SECTION brands ============================================
             Moved here on purpose: "Shop by Brand" used to sit between the
             category grid and the product rails, cutting the shopping flow
             in half. Shoppers now meet every product first and reach the
             brand filter only after the catalogue, right above the footer.
             ============================================================== --}}
        @if($fmShow('brands'))
            @include('frontend.fm.sections.home.brands')
        @endif
        {{-- /FM:SECTION brands --}}
   {{-- FM:SECTION events ============================================ --}}
        @include('frontend.fm.sections.home.events')
        {{-- /FM:SECTION events --}}

        {{-- FM:SECTION trust-row =========================================
             The four promises. Layout from the reference; wording from
             App\Support\ShopPromises, the same source the header strip and
             the guarantees block use.
             ============================================================== --}}
        @include('frontend.fm.sections.home.trust-row')
        {{-- /FM:SECTION trust-row --}}

  

     
        {{-- FM:SECTION newsletter ========================================= --}}
        @include('frontend.fm.sections.home.newsletter')
        {{-- /FM:SECTION newsletter --}}

    </div>

@endsection

@push('script')
{{--
    Two behaviours the new design promises that no existing file owns:

      1. the hero slides / dots / arrows,
      2. the flash-sale countdown, which has to be LIVE - the reference
         hard-codes 08:42:19, which on a real shop would be a lie.

    Both are progressive enhancements. The hero already renders its first
    slide as .is-active and the countdown is already painted server-side
    from end_time, so with JavaScript off the page is a correct, static,
    fully clickable banner page - nothing is lost, it just stops moving.

    The code lives in public/frontend/js/fm-home.js. It used to be an inline
    block here, which meant the browser re-downloaded it on every page view
    and the page file could not be read without scrolling past hundreds of
    lines of JavaScript. Nothing about the behaviour changed.
--}}
<script src="{{ asset('frontend/js/fm-home.js') }}"></script>
@endpush