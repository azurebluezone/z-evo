{{--
    FM:SECTION checkout-page ---------------------------------------------------
    Modern checkout: resources/views/frontend/pages/customer/checkout.blade.php
    was rewritten onto the FreshMart chrome.

    ---------------------------------------------------------------------------
    CONTRACT - DO NOT DIVERGE WITHOUT READING THE CONTROLLER
    ---------------------------------------------------------------------------
    CustomerController@checkout() passes exactly three variables and this page
    is allowed to assume nothing else:

        $shippingcharge      Collection<ShippingCharge>  (id, name, amount)
        $bkash_gateway       PaymentGateway|null        (status=1, type=bkash)
        $shurjopay_gateway   PaymentGateway|null        (status=1, type=shurjopay)

    Everything else comes from the session, exactly as before:
        shipping          float   selected ShippingCharge->amount
        shipping_area_id  int     selected ShippingCharge->id
        discount          float   coupon discount already in the session
        coupon_code       string  coupon code already in the session

    The form is POSTed to route('customer.ordersave') and carries the SAME five
    field names the classic page sent. order_save() reads them by name, so
    renaming any of them silently breaks order placement:

        name, address, phone, area, payment_method

    payment_method values are the literal strings the gateway branch in
    order_save() switches on: 'Cash On Delivery', 'bkash', 'shurjopay'.
    bKash and Shurjopay are rendered only when their gateway row is enabled,
    which is why the @if wrappers must stay.

    ---------------------------------------------------------------------------
    WHAT WAS PORTED FROM THE CLASSIC PAGE, UNCHANGED IN BEHAVIOUR
    ---------------------------------------------------------------------------
    * Cart rows read Cart::instance('shopping')->content() and carry
      data-rowid / data-unit-price / data-qty so the number on screen and the
      number posted can never disagree.
    * Quantity +/- posts to /cart-update/{rowId} with {quantity}.
    * Remove posts to /remove-from-cart/{rowId} and re-syncs the header badge.
    * Changing the delivery area GETs route('shipping.charge') and re-renders
      the totals.
    * Coupon posts {coupon_code, subtotal} to route('coupon.apply').
    * Totals are subtotal + shipping - discount, recomputed client side from
      the DOM after every mutation.
    * The InitiateCheckout event (Pixel + CAPI) still fires on load, with the
      same total / num_items / product_ids payload.

    ---------------------------------------------------------------------------
    TWO DELIBERATE CHANGES
    ---------------------------------------------------------------------------
    1. CSRF token. The classic inline script read
       document.querySelector('meta[name="csrf-token"]').content. No such meta
       tag exists anywhere in the modern chrome - fm-app.js ships tokens via
       window.FM_CONFIG - so the same code would have sent an empty token and
       every quantity change and coupon apply would have 419'd. fm-checkout.js
       therefore reads window.FM_CONFIG.csrfToken first and only falls back to
       the meta tag. (The meta tag was also added to frontend/fm/layouts.blade.php
       so no future page has to rediscover this.)

    2. Variant label. The classic row printed
       options.product_size, which ShoppingController stores as the literal
       string "variant_id:123" when a variant is picked. That is a storage
       marker, not a size, and printing it showed the customer "Size:
       variant_id:123". This page prints options.variant_name - the label the
       customer actually chose - and only falls back to product_size when
       variant_name is absent AND the marker is not a variant_id. Nothing is
       sent back to the server from here, so this is presentation only.

    The classic stylesheet's bd-ck- prefix is gone; see fm-checkout.css.
-------------------------------------------------------------------------- --}}
@extends('frontend.fm.layouts')

@section('title', 'Checkout')

@push('css')
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-checkout.css') }}">
@endpush

@section('content')
    @php
        // ---- Session mirror (identical reads to the classic page) ----------
        // subtotal() answers a formatted string ("1,050.00"): strip the
        // thousands separator BEFORE casting, or the cast stops there.
        $coCart = \Cart::instance('shopping');

        $coSubtotal  = (float) str_replace(',', '', (string) $coCart->subtotal());
        $coShipping  = (float) (Session::get('shipping') ?: 0);
        $coAreaId    = (int)  (Session::get('shipping_area_id') ?? 0);
        $coDiscount  = (float) (Session::get('discount') ?: 0);
        $coCoupon    = (string) (Session::get('coupon_code') ?? '');
        $coCouponOn  = \App\Models\Coupon::running()->exists();
        $coCount     = (int) $coCart->count();

        // ---- One pass over the cart, so every partial below reads a plain
        // ---- array instead of re-reading the Cart facade.
        $coLines = [];
        $coProductIds = [];

        foreach ($coCart->content() as $coItem) {
            $coOpts = $coItem->options;

            $coQty   = (int) $coItem->qty;
            $coUnit  = (float) $coItem->price;
            $coName  = (string) $coItem->name;
            $coImage = (string) ($coOpts->image ?? '');

            $coProductIds[] = (int) ($coItem->id ?? 0);

            // Human variant label, with the "variant_id:N" storage marker
            // filtered out rather than printed at the customer.
            $coVariant = trim((string) ($coOpts->variant_name ?? ''));
            if ($coVariant === '') {
                $coLegacy = trim((string) ($coOpts->product_size ?? ''));
                $coVariant = ($coLegacy !== '' && ! str_starts_with($coLegacy, 'variant_id:'))
                    ? $coLegacy
                    : trim((string) ($coOpts->product_color ?? ''));
            }

            $coColour = trim((string) ($coOpts->product_color ?? ''));

            $coLines[] = [
                'row'     => (string) $coItem->rowId,
                'name'    => \Illuminate\Support\Str::limit($coName, 28),
                'qty'     => $coQty,
                'unit'    => $coUnit,
                'total'   => $coUnit * $coQty,
                'image'   => $coImage !== '' ? \App\Support\MediaPath::url($coImage, 'product') : '',
                'variant' => $coVariant,
                'colour'  => ($coVariant !== '' && $coColour !== '' && $coColour !== $coVariant) ? $coColour : '',
            ];
        }

        $coGrand = ($coSubtotal + $coShipping) - $coDiscount;
    @endphp

    <section class="fm-co" data-section="checkout">
        <div class="fm-co__container">

            {{-- FM:SECTION checkout-steps --}}
            @include('frontend.fm.sections.checkout.progress')
            <!-- /FM:SECTION checkout-steps -->

            <div class="fm-co__grid">

                {{-- ================= LEFT: recipient + payment ================= --}}
                <div class="fm-co__col">

                    <div class="fm-co__card">
                        <div class="fm-co__card-head">
                            <p class="fm-co__eyebrow">Step 3 of 4</p>
                            <h1 class="fm-co__card-title">Recipient's Info</h1>
                            <p class="fm-co__card-sub">
                                Complete the form to place your order.
                                @if($generalsetting->support_phone ?? null)
                                    Need help? Call
                                    <a href="tel:{{ preg_replace('/\D/', '', $generalsetting->support_phone) }}"
                                       class="text-decoration-none">{{ $generalsetting->support_phone }}</a>.
                                @endif
                            </p>
                        </div>

                        {{-- One form for the whole column. The Place Order button
                             lives inside the payment card at the bottom of this
                             form, exactly as the classic page had it. --}}
                        <form action="{{ route('customer.ordersave') }}"
                              method="POST"
                              novalidate>
                            @csrf

                            <div class="fm-co__grid-fields">

                                {{-- ---- name ---- --}}
                                <div class="fm-co__field">
                                    <label class="fm-co__label" for="coName">
                                        Full Name <span class="req">*</span>
                                    </label>
                                    <input type="text"
                                           id="coName"
                                           name="name"
                                           class="fm-co__input @error('name') is-invalid @enderror"
                                           placeholder="MD. Rahim Uddin"
                                           value="{{ old('name') }}"
                                           autocomplete="name"
                                           required>
                                    <p class="fm-co__error">@error('name') {{ $message }} @enderror</p>
                                </div>

                                {{-- ---- phone ---- --}}
                                {{-- Kept in the same row as name rather than below
                                     it: the classic page stacked all four fields and
                                     pushed the payment choice below the fold on a
                                     phone. --}}
                                <div class="fm-co__field">
                                    <label class="fm-co__label" for="coPhone">
                                        Phone <span class="req">*</span>
                                    </label>
                                    <input type="tel"
                                           id="coPhone"
                                           name="phone"
                                           class="fm-co__input @error('phone') is-invalid @enderror"
                                           placeholder="01XXXXXXXXX"
                                           value="{{ old('phone') }}"
                                           autocomplete="tel"
                                           inputmode="numeric"
                                           minlength="11"
                                           maxlength="11"
                                           pattern="0[0-9]+"
                                           required>
                                    <p class="fm-co__error">@error('phone') {{ $message }} @enderror</p>
                                </div>

                                {{-- ---- address ---- --}}
                                <div class="fm-co__field fm-co__field--wide">
                                    <label class="fm-co__label" for="coAddress">
                                        Address <span class="req">*</span>
                                    </label>
                                    <input type="text"
                                           id="coAddress"
                                           name="address"
                                           class="fm-co__input @error('address') is-invalid @enderror"
                                           placeholder="House 12, Road 5, Block-C"
                                           value="{{ old('address') }}"
                                           autocomplete="street-address"
                                           required>
                                    <p class="fm-co__error">@error('address') {{ $message }} @enderror</p>
                                </div>

                                {{-- ---- area ---- --}}
                                <div class="fm-co__field fm-co__field--wide">
                                    <label class="fm-co__label" for="coArea">
                                        Shipping Area <span class="req">*</span>
                                    </label>
                                    <select id="coArea"
                                            name="area"
                                            class="fm-co__select @error('area') is-invalid @enderror"
                                            required>
                                        <option value="" disabled {{ $coAreaId === 0 ? 'selected' : '' }}>
                                            Select Delivery Area
                                        </option>
                                        @foreach($shippingcharge as $coCharge)
                                            <option value="{{ $coCharge->id }}"
                                                {{ $coAreaId === (int) $coCharge->id ? 'selected' : '' }}>
                                                {{ $coCharge->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="fm-co__error">@error('area') {{ $message }} @enderror</p>
                                </div>
                            </div>

                            {{-- ================= Payment ================= --}}
                            <div class="fm-co__card" style="box-shadow:none;padding-left:0;padding-right:0;border-left:0;border-right:0;border-radius:0;background:transparent;margin-top:1.75rem;">
                                <div class="fm-co__card-head" style="margin-bottom:.75rem;">
                                    <p class="fm-co__eyebrow">Step 3 of 4</p>
                                    <h2 class="fm-co__card-title">Payment Method</h2>
                                </div>

                                {{-- A <fieldset>/<legend> pair rather than a bare div:
                                     the radios are one logical control and screen
                                     readers announce them as a group. --}}
                                <fieldset style="border:0;padding:0;margin:0;">
                                    <legend class="visually-hidden">Choose a payment method</legend>

                                    <div class="fm-co__pay-list" data-fm-pay-list>

                                        <label class="fm-co__pay is-selected" data-fm-pay>
                                            <input type="radio"
                                                   name="payment_method"
                                                   value="Cash On Delivery"
                                                   id="coPayCod"
                                                   checked>
                                            <span class="fm-co__pay-text">
                                                <span class="fm-co__pay-name">
                                                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                                    Cash On Delivery
                                                </span>
                                                <span class="fm-co__pay-note">Pay the courier when it arrives</span>
                                            </span>
                                        </label>

                                        @if($bkash_gateway)
                                            <label class="fm-co__pay" data-fm-pay>
                                                <input type="radio"
                                                       name="payment_method"
                                                       value="bkash"
                                                       id="coPayBkash">
                                                <span class="fm-co__pay-text">
                                                    <span class="fm-co__pay-name">
                                                        <i class="bi bi-phone" aria-hidden="true"></i>
                                                        bKash
                                                    </span>
                                                    <span class="fm-co__pay-note">Pay from your bKash wallet</span>
                                                </span>
                                            </label>
                                        @endif

                                        @if($shurjopay_gateway)
                                            <label class="fm-co__pay" data-fm-pay>
                                                <input type="radio"
                                                       name="payment_method"
                                                       value="shurjopay"
                                                       id="coPayShurjo">
                                                <span class="fm-co__pay-text">
                                                    <span class="fm-co__pay-name">
                                                        <i class="bi bi-credit-card" aria-hidden="true"></i>
                                                        Shurjopay
                                                    </span>
                                                    <span class="fm-co__pay-note">Card, mobile banking or ATM</span>
                                                </span>
                                            </label>
                                        @endif
                                    </div>
                                </fieldset>

                                <div class="fm-co__actions">
                                    <a href="{{ route('cart.show') }}" class="fm-co__btn fm-co__btn--ghost">
                                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                                        Back to cart
                                    </a>
                                    <button type="submit" class="fm-co__btn fm-co__btn--primary" data-fm-submit>
                                        <i class="bi bi-bag-check" aria-hidden="true"></i>
                                        Place Order
                                    </button>
                                </div>

                                <div class="fm-co__trust">
                                    <span><i class="bi bi-shield-check" aria-hidden="true"></i> Secure checkout</span>
                                    <span><i class="bi bi-truck" aria-hidden="true"></i> Nationwide delivery</span>
                                    <span><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Easy returns</span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ================= RIGHT: order summary ================= --}}
                <aside class="fm-co__col" aria-labelledby="coSummaryTitle">
                    <div class="fm-co__summary" data-fm-summary>

                        <div class="fm-co__summary-head">
                            <div class="fm-co__card-head">
                                <h2 class="fm-co__card-title" id="coSummaryTitle">
                                    <i class="bi bi-basket2" aria-hidden="true"></i>
                                    Order Summary
                                </h2>
                            </div>
                            <p class="fm-co__summary-mini">
                                Grand Total
                                <strong data-fm-grand-mini>৳ {{ number_format($coGrand, 0) }}</strong>
                            </p>
                            <button type="button"
                                    class="fm-co__summary-toggle"
                                    data-fm-summary-toggle
                                    aria-expanded="false"
                                    aria-controls="coSummaryBody">
                                <span data-fm-summary-toggle-text>Show summary</span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="fm-co__summary-body is-open" id="coSummaryBody" data-fm-summary-body>

                            <ul class="fm-co__lines" data-fm-lines>
                                @forelse($coLines as $coLine)
                                    <li class="fm-co__line"
                                        data-fm-line
                                        data-rowid="{{ $coLine['row'] }}"
                                        data-unit-price="{{ $coLine['unit'] }}"
                                        data-qty="{{ $coLine['qty'] }}">

                                        @if($coLine['image'] !== '')
                                            <img class="fm-co__line-thumb"
                                                 src="{{ $coLine['image'] }}"
                                                 alt="{{ $coLine['name'] }}"
                                                 width="52" height="52"
                                                 loading="lazy">
                                        @else
                                            {{-- No image stored: a placeholder tile beats a
                                                 broken image icon next to the line. --}}
                                            <span class="fm-co__line-thumb d-flex align-items-center justify-content-center"
                                                  aria-hidden="true">
                                                <i class="bi bi-image text-muted"></i>
                                            </span>
                                        @endif

                                        <div class="fm-co__line-info">
                                            <p class="fm-co__line-name">{{ $coLine['name'] }}</p>
                                            <p class="fm-co__line-meta">
                                                @if($coLine['variant'] !== '')
                                                    {{ $coLine['variant'] }}
                                                @endif
                                                @if($coLine['colour'] !== '')
                                                    {{ $coLine['colour'] }}
                                                @endif
                                            </p>
                                        </div>

                                        <div class="fm-co__line-side">
                                            <span class="fm-co__line-price" data-fm-line-price>
                                                ৳ {{ number_format($coLine['total'], 0) }}
                                            </span>

                                            <span class="d-flex align-items-center gap-1">
                                                <span class="fm-co__qty">
                                                    <button type="button"
                                                            class="fm-co__qty-btn"
                                                            data-fm-dec
                                                            aria-label="Decrease quantity of {{ $coLine['name'] }}">
                                                        <i class="bi bi-dash" aria-hidden="true"></i>
                                                    </button>
                                                    <span class="fm-co__qty-num" data-fm-line-qty>{{ $coLine['qty'] }}</span>
                                                    <button type="button"
                                                            class="fm-co__qty-btn"
                                                            data-fm-inc
                                                            aria-label="Increase quantity of {{ $coLine['name'] }}">
                                                        <i class="bi bi-plus" aria-hidden="true"></i>
                                                    </button>
                                                </span>

                                                <button type="button"
                                                        class="fm-co__line-remove"
                                                        data-fm-remove
                                                        aria-label="Remove {{ $coLine['name'] }} from cart">
                                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </li>
                                @empty
                                    <li class="fm-co__empty">
                                        <i class="bi bi-basket2" aria-hidden="true"></i>
                                        <p>Your cart is empty.</p>
                                        <a href="{{ route('products.page') }}" class="btn-brand-primary btn btn-sm px-3">
                                            Shop now
                                        </a>
                                    </li>
                                @endforelse
                            </ul>

                            {{-- ---- coupon ---- --}}
                            @if($coCouponOn)
                                <div class="fm-co__coupon">
                                    <input type="text"
                                           id="coCouponInput"
                                           class="fm-co__coupon-input"
                                           name="coupon_code"
                                           placeholder="Coupon code (SAVE10 / WELCOME5)"
                                           value="{{ $coCoupon }}"
                                           autocomplete="off">
                                    <button type="button" class="fm-co__coupon-btn" data-fm-coupon-apply>
                                        Apply
                                    </button>
                                </div>
                                <p class="fm-co__coupon-status" data-fm-coupon-status hidden></p>
                            @endif

                            {{-- ---- totals ---- --}}
                            {{-- These four cells are the single source of truth for
                                 the numbers on screen. fm-checkout.js recomputes
                                 subtotal from the cart rows and re-reads shipping
                                 and discount from here, which is why their values
                                 live in data- attributes rather than being parsed
                                 back out of formatted text (the classic script
                                 stripped "৳" and "," with a regex, which silently
                                 returns 0 for any future "Tk 1,050.00" string). --}}
                            <dl class="fm-co__totals">
                                <div class="fm-co__total-row">
                                    <dt>Subtotal</dt>
                                    <dd data-fm-subtotal
                                        data-value="{{ $coSubtotal }}">
                                        ৳ {{ number_format($coSubtotal, 0) }}
                                    </dd>
                                </div>
                                <div class="fm-co__total-row">
                                    <dt>Shipping</dt>
                                    <dd data-fm-shipping
                                        data-value="{{ $coShipping }}">
                                        ৳ {{ number_format($coShipping, 0) }}
                                    </dd>
                                </div>
                                <div class="fm-co__total-row fm-co__total-row--discount">
                                    <dt>Discount (coupon)</dt>
                                    <dd data-fm-discount
                                        data-value="{{ $coDiscount }}">
                                        − ৳ {{ number_format($coDiscount, 0) }}
                                    </dd>
                                </div>
                                <div class="fm-co__total-row fm-co__total-row--grand">
                                    <dt>Grand Total</dt>
                                    <dd data-fm-grand>৳ {{ number_format($coGrand, 0) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection

@push('script')
    {{--
        Behaviour lives in public/frontend/js/fm-checkout.js rather than an inline
        block, for the same reason fm-cart.js exists: the page template stays
        readable and the browser can cache the script instead of re-downloading
        ~250 lines on every visit. Every endpoint and the CSRF token are passed
        through window.FM_CONFIG, which frontend/fm/layouts.blade.php defines
        for the whole template set.
    --}}
    <script>
        window.FM_CO = {
            shippingChargeUrl: @json(route('shipping.charge')),
            couponApplyUrl:   @json(route('coupon.apply')),
            cartUpdateUrl:    @json(url('/cart-update')),
            cartRemoveUrl:    @json(url('/remove-from-cart')),
            /* Pixel payload, precomputed server side so the browser is not
               re-deriving it (and cannot get it wrong). */
            tracking: {
                total:       {{ $coGrand }},
                currency:    'BDT',
                num_items:   {{ $coCount }},
                product_ids: @json($coProductIds)
            }
        };
    </script>
    <script src="{{ asset('frontend/js/fm-checkout.js') }}"></script>
@endpush
