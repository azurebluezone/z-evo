{{--
    FM:SECTION shop-toolbar ------------------------------------------------
    Above the result grid: view switcher, honest result count and the two
    selects that shape the listing.

    Both selects belong to the sidebar form through the form attribute, so
    one GET form owns every filter on the page and one submit carries them
    all. Sort and page size change immediately (data-fm-submit), price and
    category wait for the Apply button.
-------------------------------------------------------------------------- --}}

@php
    $fmSortBy = (string) request('sort_by', '');
    $fmPerPage = (int) request('per_page', 24);

    $fmSortOptions = [
        ''           => 'Default (newest first)',
        'price_lth'  => 'Price: low to high',
        'price_htl'  => 'Price: high to low',
        'latest'     => 'Newest first',
        'oldest'     => 'Oldest first',
    ];

    $fmPerPageOptions = [4, 8, 16, 24, 32, 64, 80, 100];

    /*
     * Layout flavour.
     *
     * 'v1' is the original FreshMart toolbar. 'v2' is the work/code.html one
     * and is used only by pages/products.blade.php; search.blade.php passes
     * nothing and keeps the old look.
     *
     * BOTH flavours render the same two selects with the same names, the
     * same form="fmFilterForm" owner and the same data-fm-submit hook, so the
     * page script that drives them is shared. The v2 panel simply drops the
     * "Per page" select, which the reference does not show - the value is
     * still accepted in the query string and still honoured by the
     * controller, it just has no widget.
     */
    $fmV2 = ($variant ?? 'v1') === 'v2';

    // The counter the v2 toolbar prints, in the reference's phrasing.
    $fmShowing = $products->count();
    $fmTotal   = $products->total();
@endphp

<div class="fm-shop-toolbar" data-section="shop-toolbar">

    <div class="{{ $fmV2 ? 'fm-shop-toolbar__left' : 'flex items-center gap-3 w-full sm:w-auto' }}">

        @if($fmV2)
            {{-- Phones and tablets only: the drawer trigger. It is hidden by
                 CSS from lg up, where the sidebar is already on screen. --}}
            <button type="button" class="fm-filter-open" data-fm-open-filters
                    aria-controls="fmShopFiltersDrawer" aria-expanded="false">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                          stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
                <span>Filters</span>
                @if($fmHasActiveFilter ?? false)
                    <span class="fm-filter-open__count">!</span>
                @endif
            </button>
        @endif

        <div class="fm-view-switch" role="group" aria-label="View type">
            <button type="button" class="fm-view-btn is-active" data-fm-view="grid"
                    aria-pressed="true" title="Grid view" aria-label="Grid view">
                @if($fmV2)
                    <svg fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 4h4v4H4V4zm6 0h4v4h-4V4zm6 0h4v4h-4V4zM4 10h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4zM4 16h4v4H4v-4zm6 0h4v4h-4v-4zm6 0h4v4h-4v-4z"/>
                    </svg>
                @else
                    <i class="bi bi-grid-fill"></i>
                @endif
            </button>
            <button type="button" class="fm-view-btn" data-fm-view="list"
                    aria-pressed="false" title="List view" aria-label="List view">
                @if($fmV2)
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"
                              stroke-linejoin="round" stroke-width="2"/>
                    </svg>
                @else
                    <i class="bi bi-view-list"></i>
                @endif
            </button>
        </div>

        @if($fmV2)
            {{-- The reference says "Displaying N products". A paginator only
                 knows the page it is on and the grand total, so both are
                 printed: what is on screen, and what there is in total. --}}
            <p class="fm-shop-toolbar__count">
                Displaying <strong>{{ number_format($fmShowing) }}</strong>
                @if($fmTotal > $fmShowing)
                    of <strong>{{ number_format($fmTotal) }}</strong>
                @endif
                products
            </p>
        @else
            <span class="text-xs font-semibold text-gray-600">
                Total <strong class="text-gray-900">{{ number_format($fmTotal) }}</strong> products found
            </span>
        @endif
    </div>

    {{-- Sort & page size --}}
    <div class="{{ $fmV2 ? 'fm-shop-toolbar__right' : 'flex items-center gap-2 sm:gap-3 w-full sm:w-auto justify-end flex-wrap' }}">

        @unless($fmV2)
            {{-- The reference has no page-size widget; the v2 toolbar hides
                 it, but the parameter itself keeps working for anyone who
                 links to per_page=8 by hand. --}}
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-gray-500 whitespace-nowrap" for="fmPerPage">Per page:</label>
                <select id="fmPerPage" name="per_page" form="fmFilterForm" data-fm-submit
                        class="bg-gray-50 border border-gray-200 text-gray-900 text-xs font-bold rounded-xl py-2 px-3 focus:ring-0 focus:border-[#1b5e20] cursor-pointer">
                    @foreach($fmPerPageOptions as $fmPerPageOption)
                        <option value="{{ $fmPerPageOption }}" {{ $fmPerPage === $fmPerPageOption ? 'selected' : '' }}>
                            {{ $fmPerPageOption }}
                        </option>
                    @endforeach

                    {{-- A page size outside the list (for example the "show all"
                         link with per_page=9999) must stay visible instead of
                         silently snapping back to the first option. --}}
                    @if($fmPerPage > 0 && !in_array($fmPerPage, $fmPerPageOptions, true))
                        <option value="{{ $fmPerPage }}" selected>{{ $fmPerPage }}</option>
                    @endif
                </select>
            </div>
        @endunless

        <div class="{{ $fmV2 ? 'fm-sort-wrap' : 'flex items-center gap-2' }}">
            <label class="{{ $fmV2 ? 'fm-sort-label' : 'text-xs font-semibold text-gray-500 whitespace-nowrap' }}"
                   for="fmSortBy">Sort by:</label>
            <select id="fmSortBy" name="sort_by" form="fmFilterForm" data-fm-submit
                    class="{{ $fmV2 ? '' : 'bg-gray-50 border border-gray-200 text-gray-900 text-xs font-bold rounded-xl py-2 px-3 focus:ring-0 focus:border-[#1b5e20] cursor-pointer' }}">
                @foreach($fmSortOptions as $fmSortValue => $fmSortLabel)
                    <option value="{{ $fmSortValue }}" {{ $fmSortBy === $fmSortValue ? 'selected' : '' }}>
                        {{ $fmSortLabel }}
                    </option>
                @endforeach
            </select>
            @if($fmV2)
                <svg class="fm-sort-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                     aria-hidden="true">
                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2"/>
                </svg>
            @endif
        </div>
    </div>

</div>
