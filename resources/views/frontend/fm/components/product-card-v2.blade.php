{{--
    FM:COMPONENT product-card-v2
    =========================================================================
    The card from the two approved reference pages: work/home.html (the home
    rails) and work/code.html (the catalog).

    WHY A SECOND CARD
    -------------------------------------------------------------------------
    The existing components/product-card.blade.php stays exactly as it is:
    it is used by search.blade.php and by the related-products rail on
    product-details.blade.php, and neither page is part of this redesign.
    Both new pages are handed this component instead, so the shared pages keep
    the old card rather than inheriting a redesign nobody asked for.

    Usage
        <x-fm::product-card-v2 :product="$product" variant="home" />
        <x-fm::product-card-v2 :product="$product" variant="catalog" />

    Props
        product    App\Models\Product          required
        variant    'home'|'catalog'  (default 'home')
        badgeText  string|null                 pre-set badge, wins over "Save N%"
        wishlist   bool  (default true)
        stockPlate bool  (default true)         grey plate over the media

    WHAT IS DIFFERENT FROM THE v1 CARD
    -------------------------------------------------------------------------
    Two action buttons (the reference has both), no rating row and no unit
    line. Every hook the shared JavaScript needs is kept:

        form[data-add-to-cart]      intercepted by fm-app.js -> cart badge,
                                    toast and mini cart refresh in place
        [data-wishlist-id]          delegated wishlist toggle
        .is-active on that button   the painted "saved" state
        <i> inside the wish button  fm-app.js swaps bi-heart / bi-heart-fill

    "Order Now" deliberately does NOT carry data-add-to-cart: it posts to
    route('cart.store.buy') (ShoppingController::cart_store_by_now), which
    puts the item in the cart and redirects to checkout. Intercepting it
    would turn a checkout jump into a silent in-page add.

    VARIANT AND MARKUP
    -------------------------------------------------------------------------
    The two references differ only in where the action row sits (inside the
    padded body vs. a sibling of it) and in the class names. Both are the
    same flex column with margin-top:auto on the action row, so ONE structure
    serves both and the variant only switches the names - which is what keeps
    fm-home-v2.css and fm-products-v2.css from ever meeting.
    =========================================================================
--}}

@php
    // Anonymous components do not declare defaults, so normalise here.
    $variant    = $variant ?? 'home';
    $badgeText  = $badgeText ?? null;
    $wishlist   = $wishlist ?? true;
    $stockPlate = $stockPlate ?? true;

    $isCatalog = $variant === 'catalog';

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

    // Class names, per variant.
    $mediaClass = $isCatalog ? 'fm-product-card__media' : 'fm-product-image-container';
    $badgeClass = $isCatalog ? 'fm-product-card__badge' : 'fm-product-badge-float';
    $wishClass  = $isCatalog ? 'fm-product-card__wish' : 'fm-wishlist-btn';
    $bodyClass  = $isCatalog ? 'fm-product-card__body' : 'fm-product-content';
    $titleClass = $isCatalog ? 'fm-product-card__title' : 'fm-product-title';
    $priceClass = $isCatalog ? 'fm-product-card__price' : 'fm-product-price-row';
    $actClass   = $isCatalog ? 'fm-actions-row' : 'fm-product-actions-grid';
    $cartClass  = $isCatalog ? 'fm-btn-cart' : 'fm-btn-cart-secondary';
    $orderClass = $isCatalog ? 'fm-btn-order' : 'fm-btn-order-primary';
    $plateClass = $isCatalog ? 'fm-product-card__soldout' : 'fm-product-soldout';

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

    @if($isCatalog)
        <div class="fm-product-card__inner">
    @endif

        {{-- Media ---------------------------------------------------------- --}}
        <div class="{{ $mediaClass }}">
            <a href="{{ $url }}" aria-label="{{ $name }}">
                <img src="{{ $image }}" alt="{{ $name }}"
                     loading="lazy" decoding="async" width="600" height="600">
            </a>

            @if($fmBadgeText)
                <div class="{{ $badgeClass }}">
                    <span class="fm-badge fm-badge-{{ $fmBadgeKind }}">{{ $fmBadgeText }}</span>
                </div>
            @endif

            @if($wishlist)
                {{-- The heart keeps <i>: fm-app.js swaps bi-heart and
                     bi-heart-fill on exactly that element. --}}
                <button type="button"
                        class="{{ $wishClass }} {{ $fmWishlisted ? 'is-active' : '' }}"
                        data-wishlist-id="{{ $product->id }}"
                        title="{{ $fmWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}"
                        aria-label="{{ $fmWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}">
                    <i class="bi {{ $fmWishlisted ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                </button>
            @endif

            @if($stockPlate && ! $inStock)
                <div class="{{ $plateClass }}">Out of stock</div>
            @endif
        </div>

        {{-- Body ----------------------------------------------------------- --}}
        <div class="{{ $bodyClass }}">

            <h3 class="{{ $titleClass }}"><a href="{{ $url }}">{{ $name }}</a></h3>

            {{--
                The two references use different elements for the price (a
                <span>/<span> on the home rails, a <strong>/<del> in the
                catalog), so both are spelled out rather than assembled from a
                tag name - a dynamic tag would also break the CSS selectors.
            --}}
            <div class="{{ $priceClass }}">
                @if($isCatalog)
                    <strong>Tk {{ number_format($newPrice) }}</strong>
                    @if($hasDiscount)
                        <del>Tk {{ number_format($oldPrice) }}</del>
                    @endif
                @else
                    <span class="fm-price-current">Tk {{ number_format($newPrice) }}</span>
                    @if($hasDiscount)
                        <span class="fm-price-regular">Tk {{ number_format($oldPrice) }}</span>
                    @endif
                @endif
            </div>

            {{-- Two actions, exactly as both references show them. --}}
            <div class="{{ $actClass }} fm-product-card__actions">
                <form method="POST" action="{{ route('cart.store') }}" data-add-to-cart>
                    @csrf
                    <input type="hidden" name="id" value="{{ $product->id }}">
                    <input type="hidden" name="qty" value="1">
                    <button type="submit" class="{{ $cartClass }}"
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
                    <button type="submit" class="{{ $orderClass }}"
                            title="Buy now" aria-label="Buy {{ $name }} now"
                            {{ $inStock ? '' : 'disabled' }}>
                        <i class="bi bi-lightning-charge-fill"></i>
                        <span>Order Now</span>
                    </button>
                </form>
            </div>
        </div>

    @if($isCatalog)
        </div>
    @endif

</article>