{{--
    FM:SECTION cart-summary -------------------------------------------------
    Right hand column of /shop/cart: the money card and the checkout call to
    action.

    Expects (computed once in pages/cart.blade.php):
        $fmQty           int     total quantity in the basket
        $fmSubtotal      float   sum(price * qty) over every row
        $fmShipping      float   session("shipping"), 0 until checkout sets it
        $fmDiscount      float   session("discount"), set by the coupon flow
        $fmTotal         float   (subtotal + shipping) - discount
        $fmSavings       float   sum((old_price - price) * qty), 0 when no row
                                 is discounted
        $fmCouponEnabled bool    Coupon::running()->exists()
        $fmCouponCode    string  session("coupon_code")

    The row set mirrors the classic summary exactly - Items, Subtotal,
    Shipping, Discount, Total - so no line the old page showed goes missing.
    The savings callout is extra: its number is real (cart options carry
    old_price), and it only renders while there is something to save.

    data-fm-shipping / data-fm-discount are the server values the page script
    reads instead of guessing. The coupon form and the applied chip are BOTH
    in the DOM with `hidden` toggled between them, so applying or removing a
    code swaps state without the script having to invent markup.
-------------------------------------------------------------------------- --}}

<div class="fm-basket-summary">
    <div class="fm-basket-card fm-basket-summary__card"
         data-fm-summary-card
         data-fm-shipping="{{ $fmShipping }}"
         data-fm-discount="{{ $fmDiscount }}">
        <div class="fm-basket-card__head">
            <h2 class="fm-basket-card__title">Order Summary</h2>
            <span class="fm-basket-summary__secure">
                <i class="bi bi-lock" aria-hidden="true"></i> Secure Checkout
            </span>
        </div>

        <div class="fm-basket-card__body fm-basket-summary__body">
            <div class="fm-basket-summary__saving"
                 data-fm-sum-saving
                 @if($fmSavings <= 0) hidden @endif>
                <i class="bi bi-piggy-bank" aria-hidden="true"></i>
                <span>You are saving <strong data-fm-sum-saving-value>Tk {{ number_format($fmSavings) }}</strong> on this order today.</span>
            </div>

            <dl class="fm-basket-summary__rows">
                <div class="fm-basket-summary__row">
                    <dt>Items</dt>
                    <dd data-fm-sum-items>{{ $fmQty }}</dd>
                </div>
                <div class="fm-basket-summary__row">
                    <dt>Subtotal</dt>
                    <dd data-fm-sum-subtotal>Tk {{ number_format($fmSubtotal) }}</dd>
                </div>
                <div class="fm-basket-summary__row">
                    <dt>Shipping</dt>
                    <dd data-fm-sum-shipping>Tk {{ number_format($fmShipping) }}</dd>
                </div>
                <div class="fm-basket-summary__row fm-basket-summary__row--discount"
                     data-fm-sum-discount-row
                     @if($fmDiscount <= 0) hidden @endif>
                    <dt>
                        Discount
                        <span class="fm-basket-summary__code" data-fm-sum-code>{{ $fmCouponCode }}</span>
                    </dt>
                    <dd data-fm-sum-discount>- Tk {{ number_format($fmDiscount) }}</dd>
                </div>
            </dl>

            <div class="fm-basket-summary__total">
                <span>Total</span>
                <strong data-fm-sum-total>Tk {{ number_format($fmTotal) }}</strong>
            </div>

            @if($fmCouponEnabled)
                <div class="fm-basket-summary__coupon" data-fm-coupon-block>
                    {{-- Input + Apply: hidden while a code is applied. --}}
                    <div data-fm-coupon-form @if($fmDiscount > 0) hidden @endif>
                        <label class="fm-basket-summary__label" for="fmCouponInput">Promo code</label>
                        <div class="fm-basket-summary__field">
                            <input class="fm-basket-summary__input"
                                   id="fmCouponInput"
                                   name="coupon_code"
                                   type="text"
                                   autocomplete="off"
                                   placeholder="Enter code"
                                   data-fm-coupon-input>
                            <button class="btn btn-brand-primary rounded-full px-4 py-2 text-xs font-bold"
                                    type="button"
                                    data-fm-coupon-apply>
                                Apply
                            </button>
                        </div>
                    </div>

                    {{-- Applied chip + Remove: hidden until a code is applied. --}}
                    <div class="fm-basket-summary__applied" data-fm-coupon-applied @if($fmDiscount <= 0) hidden @endif>
                        <span class="fm-basket-summary__applied-code">
                            <i class="bi bi-tag" aria-hidden="true"></i>
                            <span data-fm-applied-code>{{ $fmCouponCode }}</span>
                        </span>
                        <button class="fm-basket-summary__applied-remove"
                                type="button"
                                data-fm-coupon-remove>
                            Remove
                        </button>
                    </div>
                </div>
            @endif

            {{-- The classic page always renders this link, empty basket or not,
                 so it is never conditionally hidden here. --}}
            <a class="btn btn-brand-primary w-100 rounded-full py-3 text-sm font-bold"
               href="{{ route('customer.checkout') }}"
               data-fm-checkout>
                <i class="bi bi-shield-check me-2" aria-hidden="true"></i>Proceed to Checkout
                <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
            </a>

            {{-- The four promises come from ShopPromises::all(), the same single
                 definition service-strip and the detail pages read - not a
                 second copy of the wording. The text (hours, return window)
                 rides along as a tooltip so the card stays compact. --}}
            @php
                $fmPromises = \App\Support\ShopPromises::all();
            @endphp
            <ul class="fm-basket-summary__trust">
                @foreach($fmPromises as $fmPromise)
                    <li title="{{ $fmPromise->text }}">
                        <i class="bi {{ $fmPromise->icon }}" aria-hidden="true"></i>{{ $fmPromise->title }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
