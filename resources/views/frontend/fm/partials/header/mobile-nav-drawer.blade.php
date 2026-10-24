{{--
    FM:PARTIAL header/mobile-nav-drawer
    =========================================================================
    One offcanvas serves the phone hamburger and the nav strip's "Browse
    Categories" trigger: the categories first, because that is what a visitor is
    reaching for, then the same destinations as the desktop strip.

    The destination block is the shared partials/header/nav-links partial, so a
    new page appears in the drawer and in the desktop strip in one edit. The
    account block below it has no desktop twin - desktop puts the account menu
    in the header bar - so it lives here and only here.

    Props
        (none)

    Usage
        @include('frontend.fm.partials.header.mobile-nav-drawer')
--}}

<div aria-labelledby="mobileNavDrawerLabel" class="offcanvas offcanvas-start" id="mobileNavDrawer" tabindex="-1">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title text-[#1b5e20] font-bold" id="mobileNavDrawerLabel">
            <i class="bi bi-grid-fill me-2"></i>Browse Categories
        </h5>
        <button aria-label="Close" class="btn-close" data-bs-dismiss="offcanvas" type="button"></button>
    </div>
    <div class="offcanvas-body">
        {{-- Departments, and under each of them its sub-departments. The tree
             is the shared partial: the classic mobile menu printed the same
             three levels from the same $menucategories query. --}}
        @include('frontend.fm.partials.header.nav-categories')

        <a class="fm-drawer-cat fm-drawer-cat--all" href="{{ route('categories.page') }}">
            <i class="bi bi-grid-3x3-gap text-emerald-600"></i>
            <span>View all categories</span>
        </a>

        <hr class="my-3"/>

        {{-- Same destinations as the desktop strip. --}}
        @include('frontend.fm.partials.header.nav-links', ['variant' => 'drawer'])

        <hr class="my-3"/>

        <ul class="fm-drawer-links list-unstyled m-0">
            <li><a href="{{ route('wishlist') }}"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
            {{-- The AI Lens pill is hidden from the bar below 1024px (fm-ai.css), so the
                 drawer carries it there or those widths would lose AI search entirely.
                 The two inline triggers in the search bar are hidden at the same
                 breakpoint (fm-ai.css again), so the drawer carries the voice entry
                 too - otherwise hiding them would quietly remove voice search from
                 every tablet width. --}}
            <li class="fm-drawer-ai d-lg-none">
                @include('frontend.fm.sections.ai.ai-lens-toggle', ['mode' => 'camera', 'label' => 'AI Lens'])
            </li>
            <li class="fm-drawer-ai d-lg-none">
                @include('frontend.fm.sections.ai.ai-lens-toggle', ['mode' => 'voice', 'label' => 'Voice Search'])
            </li>
            @auth('customer')
                <li><a href="{{ route('customer.account') }}"><i class="bi bi-speedometer2 me-2"></i>My account</a></li>
                <li><a href="{{ route('customer.orders') }}"><i class="bi bi-box-seam me-2"></i>My orders</a></li>
                <li>
                    <form method="POST" action="{{ route('customer.logout') }}">@csrf<button type="submit" class="fm-drawer-logout"><i class="bi bi-box-arrow-right me-2"></i>Log out</button></form>
                </li>
            @else
                <li><a href="{{ route('customer.login') }}"><i class="bi bi-box-arrow-in-right me-2"></i>Log in</a></li>
                <li><a href="{{ route('customer.register') }}"><i class="bi bi-person-plus me-2"></i>New account</a></li>
            @endauth
        </ul>
    </div>
</div>