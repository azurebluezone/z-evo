{{--
    FM:COMPONENT product-grid
    =========================================================================
    Responsive product grid. One component for every listing on the site.

    Usage
        <x-fm::product-grid :products="$products" :columns="4" />
        <x-fm::product-grid :products="$products" card="v2" variant="catalog" />
        <x-fm::product-grid :products="$fmRailItems" card="v2" variant="home" />

    Props
        products   Collection|Paginator   the result set                (required)
        columns    int      default 4     Bootstrap column count (card v1)
        emptyText  string|null            message when the set is empty
        card       'v1'|'v2'  default 'v1'
        variant    'catalog'|'home'  default 'catalog'   the v2 card flavour

    card picks the card component:
        'v1'  components/product-card.blade.php      the original FreshMart card
        'v2'  components/product-card-v2.blade.php   the work/home.html +
              work/code.html card, two action buttons

    BOTH flavours keep the same hooks (form[data-add-to-cart],
    [data-wishlist-id], .is-active), so fm-app.js serves them identically and a
    page can switch flavour without touching any JavaScript.

    The v2 grid is a CSS grid (.fm-product-grid), not a Bootstrap row - the
    references lay it out with grid-cols-2 / md:3 / xl:4 and the column counts
    live in fm-products-v2.css. That is why `columns` is ignored for v2.
    =========================================================================
--}}

@php
    $fmProducts  = $products ?? collect();
    $fmColumns   = (int) ($columns ?? 4);
    $fmEmptyText = $emptyText ?? 'No products right now.';
    $fmCard      = ($card ?? 'v1') === 'v2' ? 'v2' : 'v1';
    $fmVariant   = ($variant ?? 'catalog') === 'home' ? 'home' : 'catalog';

    // Bootstrap has no 5 column class, so 5 is expressed as 6 with a custom rule.
    // 3 columns mirrors the approved catalog design breakpoint for breakpoint:
    // one card per row on phones, two from the small breakpoint, three on lg+.
    // 4 columns mirrors the hi design rails: one card per row on phones, two
    // from the small breakpoint, four from lg.
    $fmColClass = match ($fmColumns) {
        2     => 'col-6 col-md-4 col-lg-3',
        3     => 'col-12 col-sm-6 col-lg-4',
        4     => 'col-12 col-sm-6 col-lg-3',
        5     => 'col-6 col-md-4 col-lg-3 fm-col-5',
        default => 'col-12 col-sm-6 col-lg-4',
    };
@endphp

@if($fmProducts->isNotEmpty())
    @if($fmCard === 'v2')
        <div class="fm-product-grid" data-view="grid">
            @foreach($fmProducts as $fmProduct)
                <x-fm::product-card-v2 :product="$fmProduct" variant="{{ $fmVariant }}" />
            @endforeach
        </div>
    @else
        <div class="row g-3 g-md-4">
            @foreach($fmProducts as $fmProduct)
                <div class="col {{ $fmColClass }}">
                    {{-- The card lives inside the modern template set, so it is
                         addressed through the "fm" component namespace registered
                         in AppServiceProvider rather than the global components
                         folder. --}}
                    <x-fm::product-card :product="$fmProduct" />
                </div>
            @endforeach
        </div>
    @endif
@else
    <div class="fm-empty">
        <i class="bi bi-box-seam"></i>
        <p>{{ $fmEmptyText }}</p>
    </div>
@endif
