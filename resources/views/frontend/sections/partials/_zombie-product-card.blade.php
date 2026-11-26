@php
    /*
     * One home-page product card.
     *
     * Stock rules:
     *   - a product with variants must be opened on its detail page, so the
     *     shopper chooses the size/colour instead of silently buying the
     *     default variant;
     *   - an out-of-stock product shows a disabled "Out of Stock" action and a
     *     badge over the image, so the click never starts a doomed cart POST;
     *   - a simple product keeps the direct Buy Now / Add to Cart forms.
     *
     * expected: $product (App\Models\Product), with image + prosizes/procolors
     * eager loaded and the batched has_variants/available_to_promise
     * attributes attached by FrontendController::index().
     */
    $cardProduct = $product ?? null;
    $cardId = (int) ($cardProduct->id ?? 0);
    $cardName = (string) ($cardProduct->name ?? '');
    $cardUrl = route('product', $cardProduct->slug ?? $cardId);
    $cardImage = \App\Support\MediaPath::product($cardProduct);
    $cardInStock = method_exists($cardProduct, 'isInStock') ? $cardProduct->isInStock() : false;
    $cardHasVariants = method_exists($cardProduct, 'hasVariants') ? $cardProduct->hasVariants() : false;
    $cardHasLegacyOptions = ($cardProduct && method_exists($cardProduct, 'prosizes') && !$cardProduct->prosizes->isEmpty())
        || ($cardProduct && method_exists($cardProduct, 'procolors') && !$cardProduct->procolors->isEmpty());
@endphp

<div class="zombie-product-card" data-product-id="{{ $cardId }}" data-in-stock="{{ $cardInStock ? 1 : 0 }}">
    <a href="{{ $cardUrl }}" class="zombie-product-link">
        <div class="zombie-product-img-container">
            <img src="{{ $cardImage }}" alt="{{ $cardName }}" class="zombie-product-img" loading="lazy"
                 onerror="this.onerror=null;this.src='{{ asset('uploads/default/no-image.png') }}';">

            @if(!$cardInStock)
                <span class="zombie-out-of-stock">Out of Stock</span>
            @endif
        </div>

        <div class="zombie-box-text">
            <p class="zombie-product-title">{{ Str::limit($cardName, 80) }}</p>
            <div class="zombie-product-price">
                @if($cardProduct->old_price)
                    <span class="zombie-original-price">৳ {{ $cardProduct->old_price }}</span>
                @endif
                <span class="zombie-discounted-price">৳ {{ $cardProduct->new_price }}</span>
            </div>
        </div>
    </a>

    <div class="zombie-btn-group">
        @if(!$cardInStock)
            <button type="button" class="zombie-btn-cart" disabled style="flex: 1; width: 100%;">Out of Stock</button>
        @elseif($cardHasVariants || $cardHasLegacyOptions)
            <a href="{{ $cardUrl }}" class="zombie-btn-buy">BUY NOW</a>
            <a href="{{ $cardUrl }}" class="zombie-btn-cart">CHOOSE OPTIONS</a>
        @else
            <form action="{{ route('cart.store.buy') }}" method="POST" style="flex: 1;">
                @csrf
                <input type="hidden" name="id" value="{{ $cardId }}" />
                <input type="hidden" name="qty" value="1" />
                <button type="submit" class="zombie-btn-buy" style="width: 100%;">BUY NOW</button>
            </form>

            <form action="{{ route('cart.store') }}" method="POST" style="flex: 1;">
                @csrf
                <input type="hidden" name="id" value="{{ $cardId }}" />
                <input type="hidden" name="qty" value="1" />
                <button type="submit" class="zombie-btn-cart" style="width: 100%;" onclick="add_to_cart(this,event)">ADD TO CART</button>
            </form>
        @endif
    </div>
</div>
