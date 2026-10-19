@if(isset($sections) && ($sections['flash_sale']['show'] ?? false) && isset($flash_sale))
    <div class="my-60">
        <div class="container">
            <div class="section-header left-style d-flex justify-content-between align-items-center">
                <h5 class="title">{{ $sections['flash_sale']['title'] ?? ($flash_sale->title ?? 'FLASH SALE') }}</h5>
            </div>

            <div class="offer-wrapper">
                <div class="row g-4 align-items-stretch">
                    <div class="col-12">
                        @php
                            $flashSaleStartIso = null;
                            $flashSaleEndIso = null;

                            if (isset($flash_sale) && $flash_sale->start_time && $flash_sale->end_time) {
                                try {
                                    $flashSaleStartIso = $flash_sale->start_time->toIso8601String();
                                    $flashSaleEndIso = $flash_sale->end_time->toIso8601String();
                                } catch (\Throwable $e) {
                                    $flashSaleStartIso = null;
                                    $flashSaleEndIso = null;
                                }
                            }
                        @endphp

                        <div class="h-100 rounded--5" style="border:2px solid #c97; background:#fff; padding:16px; display:flex; flex-direction:column; justify-content:flex-start;" data-flash-sale-start="{{ $flashSaleStartIso }}" data-flash-sale-end="{{ $flashSaleEndIso }}" id="homeFlashSalePanel">
                            @php
                                $flashSaleTitle = (isset($flash_sale) && method_exists($flash_sale, 'isRunning') && $flash_sale->isRunning())
                                    ? ($flash_sale->title ?? 'FLASH SALE')
                                    : ($flash_sale->title ?? 'FLASH SALE');
                            @endphp
                            <div style="text-align:center; font-weight:800; font-size:32px; line-height:1; color:#e11d48; letter-spacing:1px;">{{ $flashSaleTitle }}</div>
                            <div style="margin-top:14px; border:1px solid #ddd; border-radius:8px; padding:10px; background:#f6f6f6; max-width:420px; margin-left:auto; margin-right:auto;">
                                <div style="font-family:monospace; font-weight:800; font-size:44px; text-align:center; line-height:1; color:#222;" id="homeFlashSaleCountdown">00:00:00</div>
                                <div style="display:flex; justify-content:space-between; margin-top:6px; font-size:14px; color:#111;">
                                    <span>Hours</span>
                                    <span>Minutes</span>
                                    <span>Seconds</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const panel = document.getElementById('homeFlashSalePanel');
            const output = document.getElementById('homeFlashSaleCountdown');
            if (!panel || !output) return;

            const endRaw = panel.getAttribute('data-flash-sale-end');
            if (!endRaw) return;

            const endTime = new Date(endRaw).getTime();
            if (!Number.isFinite(endTime)) return;

            const pad = (n) => String(n).padStart(2, '0');

            const tick = () => {
                const now = Date.now();
                const diff = Math.max(0, endTime - now);
                const totalSeconds = Math.floor(diff / 1000);
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;
                output.textContent = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
            };

            tick();
            setInterval(tick, 1000);
        });
    </script>
    @endpush
@endif
{{-- Flash Sale Section --}}
@php
    $showFlashSale = isset($flash_sale) && $flash_sale->isRunning() && isset($flash_sale_products) && $flash_sale_products->count() > 0;
@endphp

@if($showFlashSale)
    <section class="flash-sell-section my-60">
        <div class="container">
            <div class="section-header left-style d-flex justify-content-between align-items-center">
                <h5 class="title">{{ $flash_sale->title ?? 'Grand Opening Sale' }}</h5>
            </div>

            <div class="flash-sell-slider-wrapper">
                <div class="flash-sell-slider owl-theme owl-carousel">
                    @foreach($flash_sale_products as $product)
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
    </section>
@endif
