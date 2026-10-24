{{--
    FM:COMPONENT shop-sidebar
    =========================================================================
    The catalog filter column: the filter form plus (v1 only) the offer card.

    WHY A COMPONENT
    -------------------------------------------------------------------------
    This used to be frontend/fm/sections/shop-sidebar.blade.php, included with
    @include. A partial inherits the INCLUDING view's scope, so it could only be
    rendered by a page that had already declared ~16 locals
    ($fmRouteName, $fmCategorySlug, $fmBrandSlugs, $fmMinPrice, $fmMaxPrice,
    $fmRating, $fmInStock, $fmType, $categoryCounts, $brandCounts,
    $priceCeiling, $hasReviewData ...). Only pages/products.blade.php and
    pages/search.blade.php did that - each with its own copy of the same parse.
    That is why the panel could not be reused anywhere else.

    As a component it takes those as optional PROPS with real defaults, so

        <x-fm::shop-sidebar />                 the FreshMart panel (v1)
        <x-fm::shop-sidebar variant="v2" aside />   the work/code.html panel

    render a complete, correct panel on ANY page. Nothing has to be declared by
    the caller; every prop is an override.

    WHERE EACH FLAVOUR CAN RENDER
    -------------------------------------------------------------------------
    v1 is styled by fm-products.css + Tailwind, which are global: it renders
    correctly anywhere, wrapped in whatever column the page already has (that is
    how pages/search.blade.php uses it - inside a Bootstrap .col + .collapse).

    v2 is styled by fm-products-v2.css, whose rules are scoped to the shop
    layout, `.fm-page-shop` (its token block, the panel, the drawer, the
    toolbar). So a page that wants v2 opens that scope once on its own wrapper:

        <div class="fm-page-shop"> ... <x-fm::shop-sidebar variant="v2" aside /> ... </div>

    `aside` adds the shell column classes (col-12 col-lg-3 d-none d-lg-block) as
    well as the <aside> tag, so the panel is 3 of the 12 Bootstrap columns and
    only appears from lg up - below that the same form node is moved into the
    drawer. A page that has no 12-column row uses the plain flavour and passes
    no `aside`.

    The PRODUCT CARD is no longer part of this scope: it lives in
    fm-card-system.css, which every storefront page loads, so the same card
    renders on any page. The shop page's own sheets only carry what is shop
    specific - shell, panel, toolbar, drawer, pager.

    Props (all optional)
        variant         'v1'|'v2'   default 'v1'
        aside           bool        default false   wrap in <aside class="fm-shop-aside">
                                    (the v2 two-column column; the shop page needs it,
                                     the search page keeps its own Bootstrap column)
        filters         \App\Support\ShopFilters   default: the current request
        categoryList    Collection  default: ShopFilterData::categories()
                                    (categories holding a published product, by name)
        categoryCounts  Collection  default: ShopFilterData::categoryCounts()
        brandList       Collection  default: ShopFilterData::brands()
                                    (brands a published product uses, by name)
        brandCounts     Collection  default: ShopFilterData::brandCounts()
        priceCeiling    int         default: ShopFilterData::priceCeiling()
        hasReviewData   bool        default: ShopFilterData::hasReviewData()
        categorySlug    string      default: from filters
        brandSlugs      array       default: from filters
        minPrice        float       default: from filters
        maxPrice        float       default: from filters
        rating          int         default: from filters
        inStock         bool        default: from filters
        type            string      default: from filters

    NOTE ON THE TWO LIST PROPS
    -------------------------------------------------------------------------
    They are not named `categories` / `brands` on purpose. A component receives
    the view-SHARED data, and AppServiceProvider shares `$sidecategories` and
    `$brands` globally with a DIFFERENT meaning (every top-level category, every
    brand - unordered, unfiltered). A prop named `brands` would be
    indistinguishable from that shared variable inside this view, which is
    exactly how the first draft of this component silently rendered the wrong
    department order. See the note in the preamble below.

    WHAT MUST NOT CHANGE
    -------------------------------------------------------------------------
    1. ONE form node. The id is #fmFilterForm and the toolbar's two selects join
       it through form="fmFilterForm". public/frontend/js/fm-products.js MOVES
       this form into the phone drawer and back (sections/.../filters-drawer
       renders the empty shell), so a second copy of the form on the page would
       make the browser resolve form="fmFilterForm" to the first match and
       submit a stale, empty filter set. Render this component once per page.
    2. Every input keeps its name/id/value/checked as a real GET control. The
       panel works with JavaScript off: the drawer trigger is hidden by CSS
       under lg and the form stays in the page.
    3. A block renders only while the shop can answer it: the rating rows wait
       for published reviews, the brand rows wait for a brand in use. Never
       show a filter whose only possible outcome is "no results".
    =========================================================================
--}}

@php
    $fmV2     = ($variant ?? 'v1') === 'v2';
    $fmAside  = (bool) ($aside ?? false);

    /** @var \App\Support\ShopFilters $fmFilters */
    $fmFilters = $filters ?? \App\Support\ShopFilters::current();

    // Counters: the controller's numbers win when it passed them (listing
    // routes), otherwise the component fetches them itself - that is what
    // makes it usable on a page the controller never prepared.
    $fmCategoryCounts = $categoryCounts ?? \App\Support\ShopFilterData::categoryCounts();
    $fmBrandCounts    = $brandCounts ?? \App\Support\ShopFilterData::brandCounts();
    $fmPriceCeiling   = max(1, (int) ($priceCeiling ?? \App\Support\ShopFilterData::priceCeiling()));
    $fmShowRating     = (bool) ($hasReviewData ?? \App\Support\ShopFilterData::hasReviewData());

    // Departments and brands come from ShopFilterData - NOT from the
    // view-shared $sidecategories / $brands.
    //
    // AppServiceProvider shares those two names globally and they are NOT the
    // lists a listing page needs: the shared $sidecategories is every top-level
    // category (parent_id = 0, no ordering, no published-product filter), while
    // the sidebar must show the categories that actually hold a product,
    // ordered by name - the same list FrontendController::withListingSidebar()
    // used to pass.
    //
    // A Blade component receives the view-SHARED data but not the including
    // page's view data, so a bare `$sidecategories ?? ...` inside a component
    // silently reads the shared one. Measured: the department radios on
    // /products came out in a different order (caught by rendering the previous
    // commit on a second port and diffing the HTML). ShopFilterData is the one
    // definition of both lists; the props exist for a page that wants a
    // narrower list of its own.
    $sidecategories = $categoryList ?? \App\Support\ShopFilterData::categories();
    $brands         = $brandList ?? \App\Support\ShopFilterData::brands();

    // Current filter state: props beat the request, so a page that already
    // resolved a scope (the search page) can hand it over.
    $fmCategorySlug = $categorySlug ?? $fmFilters->categorySlug;
    $fmBrandSlugs   = $brandSlugs ?? $fmFilters->brandSlugs;
    $fmMinPrice     = (float) ($minPrice ?? $fmFilters->minPrice);
    $fmMaxPrice     = (float) ($maxPrice ?? $fmFilters->maxPrice);
    $fmRating       = (int) ($rating ?? $fmFilters->rating);
    $fmInStock      = (bool) ($inStock ?? $fmFilters->inStock);
    $fmType         = (string) ($type ?? $fmFilters->type);

    /*
     * Route shape decides whether "Categories" is a radio group or a list of
     * links. /products is query scoped: the radio narrows the list in place.
     * /category/{slug}, /products/category/{slug} and /products/brand/{slug}
     * are PATH scoped: FrontendController reads only its own route parameter,
     * so a radio submitted from there would filter nothing - those routes link
     * each department to its own page instead.
     */
    $fmPathScopedCategories = $fmFilters->pathScopedCategories();

    // Brands that at least one published product uses. With an empty shop
    // assignment the whole block disappears instead of listing dead links.
    $fmFilterBrands = $brands
        ->filter(static fn ($brand) => (int) ($fmBrandCounts[$brand->id] ?? 0) > 0)
        ->values();

    $fmDepartmentTotal = (int) $fmCategoryCounts->sum();

    $fmSliderMax = $fmMaxPrice > 0 ? (int) $fmMaxPrice : $fmPriceCeiling;
@endphp

@if($fmAside)
{{-- 3 of the 12 shell columns, first child of the row. The flex row paints in
     DOM order, so being first is what puts the panel on the LEFT - no `order`
     or grid-column override, which keeps the reading order (filters, then
     results) identical to what the eye sees.
     d-none d-lg-block: below lg the panel is hidden and the same form node is
     moved into the drawer by fm-products.js. --}}
<aside class="fm-shop-aside col-12 col-lg-3 d-none d-lg-block" data-fm-filter-home>
@endif

<div class="space-y-4 fm-shop-sidebar {{ $fmV2 ? 'fm-shop-sidebar--v2' : '' }}">

    {{-- Filter card --}}
    <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm {{ $fmV2 ? 'fm-sidebar' : '' }}">
        {{--
            Submit back to the page the visitor is actually on.

            The action used to be route('products.page'), which is correct on
            /products but sends every sort or price submit from /category/{slug}
            and /products/brand/{slug} over to /products. On a brand page no
            category checkbox is checked, so the brand context vanished with it
            and the visitor suddenly saw the whole catalogue.

            request()->url() is the current path with no query: a GET form
            replaces the query string wholesale, so only the path can carry the
            route context. Anything the form does not own is carried forward as
            a hidden input instead of being silently dropped on submit.
        --}}
        <form method="get" action="{{ request()->url() }}" id="fmFilterForm" class="space-y-5">
            {{-- brand[] is carried only while the Brand block did not render,
                 so a real brand checkbox can never be duplicated here. The
                 checkbox carries it whenever the block is on screen; this
                 covers the case where the block is hidden because no published
                 product carries a brand, which is exactly when dropping the
                 parameter would turn "0 results" into "the whole catalogue". --}}
            @unless($fmFilterBrands->isNotEmpty())
                @foreach((array) request('brand') as $fmCarriedBrand)
                    @if(trim((string) $fmCarriedBrand) !== '')
                        <input type="hidden" name="brand[]" value="{{ $fmCarriedBrand }}">
                    @endif
                @endforeach
            @endunless

            {{-- Keeps the homepage "see more" style links alive across a filter change --}}
            @if($fmType !== '')
                <input type="hidden" name="type" value="{{ $fmType }}">
            @endif

            {{-- Filter header --}}
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    @if($fmV2)
                        <svg class="fm-sidebar__title-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                             aria-hidden="true">
                            <path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                        </svg>
                    @else
                        <i class="bi bi-sliders2 text-[#1b5e20] text-base"></i>
                    @endif
                    <h2 class="text-base font-bold text-gray-900 m-0 {{ $fmV2 ? 'fm-sidebar__title tracking-tight' : '' }}">
                        {{ $fmV2 ? 'Filter Products' : 'Filters' }}
                    </h2>
                </div>
                <a class="text-xs font-semibold text-[#1b5e20] hover:underline {{ $fmV2 ? 'fm-sidebar__reset' : '' }}"
                   href="{{ route('products.page') }}">
                    {{ $fmV2 ? 'Reset All' : 'Reset all' }}
                </a>
            </div>

            {{-- Categories -------------------------------------------------
                 /products is query scoped: the radio narrows the list in
                 place and the controller reads ?category= back.

                 /category/{slug}, /products/category/{slug} and
                 /products/brand/{slug} are PATH scoped: FrontendController
                 reads only its own route parameter, so a radio submitted from
                 there would filter nothing while the heading happily renamed
                 the page. Those routes therefore link each department to its
                 own page - the same answer classic gives, which renders 392
                 category links and zero radios on /category/premature.
            --}}
            <div class="{{ $fmV2 ? 'fm-sidebar__group' : '' }}">
                {{--
                    code.html: "Departments" is text-slate-500 (muted), not the
                    near-black the shared markup paints. Only the v2 flavour
                    changes; v1 keeps its dark label.
                --}}
                <span class="text-xs font-bold uppercase tracking-wider block mb-3 {{ $fmV2 ? 'fm-sidebar__label text-slate-500' : 'text-gray-900' }}">
                    {{ $fmV2 ? 'Departments' : 'Categories' }}
                </span>
                <div class="{{ $fmV2 ? 'space-y-[14px]' : 'space-y-1.5' }} text-xs text-gray-700">

                    @if($fmPathScopedCategories)
                        <a class="fm-check-row" href="{{ route('products.page') }}"
                           @if($fmCategorySlug === '') aria-current="page" @endif>
                            <span class="fm-check-row__label">All categories</span>
                            <span class="fm-check-row__count">{{ $fmDepartmentTotal }}</span>
                        </a>

                        @foreach($sidecategories as $fmDepartment)
                            <a class="fm-check-row" href="{{ route('category', $fmDepartment->slug) }}"
                               @if($fmCategorySlug === $fmDepartment->slug) aria-current="page" @endif>
                                <span class="fm-check-row__label">{{ $fmDepartment->name }}</span>
                                <span class="fm-check-row__count">{{ (int) ($fmCategoryCounts[$fmDepartment->id] ?? 0) }}</span>
                            </a>
                        @endforeach
                    @else
                        <label class="fm-check-row">
                            <input type="radio" name="category" value="" {{ $fmCategorySlug === '' ? 'checked' : '' }}>
                            <span class="fm-check-row__label">All categories</span>
                            <span class="fm-check-row__count">{{ $fmDepartmentTotal }}</span>
                        </label>

                        @foreach($sidecategories as $fmDepartment)
                            <label class="fm-check-row">
                                <input type="radio" name="category" value="{{ $fmDepartment->slug }}"
                                       {{ $fmCategorySlug === $fmDepartment->slug ? 'checked' : '' }}>
                                <span class="fm-check-row__label">{{ $fmDepartment->name }}</span>
                                <span class="fm-check-row__count">{{ (int) ($fmCategoryCounts[$fmDepartment->id] ?? 0) }}</span>
                            </label>
                        @endforeach
                    @endif

                </div>
            </div>

            {{-- Price ceiling: slider is the quick path, the two amount
                 fields are the precise one (they also work without JS) --}}
            <div class="pt-2 border-t border-gray-100 {{ $fmV2 ? 'fm-sidebar__group' : '' }}">
                <div class="flex justify-between items-center mb-2 {{ $fmV2 ? 'fm-price-head' : '' }}">
                    <span class="text-xs font-bold uppercase tracking-wider {{ $fmV2 ? 'fm-sidebar__label text-slate-800' : 'text-gray-900' }}">Max price</span>
                    <span class="text-xs font-bold {{ $fmV2 ? 'fm-price-value text-emerald-700' : 'text-[#1b5e20]' }}" id="fmPriceValue">
                        Tk {{ number_format($fmSliderMax) }}
                    </span>
                </div>

                <input type="range" class="w-full fm-price-range" id="fmPriceRange"
                       min="0" max="{{ $fmPriceCeiling }}" step="10" value="{{ $fmSliderMax }}">

                <div class="flex justify-between text-xs text-gray-400 font-semibold mt-1 {{ $fmV2 ? 'fm-price-scale' : '' }}">
                    <span>Tk 0</span>
                    <span>Tk {{ number_format($fmPriceCeiling) }}+</span>
                </div>

                {{-- Quick ranges fill the amount fields; filtering still
                     waits for the Apply button below. --}}
                <div class="grid grid-cols-2 gap-2 mt-3 {{ $fmV2 ? 'fm-price-tiers' : '' }}">
                    <button type="button"
                            class="py-1.5 px-2 text-center rounded-lg bg-gray-100 hover:bg-emerald-100 text-xs font-semibold text-gray-700 hover:text-[#1b5e20] transition-colors {{ $fmV2 ? 'fm-price-pill' : '' }}"
                            data-fm-price-pill data-min="" data-max="100">Up to Tk 100</button>
                    <button type="button"
                            class="py-1.5 px-2 text-center rounded-lg bg-gray-100 hover:bg-emerald-100 text-xs font-semibold text-gray-700 hover:text-[#1b5e20] transition-colors {{ $fmV2 ? 'fm-price-pill' : '' }}"
                            data-fm-price-pill data-min="100" data-max="300">Tk 100 - 300</button>
                    <button type="button"
                            class="py-1.5 px-2 text-center rounded-lg bg-gray-100 hover:bg-emerald-100 text-xs font-semibold text-gray-700 hover:text-[#1b5e20] transition-colors {{ $fmV2 ? 'fm-price-pill' : '' }}"
                            data-fm-price-pill data-min="300" data-max="500">Tk 300 - 500</button>
                    <button type="button"
                            class="py-1.5 px-2 text-center rounded-lg bg-gray-100 hover:bg-emerald-100 text-xs font-semibold text-gray-700 hover:text-[#1b5e20] transition-colors {{ $fmV2 ? 'fm-price-pill' : '' }}"
                            data-fm-price-pill data-min="500" data-max="">Over Tk 500</button>
                </div>

                @unless($fmV2)
                {{-- Min/Max precise inputs: only in v1; reference v2 omits them. --}}
                <div class="grid grid-cols-2 gap-2 mt-3 fm-amounts">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider" for="fmMinPrice">Minimum</label>
                        <input type="number" min="0" step="10" id="fmMinPrice" name="min_price"
                               class="mt-1 w-full bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold px-2.5 py-2 focus:border-[#1b5e20] focus:ring-0"
                               placeholder="Tk 0"
                               value="{{ $fmMinPrice > 0 ? (int) $fmMinPrice : '' }}"
                               {{ $fmMinPrice > 0 ? '' : 'disabled' }}>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider" for="fmMaxPrice">Maximum</label>
                        <input type="number" min="0" step="10" id="fmMaxPrice" name="max_price"
                               class="mt-1 w-full bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold px-2.5 py-2 focus:border-[#1b5e20] focus:ring-0"
                               placeholder="Tk {{ number_format($fmPriceCeiling) }}"
                               value="{{ $fmMaxPrice > 0 ? (int) $fmMaxPrice : '' }}"
                               {{ $fmMaxPrice > 0 ? '' : 'disabled' }}>
                    </div>
                </div>
                @endunless
            </div>

            {{-- Rating: only while published products actually carry reviews.
                 Reference shows 4.5 & up and 4.0 & up with star icons.
                 14px vertical spacing for v2. --}}
            @if($fmShowRating)
                <div class="pt-2 border-t border-gray-100 {{ $fmV2 ? 'fm-sidebar__group' : '' }}">
                    <span class="text-xs font-bold uppercase tracking-wider block mb-3 {{ $fmV2 ? 'fm-sidebar__label' : 'text-gray-900' }}">Rating</span>
                    <div class="{{ $fmV2 ? 'space-y-[14px]' : 'space-y-2' }} text-xs">

                        {{-- 4.5 & up --}}
                        <label class="fm-check-row">
                            <input type="radio" name="rating" value="4.5"
                                   {{ $fmRating == 4.5 ? 'checked' : '' }}>
                            <span class="fm-check-row__label">
                                <span class="text-amber-500 whitespace-nowrap">
                                    <i class="bi bi-star-fill text-xs"></i>
                                    <i class="bi bi-star-fill text-xs"></i>
                                    <i class="bi bi-star-fill text-xs"></i>
                                    <i class="bi bi-star-fill text-xs"></i>
                                    <i class="bi bi-star-half text-xs"></i>
                                </span>
                                <span class="font-bold text-gray-800 whitespace-nowrap">4.5 & up</span>
                            </span>
                        </label>

                        {{-- 4.0 & up --}}
                        <label class="fm-check-row">
                            <input type="radio" name="rating" value="4"
                                   {{ $fmRating == 4 ? 'checked' : '' }}>
                            <span class="fm-check-row__label">
                                <span class="text-amber-500 whitespace-nowrap">
                                    <i class="bi bi-star-fill text-xs"></i>
                                    <i class="bi bi-star-fill text-xs"></i>
                                    <i class="bi bi-star-fill text-xs"></i>
                                    <i class="bi bi-star-fill text-xs"></i>
                                    <i class="bi bi-star text-xs text-gray-300"></i>
                                </span>
                                <span class="font-bold text-gray-800 whitespace-nowrap">4.0 & up</span>
                            </span>
                        </label>

                    </div>
                </div>
            @endif

            {{-- Quick toggle: text left, control right.
                 The reference mockup also shows a "Same-Day Express" checkbox, but no
                 controller reads an `express` parameter, so shipping it would be a
                 control that filters nothing. Omitted until the backend exists. --}}
            <div class="pt-2 border-t border-gray-100 {{ $fmV2 ? 'fm-sidebar__group' : '' }}">
                <label class="fm-check-row fm-check-row--split">
                    <span class="fm-check-row__label text-xs font-bold text-gray-800">In stock only</span>
                    <input type="checkbox" name="in_stock" value="1" {{ $fmInStock ? 'checked' : '' }}>
                </label>
            </div>

            {{-- Partner brands: only brands some published product uses.
                 In the v2 panel this is a <details> accordion, which is the
                 design's chevron - and which still opens and closes with
                 JavaScript off, unlike a div with a click handler. --}}
            @if($fmFilterBrands->isNotEmpty())
                @if($fmV2)
                    <details class="pt-2 border-t border-gray-100 fm-sidebar__group" open>
                        <summary class="fm-sidebar__summary">
                            <span class="fm-sidebar__label mb-0">Brand</span>
                            <svg class="fm-sidebar__chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 aria-hidden="true">
                                <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="2"/>
                            </svg>
                        </summary>
                        <div class="space-y-2 text-xs text-gray-700 mt-3">
                            @foreach($fmFilterBrands as $fmFilterBrand)
                                <label class="fm-check-row">
                                    <span class="fm-check-row__label">
                                        <input type="checkbox" name="brand[]" value="{{ $fmFilterBrand->slug }}"
                                               {{ in_array($fmFilterBrand->slug, $fmBrandSlugs, true) ? 'checked' : '' }}>
                                        {{ $fmFilterBrand->name }}
                                    </span>
                                    <span class="fm-check-row__count">{{ (int) ($fmBrandCounts[$fmFilterBrand->id] ?? 0) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </details>
                @else
                    <div class="pt-2 border-t border-gray-100">
                        <span class="text-xs font-bold text-gray-900 uppercase tracking-wider block mb-3">Brand</span>
                        <div class="space-y-2 text-xs text-gray-700">

                            @foreach($fmFilterBrands as $fmFilterBrand)
                                <label class="fm-check-row">
                                    <span class="fm-check-row__label">
                                        <input type="checkbox" name="brand[]" value="{{ $fmFilterBrand->slug }}"
                                               {{ in_array($fmFilterBrand->slug, $fmBrandSlugs, true) ? 'checked' : '' }}>
                                        {{ $fmFilterBrand->name }}
                                    </span>
                                    <span class="fm-check-row__count">{{ (int) ($fmBrandCounts[$fmFilterBrand->id] ?? 0) }}</span>
                                </label>
                            @endforeach

                        </div>
                    </div>
                @endif
            @endif

            {{-- Apply: one button for the whole card, matching the design --}}
            <button type="submit"
                    class="w-full py-3 rounded-full bg-[#1b5e20] hover:bg-[#144618] text-white text-xs font-bold tracking-wide shadow-sm transition-all {{ $fmV2 ? 'fm-sidebar__apply' : '' }}">
                Apply filters
            </button>

        </form>
    </div>

    {{-- Offer card: links to the discounted listing the route already serves.
         The v2 panel has no room for it (work/code.html shows the filter and
         nothing else), so the redesigned page leaves it out. --}}
    @unless($fmV2)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1b5e20] to-[#2e7d32] p-5 text-white shadow-md">
            <span class="px-2.5 py-0.5 rounded-full bg-amber-400 text-slate-900 text-xs font-extrabold uppercase inline-block mb-2">
                Special offers
            </span>
            <h3 class="text-base font-bold text-white leading-tight mb-1">
                Discounted products
            </h3>
            <p class="text-xs text-emerald-100 mb-3">
                Get your favorite products now at lower prices than before.
            </p>
            <div class="flex items-center justify-between pt-1">
                <span class="text-xs font-semibold text-emerald-200">Biggest discounts in one click</span>
                <a href="{{ route('products.page', ['type' => 'discount']) }}"
                   class="w-8 h-8 rounded-full bg-white text-[#1b5e20] flex items-center justify-center hover:scale-105 transition-transform"
                   title="See discounted products" aria-label="See discounted products">
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    @endunless

</div>

@if($fmAside)
</aside>
@endif
