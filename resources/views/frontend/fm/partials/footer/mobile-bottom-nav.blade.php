{{--
    FM:PARTIAL footer/mobile-bottom-nav
    =========================================================================
    The phone tab bar the shop asked for, below the reference's mobile menu:

        Menu    Shop    (Home)    Cart    Account

    It is a <nav>, NOT part of the <footer> landmark - the footer is
    contentinfo and this is the primary phone navigation. It is included from
    footer-links.blade.php, immediately after </footer>, and hidden at lg and
    up (d-lg-none + the fm-footer.css guard), so a desktop never sees it.

    MENU OPENS THE EXISTING DRAWER
    -------------------------------------------------------------------------
    "Menu" is a <button> that opens #mobileNavDrawer - the same offcanvas the
    header hamburger opens. There is therefore ONE mobile menu on the site,
    not a second copy of the categories.

    ACTIVE STATE
    -------------------------------------------------------------------------
    Each link carries .active and aria-current="page" when its route group is
    the page being viewed, so the bar tells the visitor where they are.

    CART BADGE
    -------------------------------------------------------------------------
    The badge carries data-fm-cart-count, the same contract fm-cart.js already
    repaints on the header badge, so adding/removing from the mini-cart keeps
    this number in step with the server-rendered one.

    Props
        fmCartCount  int  server-rendered basket quantity (default 0)

    Usage
        @include('frontend.fm.partials.footer.mobile-bottom-nav', [
            'fmCartCount' => $fmCartCount ?? 0,
        ])
--}}
@php
    $fmMobileShopActive = request()->routeIs(
        'products.page', 'category', 'products.category', 'products.brand'
    );
    $fmMobileHomeActive = request()->routeIs('home');
    $fmMobileCartActive = request()->routeIs('cart.show');
    $fmMobileAccountActive = request()->routeIs(
        'customer.account', 'customer.login', 'customer.register', 'customer.orders'
    );

    // Guests go to the login page; a signed-in customer goes to the dashboard.
    $fmMobileAccountUrl = auth('customer')->check()
        ? route('customer.account')
        : route('customer.login');
@endphp

<nav aria-label="Mobile quick navigation" class="app-mobile-nav d-lg-none">
    <div class="app-mobile-nav-container">
        {{-- Opens the header's offcanvas drawer: one mobile menu, not two. --}}
        <button
            aria-controls="mobileNavDrawer"
            class="app-mobile-nav-item"
            data-bs-target="#mobileNavDrawer"
            data-bs-toggle="offcanvas"
            type="button"
        >
            <i aria-hidden="true" class="bi bi-grid-fill"></i>
            <span>Menu</span>
        </button>

        <a
            @if ($fmMobileShopActive) aria-current="page" @endif
            class="app-mobile-nav-item {{ $fmMobileShopActive ? 'active' : '' }}"
            href="{{ route('products.page') }}"
        >
            <i aria-hidden="true" class="bi bi-shop"></i>
            <span>Shop</span>
        </a>

        {{-- Centre-of-the-bar elevated home button. --}}
        <a
            @if ($fmMobileHomeActive) aria-current="page" @endif
            class="app-mobile-nav-item app-mobile-nav-home-wrap {{ $fmMobileHomeActive ? 'active' : '' }}"
            href="{{ route('home') }}"
        >
            <span class="app-mobile-nav-home-btn"><i aria-hidden="true" class="bi bi-house-fill"></i></span>
            <span class="app-mobile-nav-home-label">Home</span>
        </a>

        <a
            @if ($fmMobileCartActive) aria-current="page" @endif
            class="app-mobile-nav-item {{ $fmMobileCartActive ? 'active' : '' }}"
            href="{{ route('cart.show') }}"
        >
            <span class="app-mobile-nav-icon">
                <i aria-hidden="true" class="bi bi-cart-fill"></i>
                <span class="app-mobile-nav-badge" data-fm-cart-count>{{ $fmCartCount ?? 0 }}</span>
            </span>
            <span>Cart</span>
        </a>

        <a
            @if ($fmMobileAccountActive) aria-current="page" @endif
            class="app-mobile-nav-item {{ $fmMobileAccountActive ? 'active' : '' }}"
            href="{{ $fmMobileAccountUrl }}"
        >
            <i aria-hidden="true" class="bi bi-person-fill"></i>
            <span>Account</span>
        </a>
    </div>
</nav>
