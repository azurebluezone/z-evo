{{--
    Card actions for the shared storefront product card.

    This mirrors the card /products already renders: Buy Now uses the same
    .add-to-cart-btn.product-card-order-now link the layout injects (above Add
    to Cart), and Add to Cart uses the card's own .add-to-cart-btn. No new
    visual class is introduced.

    A variant product needs a size/colour first, so both actions open the
    detail page. A simple product adds directly (AJAX for Add to Cart,
    cart.store.buy for Buy Now). A sold-out product keeps one disabled action
    and never starts a cart POST.

    Expected: $product, $cardInStock, $cardHasVariants, $cardUrl
--}}
@php
    $actionProduct = $product ?? null;
    $actionInStock = (bool) ($cardInStock ?? false);
    $actionHasVariants = (bool) ($cardHasVariants ?? false);
    $actionUrl = $cardUrl ?? ($actionProduct ? route('product', $actionProduct->slug) : '#');
@endphp

@if(!$actionInStock)
    <button type="button" class="add-to-cart-btn" disabled>
        <i class="las la-shopping-bag"></i> Out of Stock
    </button>
@elseif($actionHasVariants)
    <a href="{{ $actionUrl }}" class="add-to-cart-btn product-card-order-now" title="Choose a size on the product page">
        <i class="las la-bolt"></i> Buy Now
    </a>
    <a href="{{ $actionUrl }}" class="add-to-cart-btn" title="Choose a size on the product page">
        <i class="las la-shopping-bag"></i> Add to Cart
    </a>
@else
    <form action="{{ route('cart.store.buy') }}" method="POST">
        @csrf
        <input type="hidden" name="id" value="{{ $actionProduct->id }}">
        <input type="hidden" name="qty" value="1">
        <button type="submit" class="add-to-cart-btn product-card-order-now">
            <i class="las la-bolt"></i> Buy Now
        </button>
    </form>
    <input type="hidden" name="quantity" value="1">
    <button type="button" class="addToCart add-to-cart-btn" data-id="{{ $actionProduct->id }}" data-product_type="{{ $actionProduct->product_type ?? 1 }}">
        <i class="las la-shopping-bag"></i> Add to Cart
    </button>
@endif
