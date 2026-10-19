{{--
    FM:PARTIAL header/primary-navigation
    =========================================================================
    The desktop navigation strip under the header bar.

    It is deliberately hidden below the lg breakpoint: on a phone there is no
    room for six links, so the same destinations are served by the drawer behind
    the hamburger and behind the Categories pill instead. Both triggers point at
    #mobileNavDrawer, which is one Bootstrap offcanvas so the two entry points
    cannot drift apart - and the links themselves are the shared
    partials/header/nav-links partial.

    Props
        (none)

    Usage
        @include('frontend.fm.partials.header.primary-navigation')
--}}

<!-- BEGIN: NavigationBar -->
<nav aria-label="Main Navigation" class="app-navbar-strip hidden lg:block" data-purpose="primary-navigation">
    <div class="container mx-auto px-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-4">
                {{-- Category Sidebar Trigger --}}
                <button class="app-category-trigger" data-bs-target="#mobileNavDrawer" data-bs-toggle="offcanvas" id="app-category-drawer-trigger" type="button">
                    <i class="bi bi-grid-fill"></i>
                    <span>Browse Categories</span>
                    <i class="bi bi-chevron-down" style="font-size: 0.7rem;"></i>
                </button>

                {{-- Horizontal Nav Links (shared with the mobile drawer) --}}
                @include('frontend.fm.partials.header.nav-links', ['variant' => 'strip'])
            </div>
            {{-- Live Delivery Geolocation Pill --}}
        </div>
    </div>
</nav>
<!-- END: NavigationBar -->