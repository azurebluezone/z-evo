{{--
    FM:SECTION cart-progress ------------------------------------------------
    Top block of /shop/cart: the breadcrumb and the checkout flow indicator.

    The breadcrumb comes from the approved cart design and is wired to real
    routes (Home -> home, Shop -> products.page) - no dead "#" links, so it
    keeps working with JavaScript off.

    The stepper keeps the four labels the classic cart page has always shown
    (My cart / Shipping Info / Payment / Confirmation). Only step 1 is
    interactive in both designs, so it stays a real link back to cart.show;
    steps 2-4 are plain markers that describe the flow ahead.

    Expects: nothing - it is pure markup with no data dependency.
-------------------------------------------------------------------------- --}}

<section class="fm-basket-progress" data-section="cart-progress">
    <div class="container mx-auto px-4">
        <nav aria-label="breadcrumb">
            <ol class="fm-basket-progress__crumbs">
                <li>
                    <a href="{{ route('home') }}">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li aria-hidden="true">/</li>
                <li>
                    <a href="{{ route('products.page') }}">Shop</a>
                </li>
                <li aria-hidden="true">/</li>
                <li aria-current="page">Shopping Basket</li>
            </ol>
        </nav>

        <ol class="fm-basket-progress__steps">
            <li class="fm-basket-progress__step is-active">
                <a href="{{ route('cart.show') }}">
                    <span class="fm-basket-progress__icon"><i class="bi bi-basket2" aria-hidden="true"></i></span>
                    <span class="fm-basket-progress__text">
                        <span class="fm-basket-progress__no">Step 1</span>
                        <span class="fm-basket-progress__title">My cart</span>
                    </span>
                </a>
            </li>
            <li class="fm-basket-progress__step">
                <span class="fm-basket-progress__icon"><i class="bi bi-truck" aria-hidden="true"></i></span>
                <span class="fm-basket-progress__text">
                    <span class="fm-basket-progress__no">Step 2</span>
                    <span class="fm-basket-progress__title">Shipping Info</span>
                </span>
            </li>
            <li class="fm-basket-progress__step">
                <span class="fm-basket-progress__icon"><i class="bi bi-credit-card" aria-hidden="true"></i></span>
                <span class="fm-basket-progress__text">
                    <span class="fm-basket-progress__no">Step 3</span>
                    <span class="fm-basket-progress__title">Payment</span>
                </span>
            </li>
            <li class="fm-basket-progress__step">
                <span class="fm-basket-progress__icon"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
                <span class="fm-basket-progress__text">
                    <span class="fm-basket-progress__no">Step 4</span>
                    <span class="fm-basket-progress__title">Confirmation</span>
                </span>
            </li>
        </ol>
    </div>
</section>
