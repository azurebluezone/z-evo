{{--
    FM:PARTIAL products/catalog
    =========================================================================
    The catalogue itself: 12-column Bootstrap frame (row -> col-lg-3 filter
    panel + col-lg-9 products), toolbar + result grid + pager on the right.

    WHY BOOTSTRAP AND NOT A HAND-ROLLED GRID
    -------------------------------------------------------------------------
    "12 column, sidebar 3, products 9" is Bootstrap's own language: col-lg-3 +
    col-lg-9 = 12, the gutter comes from the row, and the arithmetic is visible
    in the markup instead of buried in a stylesheet. The card grid inside the
    9 then paints the SAME ~295px card the home page shows in its 4-across
    rails - one card, one size, wherever it appears.

    It is the design reference's own split (work/Products.html:
    `grid lg:grid-cols-12 gap-8`, `aside lg:col-span-4 xl:col-span-3`,
    `section lg:col-span-8 xl:col-span-9`).

    THE PANEL IS FIRST IN THE DOM, WHICH IS ALSO WHERE IT IS PAINTED
    -------------------------------------------------------------------------
    Bootstrap's flex row paints in DOM order, so the panel being the first
    child of the row is what puts it on the LEFT - the same side the design
    reference labels it ("DESKTOP SIDEBAR FILTERS (Left Column)"), and the same
    side the visitor's eye and caret both travel: filters, then results.

    That is why there is no grid-column/order override anywhere. An `order`
    rule could have moved the panel's pixels while leaving the reading order
    alone, and tabbing from the product cards would then have landed back on a
    panel that looks like it sits to the left of the text you just left.

    Moving it back to the right is this one element moving below .fm-shop-main
    - no CSS changes anywhere.

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
    re-rendered. Position on screen is irrelevant to that move: the drawer is
    glued to the viewport, not to the column it came from.

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

        <div class="row g-4 align-items-start">

            {{-- Filter panel: 3 of 12, first child = painted on the left. --}}
            <x-fm::shop-sidebar variant="v2" aside />

            {{-- Products column: 9 of 12. toolbar, result grid, pagination --}}
            <div class="col-12 col-lg-9 fm-shop-main">

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

        </div>
    </div>
</main>
{{-- /FM:SECTION catalog --}}
