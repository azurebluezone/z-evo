{{--
    FM PAGE hotdeals
    =========================================================================
    /hot-deals - the discounted-product listing, on the FM (modern) layout.

    It replaces frontend/pages/hotdeals.blade.php, which extended
    frontend.layouts.master: the classic layout, with its own
    `.sh-footer-v2` footer and a very different visual language from every
    other redesigned page. The result was a shop whose "Hot deals" landing page
    did not look like the shop.

    The page now reuses the product listing shell verbatim
    (fm/sections/products/catalog), which is the design it is asked to match,
    so /hot-deals and /products are the same page with a different result set:
    same sidebar, same toolbar, same grid, same pager.

    THE DATA IS UNCHANGED. FrontendController::hotdeals still builds the same
    query and hands over the same $products, $pageTitle and $metaDescription;
    only the view name changed. The classic view is still on disk - change the
    name in FrontendController back to 'frontend.pages.hotdeals' to restore it.
    =========================================================================
--}}

@extends('frontend.fm.layouts')

@php
    /*
        The controller already names this page ($pageTitle = 'Hot Deals'); it
        is used rather than a literal here so the heading, the <title> and the
        SEO meta can never disagree.
    */
    $fmHeading = trim((string) ($pageTitle ?? '')) !== ''
        ? trim((string) $pageTitle)
        : 'Hot deals';

    /*
        THE LISTING COMPONENTS RESOLVE THEIR OWN STATE.

        This page renders the same catalog section as /products and /search. It
        used to declare the whole set of filter locals - $fmRouteName,
        $fmCategorySlug, $fmBrandSlugs, $fmMinPrice, $fmMaxPrice, $fmRating,
        $fmInStock, $fmType, $fmHasActiveFilter - purely because the sidebar and
        toolbar partials read them out of the including view's scope, and
        omitting $fmRouteName 500'd the page.

        <x-fm::shop-sidebar> and <x-fm::shop-toolbar> now parse the request
        themselves through App\Support\ShopFilters, so the contract is gone: a
        page supplies what it knows and nothing else. Hot deals is scoped by
        neither a path nor a filter, so the defaults are exactly right.
    */
@endphp

@section('title', $fmHeading)

@section('meta_description', \App\Support\MetaText::plain($metaDescription ?? ''))

@push('css')
    {{--
        The SAME two sheets the /products page uses, in the same order:

          fm-products.css   the listing primitives
          fm-products-v2.css  the work/code.html redesign, scoped to
                              .fm-page-shop - which this page opens, exactly as
                              /products and /search do.

        Nothing page-specific was needed: this page is the product page with a
        different query, so adding a third sheet would have been a copy.
    --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-products.css') }}">
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
    The page behaviour is the /products page behaviour: the same grid controls,
    the same filter drawer, the same filter -> drawer move. It is the very same
    script the listing loads, not a copy - see public/frontend/js/fm-products.js.
--}}
<script src="{{ asset('frontend/js/fm-products.js') }}"></script>
@endpush