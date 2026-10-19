{{--
    FM:SECTION checkout-progress ----------------------------------------------
    Breadcrumb + flow indicator at the top of /customer/checkout.

    Relationship to cart-progress
    -----------------------------
    frontend/fm/sections/cart-progress.blade.php already renders the same four
    labels for the cart page. This file does NOT reuse it, and that is a
    deliberate trade rather than an oversight:

      - The cart page's version hard-codes Step 1 as the active, linkable step
        and Step 1's href is route('cart.show'). Checkout needs a different
        state (Steps 1-2 done, Step 3 active) and a different label set.
      - Parameterising the shared partial would mean every one of its strings
        becomes a variable, and the cart page - which is live and verified -
        would then render through a template it was never tested with. A
        regression on a working page to save ~20 lines of duplicated CSS class
        names is a bad trade.

    So the two rails share their TOKENS (fm-checkout.css copies the values from
    fm-cart.css) and nothing else. They are independent files; if one is
    restyled the other is untouched.

    Expects: nothing. Pure markup, no data dependency.
-------------------------------------------------------------------------- --}}

<section class="fm-co-steps" data-section="checkout-progress">
    <nav aria-label="breadcrumb">
        <ol class="fm-co-steps__crumbs">
            <li>
                <a href="{{ route('home') }}">
                    <i class="bi bi-house-door" aria-hidden="true"></i>
                    <span>Home</span>
                </a>
            </li>
            <li aria-hidden="true">/</li>
            <li>
                <a href="{{ route('cart.show') }}">Shopping Basket</a>
            </li>
            <li aria-hidden="true">/</li>
            <li aria-current="page">Checkout</li>
        </ol>
    </nav>

    <ol class="fm-co-steps__list">
        {{-- Steps already behind the customer link back; the active one is a
             marker, and so are the steps ahead. aria-current marks the
             position for assistive tech, not just the colour. --}}
        <li class="fm-co-steps__item fm-co-steps__item--done">
            <a href="{{ route('cart.show') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                <span class="fm-co-steps__icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                <span class="fm-co-steps__text">
                    <span class="fm-co-steps__no">Step 1</span>
                    <span class="fm-co-steps__title">My cart</span>
                </span>
            </a>
        </li>

        <li class="fm-co-steps__item fm-co-steps__item--done">
            <a href="{{ route('cart.show') }}" class="d-flex align-items-center gap-2 text-decoration-none w-100">
                <span class="fm-co-steps__icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                <span class="fm-co-steps__text">
                    <span class="fm-co-steps__no">Step 2</span>
                    <span class="fm-co-steps__title">Shipping Info</span>
                </span>
            </a>
        </li>

        <li class="fm-co-steps__item fm-co-steps__item--active" aria-current="step">
            <span class="fm-co-steps__icon"><i class="bi bi-credit-card" aria-hidden="true"></i></span>
            <span class="fm-co-steps__text">
                <span class="fm-co-steps__no">Step 3</span>
                <span class="fm-co-steps__title">Payment</span>
            </span>
        </li>

        <li class="fm-co-steps__item">
            <span class="fm-co-steps__icon"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
            <span class="fm-co-steps__text">
                <span class="fm-co-steps__no">Step 4</span>
                <span class="fm-co-steps__title">Confirmation</span>
            </span>
        </li>
    </ol>
</section>
