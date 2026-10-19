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
    // One computation feeds the document title, the page heading and the
    // active-filter chips below, so the three can never drift apart.
    $fmTypeLabels = [
        'featured'     => 'Featured products',
        'best-selling' => 'Best selling products',
        'discount'     => 'Discounted products',
        'recommended'  => 'Recommended products',
    ];

    $fmType = (string) request('type', '');
    $fmTypeLabel = $fmTypeLabels[$fmType] ?? null;

    $fmCategorySlug = trim((string) request('category', ''));

    // ------------------------------------------------------------------
    // The path wins over the query on path-scoped routes.
    //
    // /category/{slug} and /products/brand/{slug} already decide the list in
    // the route, and FrontendController::category()/brand() read only their
    // own $slug - ?category= is ignored there. Letting it through let the
    // sidebar radio rename the page: /category/premature?category=newborn
    // printed the heading "Newborn" over a list of Premature products.
    // The route is the source of truth on those routes, so it is used instead.
    // ------------------------------------------------------------------
    $fmPageRoute = app('router')->current();
    $fmRouteName = (string) ($fmPageRoute?->getName() ?? '');

    if ($fmRouteName === 'category') {
        $fmCategorySlug = (string) ($fmPageRoute->parameter('category') ?? '');
    } elseif ($fmRouteName === 'products.category') {
        $fmCategorySlug = (string) ($fmPageRoute->parameter('slug') ?? '');
    } elseif ($fmRouteName === 'products.brand') {
        // Brand pages carry no category; the heading falls through to the
        // controller's $pageTitle, which names the brand.
        $fmCategorySlug = '';
    }

    $fmActiveCategory = $fmCategorySlug !== ''
        ? ($sidecategories ?? collect())->firstWhere('slug', $fmCategorySlug)
        : null;

    $fmBrandSlugs = array_values(array_filter((array) request('brand')));
    $fmActiveBrands = ($brands ?? collect())
        ->filter(static fn ($brand) => in_array($brand->slug, $fmBrandSlugs, true))
        ->values();

    $fmMinPrice = (float) request('min_price', 0);
    $fmMaxPrice = (float) request('max_price', 0);
    $fmRating = (int) request('rating', 0);
    $fmInStock = request('in_stock') === '1';

    // The most specific active filter names the page.
    //
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

    $fmHasActiveFilter = $fmActiveCategory !== null
        || $fmActiveBrands->isNotEmpty()
        || $fmTypeLabel !== null
        || $fmMinPrice > 0
        || $fmMaxPrice > 0
        || $fmRating > 0
        || $fmInStock;
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

    @include('frontend.fm.sections.products.filters-drawer')

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
