{{--
    FM PAGE search
    =========================================================================
    Search results, reached from the header search bar.

    The reference export ships search.html as its own document, but that file is
    the same shop listing the /products page already implements: a filter
    sidebar, a grid/list switch, a result count and a pager. Only the heading
    and the query chip differ. So this page delegates to the proven shop page
    rather than growing a second copy of the grid that would then drift.

    Two differences from the plain shop page are applied first:
      - the heading names the query instead of the shop;
      - the query is presented as a removable chip, matching the reference's
        active-filter strip.

    The classic page this replaces wrote its Bengali result summary straight
    into the markup. Every string on this page is English.

    The listing itself needs categoryCounts / priceCeiling / hasReviewData,
    which FrontendController::search() now passes through withListingSidebar().

    To bring the old design back: flip "Front-end Template" in the admin panel
    (Website Setup -> Front-end Template).
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@php
    /*
     * Everything the shop page computes from its own request is reproduced
     * here so the delegated view behaves identically: the sidebar counters, the
     * price ceiling and the rating block depend on the query string, and the
     * heading has to name the search rather than the shop.
     */
    $fmTerm        = trim((string) ($term ?? ''));

    // A search may arrive carrying a category scope. The category resolution
    // below has to prefer it, otherwise the page reports the wrong department
    // while listing the right products.
    $fmSearchScope = trim((string) ($searchCategorySlug ?? request('category', '')));
    $fmCategorySlug = $fmSearchScope;
    $fmBrandSlugs  = array_values(array_filter((array) request('brand')));
    $fmMinPrice    = (float) request('min_price', 0);
    $fmMaxPrice    = (float) request('max_price', 0);
    $fmRating      = (int) request('rating', 0);
    $fmInStock     = request('in_stock') === '1';
    $fmType        = trim((string) request('type', ''));
    $fmTypeLabels  = [
        'featured'      => 'Featured products',
        'best-selling'  => 'Best selling',
        'discount'      => 'Discounted products',
        'recommended'   => 'Recommended for you',
    ];
    $fmTypeLabel   = $fmTypeLabels[$fmType] ?? null;
    /*
     * The shop page resolves the active route once and every included section
     * reads that name to decide its shape - the sidebar links to department
     * pages on a path-scoped route but posts a radio on a query-scoped one,
     * and that switch is keyed on the route. Search is query-scoped, so it is
     * declared explicitly rather than left undefined.
     */
    $fmRouteName = 'search';

    $fmSidebarCats = $sidecategories ?? collect();
    $fmActiveCategory = $fmCategorySlug !== ''
        ? $fmSidebarCats->firstWhere('slug', $fmCategorySlug)
        : null;
    $fmActiveBrands = ($brands ?? collect())
        ->filter(static fn ($brand) => in_array($brand->slug, $fmBrandSlugs, true))
        ->values();

    // shop-header's breadcrumb also reads $fmHeading. It normally derives that
    // from the query string on the page itself; here the search term names the
    // page, so both the fallback and the override are supplied and the two
    // always agree.
    $fmSearchHeading = $fmHeading = $fmTerm !== '' ? 'Results for "' . $fmTerm . '"' : 'Search';

    // shop-toolbar reads this exact name to decide whether to offer a reset, so
    // it has to exist in scope here too even though this page renders its own
    // chip strip.
    $fmHasActiveFilter = $fmHasAnyFilter = $fmActiveCategory !== null
        || $fmActiveBrands->isNotEmpty()
        || $fmMinPrice > 0
        || $fmMaxPrice > 0
        || $fmRating > 0
        || $fmInStock;
@endphp

@section('title', $pageTitle ?? 'Search')
@section('meta_description', \App\Support\MetaText::plain($metaDescription ?? '') ?: 'Search products')

@section('content')
    @include('frontend.fm.sections.shop-header', ['fmHeadingOverride' => $fmSearchHeading])

    <main class="fm-shop" data-purpose="search-results">
        <div class="container mx-auto px-4 py-5 lg:py-8">
            <div class="row g-4 align-items-start">

                <div class="col-12 col-lg-3">
                    @include('frontend.fm.sections.search.filter-toggle', [
                        'target'     => 'fmSearchFilters',
                        'hasFilters' => $fmHasAnyFilter,
                    ])

                    <div class="collapse" id="fmSearchFilters">
                        @include('frontend.fm.sections.shop-sidebar')
                    </div>
                </div>

                <div class="col-12 col-lg-9">
                    @include('frontend.fm.sections.shop-toolbar')

                    {{-- The query itself is the one thing a result page has that
                         the shop page does not, so it is shown as a removable
                         chip above the grid, echoing the reference's
                         active-filter strip. --}}
                    @if ($fmTerm !== '' || $fmHasAnyFilter)
                        @include('frontend.fm.sections.search.chips', [
                            'term'       => $fmTerm,
                            'scope'      => $fmSearchScope,
                            'scopeName'  => $fmActiveCategory->name ?? null,
                            'hasFilters' => $fmHasAnyFilter,
                        ])
                    @endif

                    {{-- With no query the controller applies no filter at all, so $products
                         holds the whole catalogue: a search page showing every
                         product would look like a shop page under the wrong
                         heading. The classic page hid this behind its own empty
                         term branch; here the grid is suppressed instead and the
                         pager goes with it, so /search with no word offers the
                         field rather than 24 arbitrary results. --}}
                    @if ($fmTerm !== '' || $fmHasAnyFilter)
                        <div class="fm-shop-grid mt-3 mt-md-4" data-fm-shop-grid data-view="grid">
                            @include('frontend.fm.sections.partials._product-grid', [
                                'products'  => $products,
                                'columns'   => 3,
                                'emptyText' => $fmTerm !== ''
                                    ? 'No products match "' . $fmTerm . '". Try a different word or clear the filters.'
                                    : 'No products match these filters.',
                            ])
                        </div>

                        @if (method_exists($products, 'hasPages') && $products->hasPages())
                            @include('frontend.fm.partials.pagination', ['paginator' => $products])
                        @endif
                    @endif
                </div>

            </div>
        </div>
    </main>

    @include('frontend.fm.sections.guarantees')
@endsection