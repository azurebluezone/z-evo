{{-- Featured Products Section --}}
@if(isset($featured_products) && count($featured_products) > 0)
    <div class="my-60">
        <div class="container">
            <div class="section-header left-style d-flex justify-content-between align-items-center">
                <h5 class="title">{{ $sections['featured']['title'] ?? 'Featured Products' }}</h5>
                <div class="section-nav" style="display:flex;gap:6px;align-items:center;">
                    <a href="{{ route('products.page') }}?type=featured" 
                       class="btn btn-sm btn-outline-secondary"
                       style="padding:6px 16px;border:1px solid #ef0000;background:#ef0000;color:#fff;border-radius:4px;font-size:14px;font-weight:500;text-decoration:none;transition:all 0.3s ease;">
                        See More
                    </a>
                </div>
            </div>
            <div class="offer-wrapper">
                <div class="product-wrapper" data-home-section-wrapper="featured">
                    @foreach($featured_products as $product)
                        @php
                            $productImageRaw = (string) ($product->image?->image ?? '');
                            $productImageIsUrl = preg_match('~^https?://~i', $productImageRaw) === 1;
                            $productImagePath = $productImageIsUrl
                                ? $productImageRaw
                                : ltrim(preg_replace('~^public/~', '', $productImageRaw), '/');
                            $hasImage = !empty($productImagePath);
                            $productImageSrc = $hasImage
                                ? ($productImageIsUrl ? $productImagePath : asset($productImagePath))
                                : asset('uploads/default/placeholder.png');
                            $displayOldPrice = !empty($product->old_price) && (float) $product->old_price > (float) ($product->new_price ?? 0);
                            $discountPercent = $displayOldPrice && (float) $product->old_price > 0
                                ? (int) round((((float) $product->old_price - (float) $product->new_price) * 100) / (float) $product->old_price)
                                : null;
                        @endphp
                        <div class="product-card">
                            <div class="product-thumb">
                                <ul class="product-card-buttons">
                                    <li class="product-wishlist-btn">
                                        <button type="button" class="addToWishlist" data-id="{{ $product->id }}"><i class="lar la-heart"></i></button>
                                    </li>
                                </ul>

                                @if(!empty($discountPercent))
                                    <span class="badge bg-danger" style="position:absolute;top:10px;left:10px;z-index:2;">-{{ $discountPercent }}%</span>
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
                                        <p>{{ Str::limit(strip_tags($product->description), 150) }}</p>
                                    </div>
                                </div>

                                <input type="hidden" name="quantity" value="1">
                                <button type="button" class="addToCart add-to-cart-btn" data-id="{{ $product->id }}" data-product_type="{{ $product->product_type ?? 1 }}">
                                    <i class="las la-shopping-bag"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@else
    {{-- Show Dummy Data --}}
    <div class="my-60">
        <div class="container">
            <div class="section-header left-style">
                <h5 class="title">Featured Products (Dummy)</h5>
            </div>
            <div class="offer-wrapper">
                <div class="product-wrapper">
                    @for($i = 1; $i <= 4; $i++)
                        <div class="product-card">
                            <div class="product-thumb">
                                <ul class="product-card-buttons">
                                    <li class="product-wishlist-btn">
                                        <button type="button" class="addToWishlist"><i class="lar la-heart"></i></button>
                                    </li>
                                </ul>
                                <a href="#">
                                    <img
                                        src="{{ asset('uploads/default/placeholder.png') }}"
                                        class="lazyload"
                                        data-src="{{ asset('uploads/default/placeholder.png') }}"
                                        alt="product"
                                        width="400"
                                        height="400"
                                        style="width:100%;height:auto;aspect-ratio:1/1;object-fit:contain;"
                                        onerror="this.onerror=null;this.src='{{ asset('uploads/default/no-image.png') }}';"
                                    >
                                </a>
                            </div>
                            <div class="product-content">
                                <div class="product-before-content">
                                    <h6 class="title"><a href="#">Dummy Featured Product {{ $i }}</a></h6>
                                    <div class="single_content__info">
                                        <div class="price">৳ 99.99</div>
                                        <div class="ratings-area">
                                            <span class="ratings">@for($j=0;$j<5;$j++)<i class="lar la-star"></i>@endfor</span>
                                            <span class="rating-count">(0)</span>
                                        </div>
                                    </div>
                                    <div class="single_content"><p>Sample product description for layout verification.</p></div>
                                </div>
                                <button type="button" class="addToCart add-to-cart-btn"><i class="las la-shopping-bag"></i> Add to Cart</button>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
@endif
