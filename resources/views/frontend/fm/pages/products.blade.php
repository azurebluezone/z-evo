{{--
    FM PAGE products
    =========================================================================
    Catalog listing behind /products, including every query-string variant
    the homepage "see more" buttons produce (type=featured, type=discount,
    type=best-selling, type=recommended).

    The route and the query are shared with the classic design, so this file
    only decides how the result is presented. Every control is a plain GET
    input (category=, brand[]=, min_price=, max_price=, rating=, in_stock=,
    sort_by=, per_page=): the address bar stays shareable, the filters keep
    working with JavaScript off, and no design flag ever occupies the query
    string - the session preview route handles that instead.

    To bring the old design back: flip "Front-end Template" in the admin
    panel (Website Setup -> Front-end Template).
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@php
    /*
     * The heading is this page's concern; the filter STATE is not.
     *
     * App\Support\ShopFilters parses the same query string for the sidebar and
     * toolbar components, so the page reads that one object instead of keeping
     * a second copy of the parse - which is exactly how the two copies drifted
     * (type labels, an empty-category edge case, the path-scoped rules).
     */
    $fmQuery = \App\Support\ShopFilters::current();

    $fmTypeLabel = $fmQuery->typeLabel();

    // The most specific active filter names the page.
    //
    // /category/{slug} and /products/brand/{slug} already decide the list in
    // the route, and FrontendController::category()/brand() read only their own
    // $slug - ?category= is ignored there. Letting it through let the sidebar
    // radio rename the page: /category/premature?category=newborn printed the
    // heading "Newborn" over a list of Premature products. ShopFilters applies
    // the path-over-query rule once, for the heading and the panel alike.
    $fmActiveCategory = $fmQuery->categorySlug !== ''
        ? ($sidecategories ?? collect())->firstWhere('slug', $fmQuery->categorySlug)
        : null;

    $fmActiveBrands = ($brands ?? collect())
        ->filter(static fn ($brand) => in_array($brand->slug, $fmQuery->brandSlugs, true))
        ->values();

    // Routes that scope the listing through the PATH rather than the query
    // string leave every candidate above null: /products/brand/{slug} never
    // puts a brand in the query, so the heading used to fall through to the
    // generic literal and the brand page named itself "All products &
    // groceries". Every listing action in FrontendController already passes
    // $pageTitle for exactly this purpose, so it takes over before the
    // literal. The /products closure passes no $pageTitle at all, hence the
    // isset() guard, and that page keeps its generic heading unchanged.
    $fmControllerTitle = isset($pageTitle) && trim((string) $pageTitle) !== ''
        ? trim((string) $pageTitle)
        : null;

    $fmHeading = ($fmActiveCategory->name ?? null)
        ?? ($fmActiveBrands->isNotEmpty() ? $fmActiveBrands->pluck('name')->join(', ') : null)
        ?? $fmTypeLabel
        ?? $fmControllerTitle
        ?? 'All products & groceries';

    // Kept for the page's own use (the header/footer partials read it); the
    // toolbar component derives the same value from ShopFilters by itself.
    $fmHasActiveFilter = $fmQuery->hasActive();
@endphp

@section('title', $fmHeading)

{{-- MetaText::plain() because the description field can hold markup: @section
     escapes once and seo-meta escapes again. --}}
@section('meta_description', 'Find your favorite products with filters and sorting - ' . \App\Support\MetaText::plain($generalsetting->description ?? ''))

@push('css')
    {{--
        Page specific sheet. It is pushed on the 'css' stack, so it loads
        after every shared file listed in layouts.blade.php and can adjust a
        shared primitive for this page only (grid vs list view, pager).
    --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-products.css') }}">
    {{--
        The work/code.html redesign. Linked after fm-products.css, which owns
        the v1 .fm-card.fm-product rules; this page no longer renders that
        card, so the two sheets do not overlap. Every rule in it is scoped to
        .fm-page-shop, which only this page opens - search.blade.php
        includes the same sidebar and toolbar partials but not the wrapper.
    --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-products-v2.css') }}">
@endpush

@section('content')

{{--
    .fm-page-shop scopes every rule in fm-products-v2.css to this page and to
    nothing else. The header and the footer are the layout's own partials and
    are untouched.
--}}
<div class="fm-page-shop">

    @include('frontend.fm.sections.products.catalog', ['products' => $products])

    <x-fm::filters-drawer />

</div>

@endsection

@push('script')
{{--
    The page behaviour lives in public/frontend/js/fm-products.js - it used to
    be an inline block here, which meant the browser re-downloaded it on
    every page view and the page file could not be read without scrolling
    past hundreds of lines of JavaScript. Nothing about the behaviour changed.
--}}
<script src="{{ asset('frontend/js/fm-products.js') }}"></script>
@endpush
