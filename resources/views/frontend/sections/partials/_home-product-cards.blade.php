@foreach($products as $product)
    @php
        $productImageRaw = (string) ($product->image?->image ?? '');
        $productImageIsUrl = preg_match('~^https?://~i', $productImageRaw) === 1;
        $productImagePath = $productImageIsUrl
            ? $productImageRaw
            : ltrim(preg_replace('~^public/~', '', $productImageRaw), '/');
        if (!$productImageIsUrl && !empty($productImagePath) && !str_contains($productImagePath, '/') && !str_contains($productImagePath, '\\')) {
            $productImagePath = 'uploads/product/' . $productImagePath;
        }
        $hasImage = !empty($productImagePath);
        $productImageSrc = $hasImage
            ? ($productImageIsUrl ? $productImagePath : asset($productImagePath))
            : asset('uploads/default/placeholder.png');
        $displayOldPrice = !empty($product->old_price) && (float) $product->old_price > (float) ($product->new_price ?? 0);
        $discountPercent = $displayOldPrice && (float) $product->old_price > 0
            ? (int) round((((float) $product->old_price - (float) $product->new_price) * 100) / (float) $product->old_price)
            : null;
        // Batched by FrontendController::frontendHome()/homeSectionProducts();
        // never a per-card query. Out-of-stock cards must not offer
        // Add to Cart, and a variant product must go through its detail
        // page so the shopper picks a size.
        $cardInStock = method_exists($product, 'isInStock') ? $product->isInStock() : false;
        $cardHasVariants = method_exists($product, 'hasVariants') ? $product->hasVariants() : false;
        $cardUrl = route('product', $product->slug);
    @endphp
    <div class="product-card" data-product-id="{{ $product->id }}" data-in-stock="{{ $cardInStock ? 1 : 0 }}">
        <div class="product-thumb">
            <ul class="product-card-buttons">
                <li class="product-wishlist-btn">
                    <button type="button" class="addToWishlist" data-id="{{ $product->id }}"><i class="lar la-heart"></i></button>
                </li>
            </ul>

            @if(!empty($discountPercent))
                <span class="badge badge--danger" style="position:absolute;top:10px;left:10px;z-index:2;">-{{ $discountPercent }}%</span>
            @endif

            @if(!$cardInStock)
                <span class="product-stock-badge">Out of Stock</span>
            @endif

            <a href="{{ route('product', $product->slug) }}">
                <img
                    src="{{ asset('uploads/default/placeholder.png') }}"
                    class="lazyload"
                    data-src="{{ $productImageSrc }}"
                    alt="{{ $product->name }}"
                    width="400"
                    height="400"
                    style="width:100%;height:auto;aspect-ratio:1/1;object-fit:contain;"
                    onerror="this.onerror=null;this.src='{{ asset('uploads/default/no-image.png') }}';">
            </a>
        </div>

        <div class="product-content">
            <div class="product-before-content">
                <h6 class="title">
                    <a data-src="{{ $productImageSrc }}" href="{{ route('product', $product->slug) }}">{{ $product->name }}</a>
                </h6>

                <div class="single_content__info">
                    <div class="price">
                        ৳ {{ $product->new_price }}
                        @if($displayOldPrice)
                            <del>৳ {{ $product->old_price }}</del>
                        @endif
                    </div>

                    <div class="ratings-area">
                        <span class="ratings">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="lar la-star"></i>
                            @endfor
                        </span>
                        <span class="rating-count">({{ $product->reviews_count ?? 0 }})</span>
                    </div>
                </div>

                <div class="single_content">
                    <p>{{ Str::limit(strip_tags($product->description ?? $product->short_description ?? ''), 150) }}</p>
                </div>
            </div>

            @include('frontend.sections.partials._product-card-actions')
        </div>
    </div>
@endforeach
