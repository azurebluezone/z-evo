{{--
    FM:PARTIAL header/primary-header
    =========================================================================
    The sticky white bar: brand + mobile menu trigger on the left, the account
    control, the omni-search field and, on wide screens, the Categories pill in
    the middle, and the AI Lens / wishlist / cart cluster on the right. The
    phone search surface is the #fmSearchDrawer offcanvas.

    The bar owns no logic of its own - every moving part is a component:

        partials/header/search-bar       the search field (both variants)
        partials/header/search-category-select
        partials/header/account-dropdown the profile menu, left of the field
        partials/header/quick-actions    AI Lens / wishlist / cart cluster
        sections/ai/*                    the AI Lens, voice and camera triggers

    so the desktop bar and the search drawer can never drift apart: they render
    the SAME search-bar partial.

    Props
        fmShopName      string  shop name, used as the logo alt text
        fmLogo          string  resolved logo URL
        fmWishlistCount int     wishlist badge, server rendered
        fmCartCount     int     cart badge, server rendered
        fmCartTotal     float   cart total, server rendered

    Usage
        @include('frontend.fm.partials.header.primary-header')
--}}

<!-- BEGIN: MainHeader -->
<header class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm" data-purpose="primary-header">
    {{--
        Header layout follows the FreshMart reference: the mobile navigation
        trigger sits LEFT of the logo so it is the first thing a thumb reaches,
        the Categories pill only appears on wide screens, and the search field is
        the flexible middle column that collapses into its own strip below the bar
        on small screens.

        The outer .container is kept instead of the reference's max-w-7xl so the
        logo stays flush with the grid of every page body underneath it; mixing
        two different max-widths at the top of the site would break that alignment.
    --}}
    <div class="container mx-auto px-4">
        <div class="py-2.5 flex items-center justify-between gap-3 lg:gap-4">
            {{-- Brand & Mobile Menu Toggle.
                 fm-header-brand is a styling hook, not a layout class: the <=576px
                 guard in #fm-audit-fix pads EVERY element whose class merely
                 CONTAINS "p-" (gap-2, py-2.5, group-hover: ...), which stacked 12px
                 on each of the four elements between the viewport edge and the
                 hamburger glyph - measured glyph left = 60px at 390px wide. The
                 chain is reset in #fm-audit-fix and fm-sections.css through this
                 class, so the hook must stay. --}}
            <div class="flex items-center gap-2 lg:gap-3 flex-shrink-0 fm-header-brand">
                {{-- The phone twin of the Categories pill: same drawer, and it
                     carries the same animation hook. The pill above is
                     xl-only, so on a phone this is the element that is
                     actually on screen to animate - fm-app.js pulses whichever
                     hook is visible. --}}
                <button aria-label="Open Navigation Drawer" class="lg:hidden p-2.5 rounded-xl text-gray-700 hover:text-[#1b5e20] hover:bg-emerald-50 transition-colors border-0 bg-transparent" data-bs-target="#mobileNavDrawer" data-bs-toggle="offcanvas" data-fm-categories-card type="button">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <a class="flex items-center gap-2 shrink-0 group" href="{{ route('home') }}">
                    <img alt="{{ $fmShopName }}" class="h-9 md:h-10 w-auto object-contain rounded-lg transition-transform group-hover:scale-102" src="{{ $fmLogo }}"/>
                </a>
            </div>

            {{-- Categories Mega Pill Button (Desktop)

                 data-fm-categories-card is the animation hook: pressing this
                 card - or the drawer's "View all categories" link - plays the
                 pulse animation on THIS element (the keyframes live in
                 fm-interaction.css, the trigger in fm-app.js). It is a data
                 attribute rather than a class so the styling stays in the
                 stylesheet and this partial carries no animation logic. --}}
            <div class="hidden xl:block shrink-0">
                <button class="bg-emerald-50 hover:bg-emerald-100 text-[#1b5e20] font-bold text-xs rounded-full px-4 py-2 flex items-center gap-2 border border-emerald-200/70 transition-all shadow-xs"
                        data-bs-target="#mobileNavDrawer" data-bs-toggle="offcanvas"
                        data-fm-categories-card type="button">
                    <i class="bi bi-grid-fill text-sm fm-categories-card__icon"></i>
                    <span>Categories</span>
                    <i class="bi bi-chevron-down text-xs text-emerald-700"></i>
                </button>
            </div>

            {{-- Account, at the head of the search field. It used to sit last in
                 the right-hand cluster; the bar reads better with the profile
                 control immediately left of the search field, and moving it out
                 frees 56px of the cluster that the tablet band needs. Hidden
                 below md exactly like the search column beside it - the drawer
                 carries the same account links at those widths. --}}
            <div class="hidden md:block shrink-0">
                @include('frontend.fm.partials.header.account-dropdown')
            </div>

            {{-- Omni-search bar. Category picker, field and both AI triggers; the AI
                 triggers are components of their own under fm/sections/ai, and the bar is
                 one under fm/partials/header. --}}
            {{-- Inline search appears at lg, not md.
                 At 768-1023px the row already carries the logo, the account
                 control and the cart pill; squeezing the field in between left
                 it 114px wide at 768px (measured), which cuts the placeholder
                 and is unusable for typing. Below lg the search icon in
                 quick-actions opens the same search component in a drawer, so
                 nothing is lost - and the bar keeps 44px tap targets. --}}
            <div class="flex-1 min-w-0 max-w-2xl hidden lg:block relative">
                @include('frontend.fm.partials.header.search-bar')
            </div>

            {{-- Quick actions: AI Lens pill, wishlist, account, cart --}}
            @include('frontend.fm.partials.header.quick-actions', [
                'fmWishlistCount' => $fmWishlistCount ?? 0,
                'fmCartCount'     => $fmCartCount ?? 0,
                'fmCartTotal'     => $fmCartTotal ?? 0,
            ])
        </div>

        {{-- The phone search surface is #fmSearchDrawer, a top offcanvas. It is
             rendered from layouts.blade.php beside the navigation drawer, not
             inside this bar, because a Bootstrap offcanvas must stay OUTSIDE the
             sticky header: the header creates a stacking context (z-index 1030)
             and .offcanvas-backdrop is appended to <body> at z-index 1040, so a
             drawer nested in here would open UNDERNEATH its own backdrop. The
             magnifier in the action cluster opens it. --}}
    </div>
</header>
<!-- END: MainHeader -->