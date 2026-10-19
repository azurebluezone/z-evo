{{--
    Design: hi/cart.html, <main> area (progress stepper, two column basket).
    Source: frontend/pages/cart (classic) - every control and endpoint below
    is the classic contract, not a new invention.

    Data: ShoppingController::cart_show() passes only $data, the raw Cart
    content, so everything this page shows is derived from what is already in
    the session - no extra query is introduced here.

    Feature notes that differ from the design source, kept deliberately:
      - Free shipping banner, Select All / Clear Cart, Save for Later,
        delivery slot, express pay buttons and the cross sell rail are in
        hi/cart.html but have no backend behind them (no free shipping
        threshold, no frontend clear cart route, no slot or gateway table the
        controller passes). They are left out rather than faked - see STATUS.
      - The classic summary rows (Items / Subtotal / Shipping / Discount /
        Total) are kept verbatim, including the zero Shipping row that only
        checkout fills in.
      - Minus at qty 1 removes the row, exactly like the classic page handler
        (`currentQty <= 1 -> cart/remove`); it is NOT disabled.

    Layout: the layout already yields inside <main>, so this view must not
    open a second <main>. The two sections below are the two columns; the
    page composes them inside one container/row.
-------------------------------------------------------------------------- --}}
@extends('frontend.fm.layouts')

@section('title', 'Shopping Cart')

@push('css')
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-cart.css') }}">
@endpush

@section('content')
    @php
        // Single computation pass for the whole page. Every section below
        // reads these variables; none of them re-reads the Cart.
        $fmRows   = collect($data ?? []);
        $fmLines  = [];
        $fmQty    = 0;
        $fmSubtotal = 0.0;
        $fmSavings  = 0.0;

        foreach ($fmRows as $fmRow) {
            $fmOpts     = $fmRow->options;
            $fmPrice    = (float) $fmRow->price;
            $fmRowQty   = (int) $fmRow->qty;
            $fmOldPrice = (float) ($fmOpts->old_price ?? 0);
            $fmLineTotal = $fmPrice * $fmRowQty;
            $fmSlug     = (string) ($fmOpts->slug ?? '');

            $fmQty       += $fmRowQty;
            $fmSubtotal  += $fmLineTotal;
            if ($fmOldPrice > $fmPrice) {
                $fmSavings += ($fmOldPrice - $fmPrice) * $fmRowQty;
            }

            $fmLines[] = [
                'row'     => (string) $fmRow->rowId,
                'name'    => (string) $fmRow->name,
                'qty'     => $fmRowQty,
                'price'   => $fmPrice,
                'old'     => $fmOldPrice,
                'total'   => $fmLineTotal,
                'url'     => $fmSlug !== '' ? route('product', $fmSlug) : route('products.page'),
                'image'   => \App\Support\MediaPath::url((string) ($fmOpts->image ?? '')),
                'variant' => (string) ($fmOpts->variant_name ?? ''),
            ];
        }

        // Shipping only gets a real value once checkout picks a charge, so on
        // this page it is normally 0 - same row the classic table shows.
        $fmShipping = (float) (Session::get('shipping') ?: 0);
        $fmDiscount = (float) (Session::get('discount') ?: 0);
        $fmCouponCode    = (string) (Session::get('coupon_code') ?? '');
        $fmCouponEnabled = \App\Models\Coupon::running()->exists();
        $fmTotal = ($fmSubtotal + $fmShipping) - $fmDiscount;
    @endphp

    {{-- FM:SECTION cart-progress --}}
    @include('frontend.fm.sections.cart-progress')

    @include('frontend.fm.sections.cart.basket')
@endsection

@push('script')
{{--
    The page behaviour lives in public/frontend/js/fm-cart.js - it used to
    be an inline block here, which meant the browser re-downloaded it on
    every page view and the page file could not be read without scrolling
    past hundreds of lines of JavaScript. Nothing about the behaviour changed.
--}}
<script src="{{ asset('frontend/js/fm-cart.js') }}"></script>
@endpush
