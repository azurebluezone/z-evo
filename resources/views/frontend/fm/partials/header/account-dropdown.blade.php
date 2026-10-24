{{--
    FM:PARTIAL header/account-dropdown
    =========================================================================
    The profile menu. It used to be the fifth control of the right-hand action
    cluster; it now sits immediately LEFT of the search field, which is where
    the reference header puts the profile control.

    The `.fm-header-user` class is load-bearing and must stay:

        fm-sections.css      hides it below 576px and pins the 44x44 box and
                             the 20px glyph of its trigger button
        layouts.blade.php    the navigation drawer carries the same destinations
                             at those widths, so nothing is lost

    It is wrapped by the caller in `hidden md:block` so it appears and
    disappears with the search column it belongs to.

    Usage
        @include('frontend.fm.partials.header.account-dropdown')
--}}

<div class="dropdown fm-header-user">
    <button aria-label="Account" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] text-gray-700 hover:text-[#1b5e20] hover:bg-emerald-50 rounded-full transition-colors border-0 bg-transparent" data-bs-toggle="dropdown" title="Account" type="button">
        <i class="bi bi-person text-2xl"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-md border-0 rounded-xl p-2 text-sm">
        @auth('customer')
            <li><h6 class="dropdown-header text-emerald-900 font-bold">Welcome, {{ auth('customer')->user()->name }}</h6></li>
            <li><a class="dropdown-item rounded-lg" href="{{ route('customer.account') }}"><i class="bi bi-speedometer2 me-2"></i>Account</a></li>
            <li><a class="dropdown-item rounded-lg" href="{{ route('customer.orders') }}"><i class="bi bi-box-seam me-2"></i>My orders</a></li>
            <li><a class="dropdown-item rounded-lg" href="{{ route('wishlist') }}"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger rounded-lg"><i class="bi bi-box-arrow-right me-2"></i>Log out</button>
                </form>
            </li>
        @else
            <li><h6 class="dropdown-header text-emerald-900 font-bold">Guest user</h6></li>
            <li><a class="dropdown-item rounded-lg" href="{{ route('customer.login') }}"><i class="bi bi-box-arrow-in-right me-2"></i>Log in</a></li>
            <li><a class="dropdown-item rounded-lg" href="{{ route('customer.register') }}"><i class="bi bi-person-plus me-2"></i>New account</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item rounded-lg" href="{{ route('track.order') }}"><i class="bi bi-geo-alt me-2"></i>Track order</a></li>
        @endauth
    </ul>
</div>
