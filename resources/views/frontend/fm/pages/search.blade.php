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

@section('content')
    <x-fm::shop-header :heading="$fmSearchHeading" />

    <main class="fm-shop" data-purpose="search-results">
        <div class="container mx-auto px-4 py-5 lg:py-8">
            <div class="row g-4 align-items-start">

                <div class="col-12 col-lg-3">
                    @include('frontend.fm.sections.search.filter-toggle', [
                        'target'     => 'fmSearchFilters',
                        'hasFilters' => $fmHasAnyFilter,
                    ])

                    <div class="collapse" id="fmSearchFilters">
                        {{-- v1 flavour: the search page keeps the FreshMart panel.
                             The component resolves its own filter state from the
                             request, so this page no longer declares it. --}}
                        <x-fm::shop-sidebar />
                    </div>
                </div>

                <div class="col-12 col-lg-9">
                    <x-fm::shop-toolbar :products="$products" />

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
                            <x-fm::product-grid
                                :products="$products"
                                :columns="3"
                                :emptyText="$fmEmptyText" />
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