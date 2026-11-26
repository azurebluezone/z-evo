{{--
    FM:PARTIAL header/notification-bell
    =========================================================================
    The customer's notification entry in the header action cluster.

    Classic parity: frontend/layouts/partials/header.blade.php renders the same
    bell behind Auth::guard('customer')->check() and prints the unread count in
    .ecommerce__is (plus the bottom bar in layouts/app.blade.php repeats it on
    phones). The modern header shipped without it, so a signed-in customer lost
    the only control that says "there is something new", while the notifications
    page itself existed and was linked from nowhere in the chrome.

    Props
        $fmUnreadNotifications  int  server-resolved unread count, 0 by default

    The count is resolved once in frontend/fm/layouts.blade.php so this partial
    never runs a query of its own - it is included from the header on every
    modern page, and a per-include query would be a second one on top of the
    one the layouts file already pays for.

    The badge span is always rendered (hidden at 0) and carries
    data-fm-notification-count, matching the wishlist/cart badges' contract, so
    a future AJAX mark-read can repaint it without a page reload.
--}}
@auth('customer')
    @php
        $fmBellCount = (int) ($fmUnreadNotifications ?? 0);
    @endphp
    <a class="fm-header-bell relative flex items-center justify-center min-h-[44px] min-w-[44px] text-gray-700 hover:text-[#1b5e20] hover:bg-emerald-50 rounded-full transition-colors"
       href="{{ route('customer.notifications.index') }}"
       title="Notifications"
       aria-label="Notifications{{ $fmBellCount > 0 ? ', ' . $fmBellCount . ' unread' : '' }}"
       data-fm-notification-bell>
        <i class="bi bi-bell"></i>
        <span class="absolute top-1 right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full{{ $fmBellCount > 0 ? '' : ' hidden' }}"
              data-fm-notification-count
              id="fmNotificationCount">{{ $fmBellCount }}</span>
    </a>
@endauth
