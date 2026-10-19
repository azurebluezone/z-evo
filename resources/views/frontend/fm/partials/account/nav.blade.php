{{--
    FM:SECTION account-nav ------------------------------------------------------
    Modern replacement for resources/views/frontend/layouts/customer/sidebar.blade.php.

    Design: hi/account.html, left rail. Brand tokens come from fm-sections.css /
    index.css; the rules that are specific to this rail live in fm-account.css
    behind the .fm-acct-nav- prefix, so this partial cannot restyle the header,
    footer or product cards.

    Contract note - the classic sidebar read the customer straight off the guard
    and rendered <img src="{{ asset(Auth::...->image) }}">. That blows up with
    "Undefined variable" style noise when image is null and, worse, it is the
    one place that decides whether a page renders at all. Here the customer is
    resolved once, defensively, and every field has a fallback. MediaPath is
    used instead of asset() so a stored absolute URL or a storage-relative path
    both resolve, which is what the rest of the modern set already does.

    $fmAcctCustomer - optional pre-resolved Customer model. The pages pass the
    one they already loaded (account.blade.php loads it with cust_area eager
    loaded) so this partial does not re-query. Falls back to the guard.
-------------------------------------------------------------------------- --}}
@php
    $fmAcctCustomer = $fmAcctCustomer ?? null;

    if ($fmAcctCustomer === null) {
        $fmAcctUser = Auth::guard('customer')->user();
        $fmAcctCustomer = $fmAcctUser;
    }

    $fmAcctName  = (string) ($fmAcctCustomer->name ?? 'My Account');
    $fmAcctMail  = (string) ($fmAcctCustomer->email ?? '');
    $fmAcctPhone = (string) ($fmAcctCustomer->phone ?? '');

    // Fall back to an initials avatar rather than a broken <img>: the classic
    // sidebar printed an empty src attribute, which browsers turn into a
    // re-request of the current page.
    $fmAcctInitial = mb_substr(trim($fmAcctName) !== '' ? mb_strtoupper(trim($fmAcctName)) : 'M', 0, 1);
    $fmAcctImage   = (string) ($fmAcctCustomer->image ?? '');
    $fmAcctImgUrl  = $fmAcctImage !== ''
        ? \App\Support\MediaPath::url($fmAcctImage, 'settings')
        : '';

    // One active-state decision for the whole rail. request()->is() matches the
    // same URI patterns the classic sidebar used, so a page does not lose its
    // highlight just because it was rewritten.
    $fmAcctPath = '/' . ltrim(request()->path(), '/');

    $fmAcctLinks = [
        ['route' => 'customer.account',              'path' => 'customer/account',       'icon' => 'bi-person',            'label' => 'My Account'],
        ['route' => 'customer.orders',               'path' => 'customer/orders',        'icon' => 'bi-bag-check',         'label' => 'My Orders'],
        ['route' => 'customer.notifications.index',  'path' => 'customer/notifications', 'icon' => 'bi-bell',              'label' => 'Notifications'],
        ['route' => 'customer.profile_edit',         'path' => 'customer/profile-edit',  'icon' => 'bi-pencil-square',     'label' => 'Profile Edit'],
        ['route' => 'customer.change_pass',          'path' => 'customer/change-password','icon' => 'bi-shield-lock',      'label' => 'Change Password'],
        ['route' => 'customer.order_track',          'path' => 'customer/order-track',   'icon' => 'bi-truck',             'label' => 'Track Order'],
    ];
@endphp

<!-- FM:SECTION account-identity -->
<aside class="fm-acct-nav" aria-label="Account">
    <div class="fm-acct-nav__id">
        @if($fmAcctImgUrl !== '')
            <img class="fm-acct-nav__avatar" src="{{ $fmAcctImgUrl }}" alt="" width="56" height="56">
        @else
            <span class="fm-acct-nav__avatar fm-acct-nav__avatar--initial" aria-hidden="true">{{ $fmAcctInitial }}</span>
        @endif
        <div class="fm-acct-nav__who">
            <p class="fm-acct-nav__hello">Hello</p>
            <p class="fm-acct-nav__name">{{ $fmAcctName }}</p>
            @if($fmAcctPhone !== '')
                <p class="fm-acct-nav__meta"><i class="bi bi-telephone" aria-hidden="true"></i> {{ $fmAcctPhone }}</p>
            @endif
        </div>
    </div>

    <nav class="fm-acct-nav__menu">
        <ul>
            @foreach($fmAcctLinks as $fmAcctLink)
                @php
                    $fmAcctActive = $fmAcctPath === '/' . $fmAcctLink['path'];
                    $fmAcctHref  = route($fmAcctLink['route']);
                @endphp
                <li>
                    <a href="{{ $fmAcctHref }}"
                       class="fm-acct-nav__link{{ $fmAcctActive ? ' is-active' : '' }}"
                       @if($fmAcctActive) aria-current="page" @endif>
                        <i class="bi {{ $fmAcctLink['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $fmAcctLink['label'] }}</span>
                    </a>
                </li>
            @endforeach

            <li class="fm-acct-nav__divider" aria-hidden="true"></li>
            <li>
                <a href="{{ route('customer.logout') }}"
                   class="fm-acct-nav__link fm-acct-nav__link--danger"
                   onclick="event.preventDefault(); document.getElementById('fm-acct-logout').submit();">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <form id="fm-acct-logout" action="{{ route('customer.logout') }}" method="POST" hidden>
        @csrf
    </form>
</aside>
<!-- /FM:SECTION account-identity -->
