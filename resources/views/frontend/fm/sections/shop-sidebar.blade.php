{{--
    FM:SECTION shop-sidebar ------------------------------------------------
    Left column of the catalog: the filter form plus the offer card.

    Everything inside <form> is a real GET control that the /products route
    understands; nothing here is decorative. A block renders only while the
    shop can actually answer it - the rating rows wait for published reviews
    and the brand rows wait for products that carry a brand - so a visitor
    never meets a control whose only possible outcome is "no results".
    Selected state comes straight from the checked attribute, so it is right
    on first paint and needs no script.
-------------------------------------------------------------------------- --}}

@php
    $fmCategoryCounts = $categoryCounts ?? collect();
    $fmBrandCounts = $brandCounts ?? collect();
    $fmPriceCeiling = max(1, (int) ($priceCeiling ?? 0));
    $fmShowRating = (bool) ($hasReviewData ?? false);

    /*
     * Layout flavour.
     *
     * 'v1' is the original FreshMart panel. 'v2' is the work/code.html panel
     * and is used only by pages/products.blade.php; the search page passes
     * nothing and therefore keeps the old look.
     *
     * Only CHROME varies. Every input keeps the same name, id, value and
     * checked state in both flavours, and there is still exactly ONE
     * <form id="fmFilterForm"> - the whole point of the variant is that the
     * filter logic below has a single definition.
     */
    $fmV2 = ($variant ?? 'v1') === 'v2';

    // Brands that at least one published product uses. With an empty shop
    // assignment the whole block disappears instead of listing dead links.
    $fmFilterBrands = ($brands ?? collect())
        ->filter(static fn ($brand) => (int) ($fmBrandCounts[$brand->id] ?? 0) > 0)
        ->values();

    $fmDepartmentTotal = (int) $fmCategoryCounts->sum();

    // Route shape decides whether "Categories" is a radio group or a list of
    // links. $fmRouteName is resolved once in pages/products.blade.php and
    // arrives through the include, exactly like $fmType below.
    $fmPathScopedCategories = in_array((string) $fmRouteName, [
        'category', 'products.category', 'products.brand',
    ], true);

    $fmSliderMax = $fmMaxPrice > 0 ? (int) $fmMaxPrice : $fmPriceCeiling;
@endphp

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
                 there would filter nothing while the heading (before the fix
                 in pages/products.blade.php) happily renamed the page. Those
                 routes therefore link each department to its own page - the
                 same answer classic gives, which renders 392 category links
                 and zero radios on /category/premature.
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
                <div class="space-y-1.5 text-xs text-gray-700">

                    @if($fmPathScopedCategories)
                        <a class="fm-check-row" href="{{ route('products.page') }}"
                           @if($fmCategorySlug === '') aria-current="page" @endif>
                            <span class="fm-check-row__label">All categories</span>
                            <span class="fm-check-row__count">{{ $fmDepartmentTotal }}</span>
                        </a>

                        @foreach(($sidecategories ?? collect()) as $fmDepartment)
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

                        @foreach(($sidecategories ?? collect()) as $fmDepartment)
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

                <div class="grid grid-cols-2 gap-2 mt-3 {{ $fmV2 ? 'fm-amounts' : '' }}">
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
            </div>

            {{-- Rating: only while published products actually carry reviews --}}
            @if($fmShowRating)
                <div class="pt-2 border-t border-gray-100 {{ $fmV2 ? 'fm-sidebar__group' : '' }}">
                    <span class="text-xs font-bold text-gray-900 uppercase tracking-wider block mb-3">Rating</span>
                    <div class="space-y-2 text-xs">

                        @for($fmStars = 4; $fmStars >= 1; $fmStars--)
                            <label class="fm-check-row">
                                <input type="radio" name="rating" value="{{ $fmStars }}"
                                       {{ $fmRating === $fmStars ? 'checked' : '' }}>
                                <span class="fm-check-row__label">
                                    <span class="text-amber-500 whitespace-nowrap">
                                        @for($fmStar = 1; $fmStar <= 5; $fmStar++)
                                            <i class="bi {{ $fmStar <= $fmStars ? 'bi-star-fill' : 'bi-star' }} text-xs"></i>
                                        @endfor
                                    </span>
                                    <span class="font-bold text-gray-800 whitespace-nowrap">{{ $fmStars }}  & up</span>
                                </span>
                            </label>
                        @endfor

                    </div>
                </div>
            @endif

            {{-- Quick toggle: text left, control right --}}
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
