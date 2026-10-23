{{--
    FM:COMPONENT product-card-v2
    =========================================================================
    THE product card. One card, every page - home rails, flash sale, /products
    and /hot-deals all render this exact markup.

    THE RULING
    -------------------------------------------------------------------------
    There is only ONE card design in this theme. The home page card
    (work/home.html) is the approved one, and it is what every surface uses.
    Earlier this component carried a second "catalog" flavour with its own
    class names (fm-product-card__media / __badge / __wish / __body / __title /
    __price, fm-btn-cart / fm-btn-order) fed by work/code.html. That second
    design is gone: two cards meant two CSS systems, and the same shop page
    could drift from the home page without anyone noticing.

    The variant prop is gone with it. Grid DENSITY is not the card's business
    any more - it belongs to the grid, so product-grid passes
    fm-product-grid--home / --catalog and this component never hears about it.

    WHAT IS STYLED WHERE
    -------------------------------------------------------------------------
    public/frontend/css/fm-card-system.css owns this card and nothing else, and
    it is loaded on every storefront page. It carries its own --fm-card-*
    tokens instead of the page's --fmv2-* palette, because fm-home-v2.css and
    fm-products-v2.css declare DIFFERENT neutrals (#e5e7eb vs #e2e8f0,
    #111827 vs #1e293b) - a card reading those would look like two designs
    again on the next CSS pass.

    Props
        product    App\Models\Product          required
        badgeText  string|null                 pre-set badge, wins over "Save N%"
        wishlist   bool  (default true)
        stockPlate bool  (default true)         grey plate over the media

    Hooks the shared JavaScript relies on - do not rename:
        form[data-add-to-cart]      intercepted by fm-app.js -> cart badge,
                                    toast and mini cart refresh in place
        [data-wishlist-id]          delegated wishlist toggle
        .is-active on that button   the painted "saved" state
        <i> inside the wish button  fm-app.js swaps bi-heart / bi-heart-fill

    "Order Now" deliberately does NOT carry data-add-to-cart: it posts to
    route('cart.store.buy') (ShoppingController::cart_store_by_now), which puts
    the item in the cart and redirects to checkout. Intercepting it would turn
    a checkout jump into a silent in-page add.
    =========================================================================
--}}

@php
    // Anonymous components do not declare defaults, so normalise here.
    $badgeText  = $badgeText ?? null;
    $wishlist   = $wishlist ?? true;
    $stockPlate = $stockPlate ?? true;

    $image  = \App\Support\MediaPath::product($product);
    $url    = route('product', $product->slug ?: $product->id);
    $name   = (string) $product->name;

    $oldPrice = (float) ($product->old_price ?? 0);
    $newPrice = (float) ($product->new_price ?? 0);
    $hasDiscount = $oldPrice > 0 && $oldPrice > $newPrice;
    $discountPercent = $hasDiscount ? (int) round((($oldPrice - $newPrice) / $oldPrice) * 100) : 0;

    $inStock = (int) ($product->stock ?? 0) > 0;

    // The wishlist is a session array of product ids, so the painted state is
    // correct on first paint and never waits for the refreshWishlist() call.
    $fmWishlistIds = session()->get('wishlist', []);
    $fmWishlisted  = is_array($fmWishlistIds)
        && in_array((int) $product->id, array_map('intval', $fmWishlistIds), true);

    // Badge text: an explicit badge wins, then the discount, then nothing.
    // "100% Organic" is NOT invented - the Product model has no organic flag,
    // so the reference's green badge has no honest value to print.
    $fmBadgeText = $badgeText;
    $fmBadgeKind = 'organic';
    if ($fmBadgeText === null && $discountPercent > 0) {
        $fmBadgeText = 'Save ' . $discountPercent . '%';
        $fmBadgeKind = 'sale';
    }
@endphp

<article class="fm-product-card" data-product-id="{{ $product->id }}">

    {{-- Media ---------------------------------------------------------- --}}
    <div class="fm-product-image-container">
        <a href="{{ $url }}" aria-label="{{ $name }}">
            <img src="{{ $image }}" alt="{{ $name }}"
                 loading="lazy" decoding="async" width="600" height="600">
        </a>

        @if($fmBadgeText)
            <div class="fm-product-badge-float">
                <span class="fm-badge fm-badge-{{ $fmBadgeKind }}">{{ $fmBadgeText }}</span>
            </div>
        @endif

        @if($wishlist)
            {{-- The heart keeps <i>: fm-app.js swaps bi-heart and
                 bi-heart-fill on exactly that element. --}}
            <button type="button"
                    class="fm-wishlist-btn {{ $fmWishlisted ? 'is-active' : '' }}"
                    data-wishlist-id="{{ $product->id }}"
                    title="{{ $fmWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}"
                    aria-label="{{ $fmWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}">
                <i class="bi {{ $fmWishlisted ? 'bi-heart-fill' : 'bi-heart' }}"></i>
            </button>
        @endif

        @if($stockPlate && ! $inStock)
            <div class="fm-product-soldout">Out of stock</div>
        @endif
    </div>

    {{-- Body ----------------------------------------------------------- --}}
    <div class="fm-product-content">

        <h3 class="fm-product-title"><a href="{{ $url }}">{{ $name }}</a></h3>

        <div class="fm-product-price-row">
            <span class="fm-price-current">Tk {{ number_format($newPrice) }}</span>
            @if($hasDiscount)
                <span class="fm-price-regular">Tk {{ number_format($oldPrice) }}</span>
            @endif
        </div>

        {{-- Two actions, exactly as the approved card shows them. --}}
        <div class="fm-product-actions-grid">
            <form method="POST" action="{{ route('cart.store') }}" data-add-to-cart>
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}">
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="fm-btn-cart-secondary"
                        title="Add to cart" aria-label="Add {{ $name }} to cart"
                        {{ $inStock ? '' : 'disabled' }}>
                    <i class="bi bi-cart-plus"></i>
                    <span>Add to Cart</span>
                </button>
            </form>

            <form method="POST" action="{{ route('cart.store.buy') }}">
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}">
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="fm-btn-order-primary"
                        title="Buy now" aria-label="Buy {{ $name }} now"
                        {{ $inStock ? '' : 'disabled' }}>
                    <i class="bi bi-lightning-charge-fill"></i>
                    <span>Order Now</span>
                </button>
            </form>
        </div>
    </div>

</article>
