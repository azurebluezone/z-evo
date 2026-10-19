@php
    if (!isset($cart_items)) {
        $cart_items = [];
        foreach (Cart::instance('shopping')->content() as $row) {
            $thumb = $row->options->image ?? '';
            $thumb = preg_replace('~^public/~', '', (string) $thumb);
            $thumb = ltrim($thumb, '/');

            $cart_items[] = [
                'row_id' => $row->rowId,
                'product_id' => $row->id,
                'name' => $row->name,
                'qty' => (int) $row->qty,
                'price_raw' => (float) $row->price,
                'price' => $row->price,
                'total_raw' => (float) ($row->price * $row->qty),
                'thumb' => $thumb ?: 'assets/images/default.png',
                'url' => !empty($row->options->slug ?? null) ? route('products.show', $row->options->slug) : '#',
            ];
        }
    }

    if (!isset($cart_subtotal_raw)) {
        $cart_subtotal_raw = (string) Cart::instance('shopping')->subtotal();
        $cart_subtotal_raw = str_replace([',', '.00'], ['', ''], $cart_subtotal_raw);
    }

    if (!isset($cart_total_raw)) {
        $cart_total_raw = $cart_subtotal_raw;
    }

    $discount = Session::get('discount') ? Session::get('discount') : 0;
    $couponCode = (string) (Session::get('coupon_code') ?? '');
    $isCouponEnabled = \App\Models\Coupon::running()->exists();
@endphp

@php
    $isAjaxFragment = request()->ajax();
@endphp

@if(!$isAjaxFragment)
<div class="site-sidebar cart-sidebar-area" id="cart-sidebar-area">
    <button class="sidebar-close-btn"><i class="las la-times"></i></button>
    <div class="top-content d-flex gap-2">
        <h5 class="cart-sidebar-area__title">My Cart</h5>
        <a href="{{ url('/cart') }}" class="text-muted text-decoration-underline">Cart Page</a>
    </div>
@endif

@php
    $resolveThumb = function ($path) {
        $path = preg_replace('~^public/~', '', (string) $path);
        $path = ltrim($path, '/');
        if ($path === '' || !\Illuminate\Support\Facades\File::exists(public_path($path))) {
            return 'uploads/default/placeholder.png';
        }
        return $path;
    };
@endphp

@if(!$isAjaxFragment)
    <div class="cart-products cart--products">
@endif

        <div class="cart-items-wrapper">
            @if(isset($cart_items) && count($cart_items) > 0)
                @foreach($cart_items as $item)
                    <div class="single-product-item cartItem" data-id="{{ $item['row_id'] ?? $item['id'] ?? '' }}" data-price="{{ $item['price_raw'] ?? '' }}">
                        <div class="thumb">
                            @php($thumbPath = $resolveThumb($item['thumb'] ?? ''))
                            <img class="lazyload" src="{{ asset('uploads/default/placeholder.png') }}" data-src="{{ asset($thumbPath) }}" alt="product-image">
                        </div>
                        <div class="content">
                            <div class="content-top">
                                <div class="content-top-left">
                                    <a class="title" href="{{ $item['url'] ?? '#' }}">
                                        {{ $item['name'] ?? '' }}
                                    </a>

                                    <div class="d-flex flex-column">
                                        <div class="text-muted cart-item-price">
                                            <span>Price:</span>
                                            <span class="text">{{ $item['price'] ?? '' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="cart-item-action">
                                    <button class="addToWishlist text-muted" data-id="{{ $item['product_id'] ?? '' }}"><i class="lar la-heart"></i></button>
                                    <button class="removeCart text-muted" data-id="{{ $item['row_id'] ?? '' }}" data-pid="{{ $item['product_id'] ?? '' }}" type="button"><i class="las la-trash"></i></button>
                                </div>
                            </div>

                            <div class="content-bottom">
                                <div class="product-quantity-input d-flex quantity">
                                    <button class="qtyButton minus" disabled><i class="la la-minus"></i></button>
                                    <input type="number" min="1" name="quantity" step="1" value="{{ $item['qty'] ?? 1 }}" autocomplete="off">
                                    <button type="button" class="qtyButton plus"><i class="la la-plus"></i></button>
                                </div>
                                <div class="d-flex flex-column text-center">
                                    <span class="total-price fs-sm text-muted">Total Price</span>
                                    <span class="total-price-text">
                                        ৳<span class="totalPrice fw-600">{{ $item['total_raw'] ?? '' }}</span>
                                    </span>
                                </div>
                            </div>

                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="cart-bottom">
            <div class="coupon-content py-3">
                @if($isCouponEnabled)
                    <div class="coupon-content__form">
                        <div class="applyCouponContainer">
                            <div class="apply-coupon-code">
                                <input class="form-control form--control coupon" name="coupon_code" type="text" placeholder="Enter Coupon Code" value="">
                                <button id="applyCoupon" type="button">Apply</button>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="subtotal-wrapper gap-4">
                    <span class="fs-16">Subtotal :</span>
                    <strong>৳<span class="cartSubtotal">{{ $cart_subtotal_raw ?? '' }}</span></strong>
                </div>
            </div>

            @if($isCouponEnabled)
                <div class="couponContent {{ $discount > 0 ? '' : 'd-none' }} mb-2">
                    <div class="d-flex gap-3 justify-content-end align-items-center coupon-inner">
                        <div>
                            <button type="button" class="text-danger remove-coupon ps-0 removeCoupon"><i class="la la-times-circle"></i></button>
                            <small>Coupon (<span class="couponCode fw-bold">{{ $couponCode }}</span>):</small>
                        </div>
                        <strong class="amount fs-16 d-block">- ৳<span id="couponAmount">{{ $discount }}</span></strong>
                    </div>
                </div>
            @endif

            <div class="d-flex gap-3 justify-content-end cart-total-wrapper">
                <span>Total :</span>
                <strong class="amount d-block">৳<span id="finalTotal">{{ max(0, (float) ($cart_total_raw ?? 0) - (float) $discount) }}</span></strong>
            </div>

            <div class="btn-wrapper text-end">
                <a class="btn btn--base mt-3" href="{{ route('customer.checkout') }}">Checkout</a>
            </div>
        </div>
@if(!$isAjaxFragment)
    </div>
@endif

@if(!$isAjaxFragment)
</div>
@endif
