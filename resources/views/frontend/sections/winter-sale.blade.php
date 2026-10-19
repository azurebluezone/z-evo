<section class="flash-sell-section my-60" data-starts-at="{{ $winter_sale_starts_at ?? '' }}" data-ends-at="{{ $winter_sale_ends_at ?? '' }}">
    <div class="container">
        <div class="section-header left-style">
            <h5 class="title">{{ $winter_sale_title ?? 'Winter Sale' }}</h5>
        </div>

        <div class="d-flex flex-wrap offer-wrapper">
            <div class="offer-banner">
                <div class="offer-countdown">
                    <div class="remaining-time">
                        <div class="remaining-time__content">
                            <p class="box"><span class="box__days box-style">00</span> <span class="box__text">Days</span></p>
                            <p class="box"><span class="remaining-time__hrs box-style">00</span> <span class="box__text">Hours</span></p>
                            <p class="box"><span class="remaining-time__min box-style">00</span> <span class="box__text">Min</span></p>
                            <p class="box"><span class="remaining-time__sec box-style">00</span> <span class="box__text">Sec</span></p>
                        </div>
                    </div>
                </div>

                <a href="{{ $winter_sale_banner_url ?? 'javascript:void(0)' }}" class="h-100">
                    <img class="w-100 rounded--5" src="{{ asset($winter_sale_banner_image ?? 'assets/images/offer_banner/673d8279201511732084345.png') }}" alt="offer-banner">
                </a>
            </div>

            <div class="product-slider-wrapper flex-grow-1">
                <div class="product-with-banner owl-carousel owl-theme">
                    @if(isset($winter_sale_products) && count($winter_sale_products) > 0)
                        @foreach($winter_sale_products as $group)
                            <div class="offer-product">
                                @foreach($group as $product)
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
                                                <li class="product-quick-view-btn">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="button" class="addToCart" data-id="{{ $product->id }}" data-product_type="{{ $product->product_type ?? 1 }}"><i class="las la-cart-plus"></i></button>
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
                                                        <span class="rating-count">(0)</span>
                                                    </div>
                                                </div>

                                                @if(!empty($product->short_description))
                                                    <div class="single_content">
                                                        <p>{{ strip_tags($product->short_description) }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="button" class="addToCart add-to-cart-btn" data-id="{{ $product->id }}" data-product_type="{{ $product->product_type ?? 1 }}">
                                                <i class="las la-shopping-bag"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        @php
                            $generalsetting = $generalsetting ?? \App\Models\GeneralSetting::first();
                            $fallbackImage = $generalsetting->frontend_empty_image ?? 'assets/images/empty.png';
                            $fallbackRaw = (string) $fallbackImage;
                            $fallbackIsUrl = preg_match('~^https?://~i', $fallbackRaw) === 1;
                            $fallbackPath = $fallbackIsUrl ? $fallbackRaw : ltrim(preg_replace('~^public/~', '', $fallbackRaw), '/');
                            $fallbackImageSrc = !empty($fallbackPath)
                                ? ($fallbackIsUrl ? $fallbackPath : asset($fallbackPath))
                                : asset('assets/images/empty.png');
                        @endphp
                        <div class="offer-product">
                            <div class="product-card">
                                <div class="product-thumb">
                                    <a href="javascript:void(0)">
                                        <img src="{{ $fallbackImageSrc }}" class="lazyload" data-src="{{ $fallbackImageSrc }}" alt="flash">
                                    </a>
                                </div>
                                <div class="product-content">
                                    <div class="product-before-content">
                                        <h6 class="title"><a href="javascript:void(0)">Coming Soon</a></h6>
                                        <div class="single_content__info"><div class="price">-</div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
