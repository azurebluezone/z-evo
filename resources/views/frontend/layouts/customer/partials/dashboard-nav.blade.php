{{--
    Classic dashboard menu -------------------------------------------------
    Same list, order and la-* icons as the demo <aside class="dashboard-menu">.
    Links point at this application's real customer routes.
--}}
<aside class="dashboard-menu">
    <ul>
        <li>
            <a href="{{ route('customer.account') }}" class="{{ request()->routeIs('customer.account') ? 'active' : '' }}">
                <i class="las la-home"></i>Dashboard
            </a>
        </li>
        <li>
            <a href="{{ route('customer.orders') }}" class="{{ request()->routeIs('customer.orders', 'customer.invoice', 'customer.order_note') ? 'active' : '' }}">
                <i class="las la-list"></i>Orders
            </a>
        </li>
        <li>
            <a href="{{ route('customer.profile_edit') }}" class="{{ request()->routeIs('customer.profile_edit') ? 'active' : '' }}">
                <i class="las la-user-alt"></i>Profile
            </a>
        </li>
        <li>
            <a href="{{ route('customer.change_pass') }}" class="{{ request()->routeIs('customer.change_pass') ? 'active' : '' }}">
                <i class="las la-key"></i>Change Password
            </a>
        </li>
        <li>
            <a href="{{ route('customer.notifications.index') }}" class="{{ request()->routeIs('customer.notifications.*') ? 'active' : '' }}">
                <i class="la la-bell"></i>All Notifications
            </a>
        </li>
        <li>
            <a href="{{ route('customer.logout') }}" class="signout-btn"
               onclick="event.preventDefault(); document.getElementById('classic-dashboard-logout').submit();">
                <i class="la la-sign-out"></i>Sign Out
            </a>
        </li>
    </ul>

    <form id="classic-dashboard-logout" action="{{ route('customer.logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</aside>
