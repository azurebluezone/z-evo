{{--
    FM:PARTIAL header/quick-actions
    =========================================================================
    The right-hand action cluster: the phone search trigger, the AI Lens pill,
    wishlist and the cart trigger.

    Every control here is load-bearing, so the ids and data hooks are kept
    exactly as the bar had them - fm-app.js repaints cartCountBadge and
    cartTotalHeader in place after an add-to-cart, and a rename would leave the
    badge frozen at whatever the server first sent:

        #cartCountBadge / data-fm-cart-count
        #cartTotalHeader / data-fm-cart-total
        #wishlistCount / data-fm-wishlist-count

    fm-header-wishlist and fm-header-cart-total are the classes the mobile rules
    in fm-sections.css hide, because the controls do not fit a 375px bar;
    wishlist is duplicated in the navigation drawer. The account control used to
    live here too - it now sits immediately left of the search field, in
    partials/header/account-dropdown.blade.php.

    Usage
        @include('frontend.fm.partials.header.quick-actions')
--}}

<div class="flex items-center space-x-2 sm:space-x-3 shrink-0">

    {{-- Search trigger for phones: opens the search drawer, which carries the
         whole desktop search component (category picker, field, live suggest,
         both AI triggers, submit). It is an offcanvas, not a collapse, so it
         also carries a backdrop and can be dismissed by tapping outside it.
         The fm-search-open hook is what fm-sections.css sizes at 44x44; the
         CSS must not target the drawer id, because that id moved once already
         and the tap target silently dropped to 28px when it did. --}}
    <button aria-controls="fmSearchDrawer" aria-expanded="false" aria-label="Open search"
            class="md:hidden fm-search-open inline-flex items-center justify-center min-h-[44px] min-w-[44px] p-2 text-gray-700 hover:text-[#1b5e20] hover:bg-emerald-50 rounded-full transition-colors border-0 bg-transparent"
            data-bs-target="#fmSearchDrawer" data-bs-toggle="offcanvas" type="button">
        <i class="bi bi-search text-lg"></i>
    </button>

    {{-- AI Lens pill: its own component, opens the same drawer as the two
         inline triggers through the mode prop. --}}
    @include('frontend.fm.sections.ai.ai-lens-toggle')

    {{-- Wishlist --}}
    <a class="fm-header-wishlist relative flex items-center justify-center min-h-[44px] min-w-[44px] p-2 text-gray-700 hover:text-[#1b5e20] hover:bg-emerald-50 rounded-full transition-colors" href="{{ route('wishlist') }}" title="Wishlist">
        <i class="bi bi-heart text-xl"></i>
        <span class="absolute top-1 right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full" data-fm-wishlist-count id="wishlistCount">{{ $fmWishlistCount }}</span>
    </a>

    {{-- User account moved out of the cluster: it now sits immediately left of
         the search field, in partials/header/account-dropdown.blade.php. --}}

    {{-- Cart --}}
    <button aria-label="Open cart" class="fm-header-cart bg-[#1b5e20] hover:bg-[#144618] text-white rounded-full px-3.5 py-1.5 sm:px-4 sm:py-2 min-h-[44px] flex items-center shadow-sm transition-all active:scale-95 border-0" data-bs-target="#cartDrawer" data-bs-toggle="offcanvas" type="button">
        <div class="relative mr-2 flex items-center">
            <i class="bi bi-basket2-fill text-base sm:text-lg"></i>
            <span class="absolute -top-2 -right-2 bg-amber-400 text-slate-900 font-extrabold text-xs min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center shadow-xs" data-fm-cart-count id="cartCountBadge">{{ $fmCartCount }}</span>
        </div>
        <span class="fm-header-cart-total text-xs font-bold text-white tracking-wide" data-fm-cart-total id="cartTotalHeader">Tk {{ number_format($fmCartTotal) }}</span>
    </button>
</div>