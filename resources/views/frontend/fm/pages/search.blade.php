{{--
    FM PAGE search
    =========================================================================
    Search results, reached from the header search bar.

    The reference export ships search.html as its own document, but that file is
    the same shop listing the /products page already implements: a filter
    sidebar, a grid/list switch, a result count and a pager. Only the heading
    and the query chip differ. So this page renders the SAME shell as the shop
    page - same 12-column Bootstrap row, same filter panel, same toolbar, same
    one product card - rather than growing a second copy of the grid that would
    then drift.

    What it adds on top of that shell is only what a result page has and a
    category page does not:
      - the heading names the query instead of the shop;
      - the query is presented as a removable chip, matching the reference's
        active-filter strip.

    THE SHELL, EXACTLY AS ON /products
    -------------------------------------------------------------------------
    .fm-page-shop opens the scope fm-products-v2.css is written under (panel,
    toolbar, pager, drawer), then the row is Bootstrap's own 12 columns:

        col-lg-3 filter panel   (first child, so it paints on the left)
        col-lg-9 results        (toolbar, chips, grid, pager)

    Below lg the panel column is hidden and fm-products.js moves the SAME
    #fmFilterForm node into the drawer that the toolbar's "Filters" button
    opens - the identical mechanism the shop page uses, so a phone gets one
    filter behaviour to learn instead of two.

    The 3/9 split is what keeps the card the same size here as everywhere else:
    the grid inside the 9 columns is fm-product-grid--catalog, which paints the
    ~295px card the home page shows in its 4-across rails.

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
     * Only what this page ADDS to the shared listing is declared here.
     *
     * The filter state itself (category, brand[], price, rating, in_stock,
     * type, sort_by, per_page) used to be re-parsed on this page - ~16 locals
     * copied from pages/products.blade.php - so that the sidebar and toolbar
     * partials could read them out of this view's scope. Both are components
     * now and resolve their own state through App\Support\ShopFilters, so the
     * page keeps only what they cannot know: the term and the heading.
     */
    $fmTerm  = trim((string) ($term ?? ''));
    $fmQuery = \App\Support\ShopFilters::current();

    // A search may arrive carrying a category scope. It is needed twice - the
    // heading/chip scope and the chip's label - so it is resolved once.
    $fmSearchScope = $fmQuery->categorySlug;
    $fmActiveCategory = $fmSearchScope !== ''
        ? ($sidecategories ?? collect())->firstWhere('slug', $fmSearchScope)
        : null;

    // <x-fm::shop-header> derives its own heading from the query and only
    // needs to be told the one thing the URL cannot say: the term searched for.
    $fmSearchHeading = $fmTerm !== '' ? 'Results for "' . $fmTerm . '"' : 'Search';

    // Drives the chip strip, the grid guard and the toolbar's reset/count
    // badge. It is the toolbar component's own definition (ShopFilters::
    // hasActive), so the chip strip and the toolbar can never disagree.
    $fmHasActiveFilter = $fmHasAnyFilter = $fmQuery->hasActive();

    // The card quotes the term back to the visitor. It is escaped where it is
    // printed, so nothing is hand-escaped here.
    $fmEmptyText = $fmTerm !== ''
        ? 'No products match "' . $fmTerm . '". Try a different word or clear the filters.'
        : 'No products match these filters.';
@endphp

@section('title', $pageTitle ?? 'Search')
@section('meta_description', \App\Support\MetaText::plain($metaDescription ?? '') ?: 'Search products')

@push('css')
    {{--
        The shop shell. Every rule in it is scoped to .fm-page-shop, which only
        this page and pages/products.blade.php open; the panel, the toolbar, the
        pager and the drawer all come from here.
    --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-products-v2.css') }}">
@endpush

@section('content')
    <x-fm::shop-header :heading="$fmSearchHeading" />

    <div class="fm-page-shop">

        <main class="fm-shop-layout" data-purpose="search-results">
            <div class="fm-container">
                <div class="row g-4 align-items-start">

                    {{-- 3 of 12, first child = painted on the left. Below lg the
                         same form node is moved into the drawer. --}}
                    <x-fm::shop-sidebar variant="v2" aside />

                    <div class="col-12 col-lg-9 fm-shop-main">
                        <x-fm::shop-toolbar :products="$products" variant="v2" />

                        {{-- The query itself is the one thing a result page has
                             that the shop page does not, so it is shown as a
                             removable chip above the grid, echoing the
                             reference's active-filter strip. --}}
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
                            <x-fm::product-grid
                                card="v2"
                                variant="catalog"
                                :products="$products"
                                :columns="4"
                                :emptyText="$fmEmptyText" />

                            @if (method_exists($products, 'hasPages') && $products->hasPages())
                                @include('frontend.fm.partials.pagination', ['paginator' => $products])
                            @endif
                        @endif
                    </div>

                </div>
            </div>
        </main>

        {{-- Phones and tablets: holds the panel that fm-products.js moves the
             filter form into. Same component the shop page renders. --}}
        <x-fm::filters-drawer />

    </div>

    @include('frontend.fm.sections.guarantees')
@endsection

@push('script')
{{-- Drives the drawer move, the view switch and the sort selects. --}}
<script src="{{ asset('frontend/js/fm-products.js') }}"></script>
@endpush
