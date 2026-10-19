{{--
    FM:PARTIAL products/catalog
    =========================================================================
    The catalogue itself: filter panel on the left, toolbar + result grid +
    pager on the right. This is the work/code.html layout, which is why the
    columns are not Bootstrap's - .fm-page-shop scopes every rule in
    fm-products-v2.css to this block and to nothing else.

    THE FILTER FORM IS NOT DUPLICATED
    -------------------------------------------------------------------------
    work/code.html repeats the whole filter list inside a mobile drawer.
    Copying it here would put a SECOND #fmFilterForm on the page, and the sort
    select joins the form through form="fmFilterForm" - with two ids the browser
    resolves that attribute to the first match, so the visitor's sort would be
    submitted with a stale, empty filter set.

    So there is ONE form node. It starts in the desktop sidebar and
    public/frontend/js/fm-products.js MOVES it into the drawer when a phone
    opens the drawer, and moves it straight back when the drawer closes. Ids,
    names, checked states and the value typed into the price field all survive
    the move, because the same element is moved - nothing is copied or
    re-rendered.

    With JavaScript off the drawer button is hidden by CSS and the sidebar stays
    in the page, so the filters are still reachable on a phone.

    Props
        products  paginator  the result set the controller already built

    Usage
        @include('frontend.fm.sections.products.catalog', ['products' => $products])
--}}

{{-- FM:SECTION catalog =============================================== --}}
<main class="fm-shop-layout" data-purpose="catalog">
    <div class="fm-container">

        {{-- Products column: toolbar, result grid, pagination --}}
        <div class="fm-shop-main">

            <x-fm::shop-toolbar :products="$products" variant="v2" />

            <x-fm::product-grid
                :products="$products"
                card="v2"
                variant="catalog"
                :columns="4"
                emptyText="No products match these filters. Try removing a filter." />

            @if($products->hasPages())
                @include('frontend.fm.partials.pagination', ['paginator' => $products])
            @endif

        </div>

        {{-- Filter panel, on the RIGHT.

             It is LAST in the DOM on purpose. `.fm-container` is a flex row, so
             the visual order follows the DOM order - and that keeps what a
             screen reader reads in the same order as what a sighted visitor
             sees. The CSS `order` property would have moved the picture while
             leaving the reading order reversed, which is the classic
             focus-vs-eye mismatch: tab from the product cards and you would
             land back on the far-left filter panel.

             The design reference (work/Products.html) labels this aside
             "DESKTOP SIDEBAR FILTERS (Left Column)", so moving it back to the
             left is this one element moving above .fm-shop-main - the CSS does
             not care which side it is on. --}}
        <x-fm::shop-sidebar variant="v2" aside />
    </div>
</main>
{{-- /FM:SECTION catalog --}}