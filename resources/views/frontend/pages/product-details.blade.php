@extends('frontend.layouts.app')

@section('title', isset($product) ? $product->name : 'Product Details')
@section('meta_description', isset($product) ? strip_tags($product->short_description ?? '') : 'Product details')

@push('css')
<link rel="stylesheet" href="{{ asset('assets/templates/basic/css/product-details-custom.css') }}">
@endpush

@section('content')
<main>
    @php
        $mainImageRaw = (string) ($product->thumbnail_image ?? '');
        $mainImagePath = preg_replace('~^public/~', '', $mainImageRaw);
        $mainImagePath = ltrim($mainImagePath, '/');

        if (!empty($mainImagePath) && !str_contains($mainImagePath, '/') && !str_contains($mainImagePath, '\\')) {
            $mainImagePath = 'uploads/product/' . $mainImagePath;
        }

        $mainImageUrl = !empty($mainImagePath) ? asset($mainImagePath) : asset('uploads/default/placeholder.png');
        $mainImageOnError = "this.onerror=null;this.src='" . asset('uploads/default/no-image.png') . "';";

        $detailsOldPrice = !empty($product->old_price) && (float) $product->old_price > (float) ($product->new_price ?? 0);

        $docRaw = (string) ($product->document ?? '');
        $docPath = preg_replace('~^public/~', '', $docRaw);
        $docPath = ltrim($docPath, '/');
        $docUrl = $docPath !== '' ? asset($docPath) : '';
    @endphp
    <div class="py-60">
        <div class="container">
            <div class="row g-4 g-xl-5">
                <div class="col-xl-9">
                    <div class="row g-4 g-xl-5 product-details-container">
                        <div class="col-md-5" id="variantImages">
                            <div class="xzoom-container product-gallery">
                                <img class="xzoom5" id="xzoom-magnific"
                                    src="{{ $mainImageUrl }}"
                                    xoriginal="{{ $mainImageUrl }}"
                                    onerror="{{ $mainImageOnError }}"
                                    width="600"
                                    height="600"
                                    style="width:100%;height:auto;aspect-ratio:1/1;object-fit:contain;" />

                                <div class="d-none">
                                    @if(isset($product_images) && count($product_images) > 0)
                                        @foreach($product_images as $img)
                                            @php
                                                $imgPath = preg_replace('~^public/~', '', (string) $img);
                                                $imgPath = ltrim($imgPath, '/');
                                                if (!empty($imgPath) && !str_contains($imgPath, '/') && !str_contains($imgPath, '\\')) {
                                                    $imgPath = 'uploads/product/' . $imgPath;
                                                }
                                                $imgUrl = !empty($imgPath) ? asset($imgPath) : asset('uploads/default/placeholder.png');
                                            @endphp
                                            <img class="zoom-image" src="{{ $imgUrl }}" data-zoom-image="{{ $imgUrl }}" alt="Image" onerror="{{ $mainImageOnError }}">
                                        @endforeach
                                    @endif
                                </div>

                                <div class="xzoom-thumbs">
                                    <div class="quick-view-slider owl-carousel owl-theme">
                                        @if(isset($product_thumbs) && count($product_thumbs) > 0)
                                            @foreach($product_thumbs as $thumb)
                                                @php
                                                    $thumbFull = preg_replace('~^public/~', '', (string) ($thumb['full'] ?? ''));
                                                    $thumbFull = ltrim($thumbFull, '/');
                                                    if (!empty($thumbFull) && !str_contains($thumbFull, '/') && !str_contains($thumbFull, '\\')) {
                                                        $thumbFull = 'uploads/product/' . $thumbFull;
                                                    }
                                                    $thumbFullUrl = !empty($thumbFull) ? asset($thumbFull) : asset('uploads/default/placeholder.png');

                                                    $thumbThumb = preg_replace('~^public/~', '', (string) ($thumb['thumb'] ?? ''));
                                                    $thumbThumb = ltrim($thumbThumb, '/');
                                                    if (!empty($thumbThumb) && !str_contains($thumbThumb, '/') && !str_contains($thumbThumb, '\\')) {
                                                        $thumbThumb = 'uploads/product/' . $thumbThumb;
                                                    }
                                                    $thumbThumbUrl = !empty($thumbThumb) ? asset($thumbThumb) : asset('uploads/default/placeholder.png');
                                                @endphp
                                                <a href="{{ $thumbFullUrl }}" class="media-item-nav" data-media_id="{{ $thumb['id'] ?? '' }}">
                                                    <img class="xzoom-gallery5" width="80" src="{{ $thumbThumbUrl }}" xpreview="{{ $thumbFullUrl }}" onerror="{{ $mainImageOnError }}">
                                                </a>
                                            @endforeach
                                        @else
                                            <a href="{{ $mainImageUrl }}" class="media-item-nav">
                                                <img class="xzoom-gallery5" width="80" src="{{ $mainImageUrl }}" xpreview="{{ $mainImageUrl }}" onerror="{{ $mainImageOnError }}">
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <div class="product-details">
                                <div class="product-header">
                                    <h3 class="product-title product-id" data-pdi="{{ $product->id ?? '' }}">{{ $product->name ?? '' }}</h3>

                                    <div class="ratings-area">
                                        <span class="ratings">
                                            @for($i=1;$i<=5;$i++)
                                                <i class="lar la-star"></i>
                                            @endfor
                                        </span>
                                        <span> | {{ ($product->reviews_count ?? 0) > 0 ? $product->reviews_count.' Reviews' : 'No Review' }}</span>
                                    </div>

                                    <div class="d-flex flex-wrap align-items-center gap-2 product-detail-price">
                                        <span class="product-price" id="productPrice">
                                            ৳ {{ $product->new_price ?? '' }}
                                            @if($detailsOldPrice)
                                                <del>৳ {{ $product->old_price }}</del>
                                            @endif
                                        </span>
                                        <span id="stockBadge"></span>
                                    </div>
                                </div>

                                @if(!empty($product->short_description))
                                    <div class="product-summary">{!! $product->short_description !!}</div>
                                @endif

                                <div class="product-types d-flex flex-column">
                                    <span>
                                        <b class="product-details-label">Categories: </b>
                                        @if(isset($product_categories) && count($product_categories) > 0)
                                            @foreach($product_categories as $idx => $cat)
                                                <a href="{{ $cat['url'] ?? '#' }}">{{ $cat['name'] }}</a>@if($idx < count($product_categories)-1) / @endif
                                            @endforeach
                                        @endif
                                    </span>

                                    <span>
                                        <b class="product-details-label">Brand:</b>
                                        {{ $product->brand_name ?? 'Non Brand' }}
                                    </span>

                                    <span>
                                        <b class="product-details-label">SKU:</b>
                                        <span id="productSku">{{ $product->sku ?? 'Not available' }}</span>
                                    </span>
                                </div>

                                <div class="d-flex gap-2 flex-column">
                                    @if(isset($variantAttributes) && is_array($variantAttributes) && count($variantAttributes) > 0)
                                        <div class="product-attribute">
                                            <div class="ajax-preloader d-none">Loading...</div>
                                            @foreach($variantAttributes as $attr)
                                                <div class="mb-2 attributeValueArea">
                                                    <div class="fw-500 mb-1">{{ $attr['name'] ?? ($attr['slug'] ?? '') }}</div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        @foreach(($attr['values'] ?? []) as $val)
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-secondary attributeBtn"
                                                                    data-attribute='@json(["id" => $val["id"], "slug" => $attr["slug"] ?? null])'
                                                                    data-media_id="{{ $val['media_id'] ?? '' }}">
                                                                {{ $val['label'] ?? '' }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(isset($product) && method_exists($product, 'isInStock') && !$product->isInStock())
                                        <div class="alert alert-warning mb-0">Out of stock</div>
                                    @else
                                        <div class="product-add-to-cart">
                                            <div class="product-quantity-input d-flex quantity" data-update="no">
                                                <button class="qtyButton minus" disabled><i class="la la-minus"></i></button>
                                                <input type="number" min="1" name="quantity" step="1" value="1" autocomplete="off">
                                                <button type="button" class="qtyButton plus"><i class="la la-plus"></i></button>
                                            </div>
                                            <button class="btn btn--base btn--sm addToCart flex-shrink-0" data-id="{{ $product->id ?? '' }}" data-product_type="{{ $product->product_type ?? 1 }}" type="button">Add To Cart</button>
                                        </div>

                                        <div class="product-wishlist d-flex gap-2 mt-3">
                                            <form action="{{ route('cart.store.buy') }}" method="POST" class="flex-grow-1 product-buy-now-form">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $product->id ?? '' }}" />
                                                <input type="hidden" name="qty" value="1" class="product-buy-now-qty" />
                                                <button type="submit" class="btn btn--base btn--sm w-100">Order Now</button>
                                            </form>

                                            @php
                                                $owEnabled = (bool) ($generalsetting->order_whatsapp_enabled ?? false);
                                                $owNumberRaw = (string) ($generalsetting->order_whatsapp_number ?? '');
                                                $owNumber = preg_replace('/[^0-9]/', '', $owNumberRaw);
                                                $owText = trim((string) ($product->name ?? ''));
                                                $owText = $owText !== '' ? ($owText . ' - ' . url()->current()) : url()->current();
                                            @endphp
                                            @if($owEnabled && !empty($owNumber))
                                                <a class="btn btn--base btn--sm w-100 flex-grow-1" target="_blank" rel="noopener" href="https://wa.me/{{ $owNumber }}?text={{ urlencode($owText) }}">Order WhatsApp</a>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="product-share">
                                    <b>Share :</b>
                                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="blank" title="Share on Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="https://pinterest.com/pin/create/button/?url={{ urlencode(url()->current()) }}&description={{ urlencode($product->name ?? '') }}&media={{ urlencode(asset($product->thumbnail_image ?? 'assets/images/default.png')) }}" title="Share on Pinterest" target="blank">
                                        <i class="fab fa-pinterest-p"></i>
                                    </a>
                                    <a href="http://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(url()->current()) }}&title={{ urlencode($product->name ?? '') }}&summary={{ urlencode(strip_tags($product->short_description ?? '')) }}" title="Share on Linkedin" target="blank">
                                        <i class="fab fa-linkedin-in"></i>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?text={{ urlencode(($product->name ?? '') . ' ' . url()->current()) }}" title="Share on Twitter" target="blank">
                                        <i class="fab fa-x-twitter"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="products-details-wrapper pt-60">
                                 <div class="products-description pt-0">
                                     <ul class="nav nav-tabs" id="productTabs">
                                         <li><a class="active" href="#description" data-bs-toggle="tab">Description</a></li>
                                         <li class="review-rating-tab"><a href="#reviews" data-bs-toggle="tab">Reviews({{ $product->reviews_count ?? 0 }})</a></li>
                                         @if(!empty($docUrl))
                                             <li>
                                                 <a href="{{ $docUrl }}" target="_blank" rel="noopener" title="Open Document">
                                                     Document
                                                 </a>
                                             </li>
                                         @endif
                                         @if(!empty($product->pro_video) || (isset($product_videos) && count($product_videos) > 0))
                                             <li><a href="#video" data-bs-toggle="tab">Video</a></li>
                                         @endif
                                     </ul>

                                     <div class="tab-content">
                                         <div class="tab-pane fade show active" id="description">
                                            <div class="description-item">
                                                {!! $product->description ?? '' !!}
                                            </div>
                                         </div>
                                         <div class="tab-pane fade" id="reviews">
                                            <div class="review-area"></div>
                                         </div>
                                         @if(!empty($product->pro_video) || (isset($product_videos) && count($product_videos) > 0))
                                             <div class="tab-pane fade" id="video">
                                                <div class="description-item">
                                                    @if(isset($product_videos) && count($product_videos) > 0)
                                                        @foreach($product_videos as $vid)
                                                            <div class="ratio ratio-16x9 mb-3">
                                                                <video controls preload="metadata" style="width:100%;height:100%;object-fit:contain;">
                                                                    <source src="{{ asset($vid) }}" type="video/mp4">
                                                                </video>
                                                            </div>
                                                        @endforeach
                                                    @elseif(!empty($product->pro_video))
                                                        @php
                                                            $videoRaw = (string) ($product->pro_video ?? '');
                                                            $videoRaw = trim($videoRaw);
                                                            $videoPath = preg_replace('~^public/~', '', $videoRaw);
                                                            $videoPath = ltrim((string) $videoPath, '/');
                                                            $isAbsolute = str_starts_with($videoRaw, 'http://') || str_starts_with($videoRaw, 'https://');
                                                            $isMp4 = str_ends_with(strtolower($videoRaw), '.mp4') || str_ends_with(strtolower($videoPath), '.mp4');
                                                            $videoUrl = $isAbsolute ? $videoRaw : (!empty($videoPath) ? asset($videoPath) : '');
                                                        @endphp

                                                        @if($isMp4 && !empty($videoUrl))
                                                            <div class="ratio ratio-16x9">
                                                                <video controls preload="metadata" style="width:100%;height:100%;object-fit:contain;">
                                                                    <source src="{{ $videoUrl }}" type="video/mp4">
                                                                </video>
                                                            </div>
                                                        @elseif(!empty($videoUrl))
                                                            <div class="ratio ratio-16x9">
                                                                <iframe src="{{ $videoUrl }}" title="Product Video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-md-12 col-xl-3">
                    <div class="sticky-sidebar">
                        <h5 class="product-details-title fw-500 mb-3">Related Products</h5>
                        <div class="row gy-3">
                            @if(isset($related_products) && count($related_products) > 0)
                                @foreach($related_products as $rp)
                                    @php
                                        $rpThumbRaw = (string) ($rp->thumbnail_image ?? optional($rp->image)->image ?? '');
                                        $rpThumbPath = preg_replace('~^public/~', '', $rpThumbRaw);
                                        $rpThumbPath = ltrim($rpThumbPath, '/');
                                        if (!empty($rpThumbPath) && !str_contains($rpThumbPath, '/') && !str_contains($rpThumbPath, '\\')) {
                                            $rpThumbPath = 'uploads/product/' . $rpThumbPath;
                                        }
                                        $rpThumbUrl = !empty($rpThumbPath) ? asset($rpThumbPath) : asset('uploads/default/placeholder.png');
                                        $rpOldOk = !empty($rp->old_price) && (float) $rp->old_price > (float) ($rp->new_price ?? 0);
                                    @endphp
                                    <div class="col-sm-6 col-md-6 col-lg-4 col-xl-12">
                                        <div class="best-sell-item">
                                            <div class="best-sell-inner d-flex flex-wrap">
                                                <div class="thumb">
                                                    <a href="{{ route('product', $rp->slug) }}">
                                                        <img src="{{ asset('uploads/default/placeholder.png') }}" data-src="{{ $rpThumbUrl }}" class="lazyload" alt="{{ $rp->name }}" onerror="{{ $mainImageOnError }}">
                                                    </a>
                                                </div>
                                                <div class="content">
                                                    <h6 class="title">
                                                        <a href="{{ route('product', $rp->slug) }}">{{ $rp->name }}</a>
                                                    </h6>
                                                    <div class="price fw-500">
                                                        ৳ {{ $rp->new_price }}
                                                        @if($rpOldOk) <del>৳ {{ $rp->old_price }}</del> @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">No related products.</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

@push('scripts')
<script src="{{ asset('assets/templates/basic/js/product_details.js') }}"></script>
<script>
    (function(){
        const qtyInput = document.querySelector('.product-quantity-input input[name="quantity"]');
        document.querySelectorAll('.product-buy-now-form').forEach((form) => {
            form.addEventListener('submit', () => {
                const hiddenQty = form.querySelector('.product-buy-now-qty');
                if (!hiddenQty) return;
                const qtyVal = qtyInput ? parseInt(qtyInput.value || '1', 10) : 1;
                hiddenQty.value = String(Number.isFinite(qtyVal) && qtyVal > 0 ? qtyVal : 1);

                // Attach selected variant attribute value IDs (if any). If none selected, backend will use DEFAULT.
                form.querySelectorAll('input[name="variant[]"]').forEach((n) => n.remove());
                document.querySelectorAll('.attributeBtn.active').forEach((btn) => {
                    try {
                        const meta = JSON.parse(btn.getAttribute('data-attribute') || '{}');
                        if (!meta || meta.id === undefined || meta.id === null || meta.id === '') return;
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'variant[]';
                        input.value = String(meta.id);
                        form.appendChild(input);
                    } catch (e) {
                    }
                });
            });
        });
    })();
</script>

<script>
    (function($){
        if (!window.jQuery || typeof $.fn.productDetails !== 'function') return;
        var productId = {{ (int) ($product->id ?? 0) }};
        if (!productId) return;

        $('.product-details').productDetails({
            productId: productId,
            totalAttributes: {{ isset($variantAttributes) && is_array($variantAttributes) ? count($variantAttributes) : 0 }},
            stockQuantity: {{ isset($product) && method_exists($product, 'displayStockQuantity') ? (int) $product->displayStockQuantity() : (int) ($product->stock ?? 0) }},
            showStockQuantity: false,
            trackInventory: true,
            variantImageLoadUrl: "{{ url('products/:productId/variant-images') }}",
            checkStockUrl: "{{ url('products/check-stock') }}?product_id=" + productId,
        });
    })(jQuery);
</script>

{{-- 🎯 ViewContent Event: Pixel + CAPI (fires on product page load) --}}
<script>
(function() {
    'use strict';
    
    const viewContentData = {
        product_id: '{{ $product->id ?? '' }}',
        name: '{!! addslashes($product->name ?? '') !!}',
        price: {{ (float) ($product->new_price ?? 0) }},
        currency: 'BDT',
        category: '{!! addslashes(($product_categories[0]['name'] ?? '')) !!}'
    };

    function fireViewContent() {
        // window.facebookTracker — already initialized singleton
        const tracker = window.facebookTracker;
        if (tracker && typeof tracker.trackViewContent === 'function') {
            tracker.trackViewContent({
                product_id: viewContentData.product_id,
                name: viewContentData.name,
                price: viewContentData.price,
                currency: viewContentData.currency,
                category: viewContentData.category
            });
        } else if (typeof fbq === 'function') {
            fbq('track', 'ViewContent', {
                content_ids: [viewContentData.product_id],
                content_type: 'product',
                value: viewContentData.price,
                currency: viewContentData.currency,
                content_name: viewContentData.name,
                content_category: viewContentData.category
            });
        }
    }

    if (document.readyState === 'complete') fireViewContent();
    else window.addEventListener('load', fireViewContent);
})();
</script>
@endpush
@endsection
