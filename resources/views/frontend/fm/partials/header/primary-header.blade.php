{{--
    FM:PARTIAL header/primary-header
    =========================================================================
    The sticky white bar: brand + mobile menu trigger on the left, the
    omni-search field in the middle and the account / wishlist / cart cluster on
    the right. The phone search surface is the #fmSearchDrawer offcanvas.

    Space budget (measured at 1440px, container 1320px -> 1296px interior):

        brand      180-220px   logo max 180x48
        search     1fr         stretches, never below 320px
        actions    auto        three 44px controls + cart pill (~270px)

    The Categories pill that used to sit between the logo and the search field
    is gone from this bar. It duplicated the "Browse Categories" trigger in the
    navigation strip and the Categories link in the same strip, and it only
    appeared at xl (>=1200px) - i.e. the exact widths where the strip is also
    visible. Removing it gave the search field the width back.

    Below lg the row becomes three zones: hamburger left, logo dead centre,
    actions right. The logo is absolutely centred rather than flex-centred so
    the left and right clusters can keep their own intrinsic widths; measuring
    the free space instead would shift the mark whenever a badge appeared.

    The bar owns no logic of its own - every moving part is a component:

        partials/header/search-bar       the search field (both variants)
        partials/header/search-category-select
        partials/header/quick-actions    account / wishlist / cart cluster
        partials/header/account-dropdown the profile menu, inside that cluster
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
        Header layout: the mobile navigation trigger sits LEFT of the logo so it
        is the first thing a thumb reaches; from lg up the bar is a 3-column grid
        (brand | search | actions); below lg the logo is centred and the search
        field moves into #fmSearchDrawer. The old Categories pill beside the logo
        is gone - the nav strip's "Browse Categories" and "Categories" link are
        the desktop entry points.

        The outer .container is kept instead of the reference's max-w-7xl so the
        logo stays flush with the grid of every page body underneath it; mixing
        two different max-widths at the top of the site would break that alignment.
    --}}
    <div class="container mx-auto px-4">
        {{-- No gap-* utility here on purpose: Bootstrap 5.3 ships
             `.gap-3{gap:1rem!important}`, which outranks any Tailwind gap and
             any plain `column-gap` on this row. The row's gaps are owned by
             fm-sections.css (flex below lg, grid columns from lg). --}}
        <div class="fm-header-row py-2.5 flex items-center justify-between">
            {{-- Brand & Mobile Menu Toggle.
                 fm-header-brand is a styling hook, not a layout class: the <=576px
                 guard in #fm-audit-fix pads EVERY element whose class merely
                 CONTAINS "p-" (gap-2, py-2.5, group-hover: ...), which stacked 12px
                 on each of the four elements between the viewport edge and the
                 hamburger glyph - measured glyph left = 60px at 390px wide. The
                 chain is reset in #fm-audit-fix and fm-sections.css through this
                 class, so the hook must stay. --}}
            <div class="flex items-center gap-2 lg:gap-3 flex-shrink-0 fm-header-brand">
                {{-- The phone entry point to the category/navigation drawer. It
                     carries data-fm-categories-card, the animation hook:
                     fm-app.js pulses whichever hook is visible, and the
                     drawer's "View all categories" link is the other one. The
                     desktop pill that used to be the third trigger is gone from
                     the bar. --}}
                <button aria-label="Open Navigation Drawer" class="lg:hidden p-2.5 rounded-xl text-gray-700 hover:text-[#1b5e20] hover:bg-emerald-50 transition-colors border-0 bg-transparent" data-bs-target="#mobileNavDrawer" data-bs-toggle="offcanvas" data-fm-categories-card type="button">
                    <i class="bi bi-list text-2xl"></i>
                </button>
                <a class="fm-header-logo flex items-center gap-2 shrink-0 group" href="{{ route('home') }}">
                    <img alt="{{ $fmShopName }}" class="h-9 md:h-10 w-auto object-contain rounded-lg transition-transform group-hover:scale-102" src="{{ $fmLogo }}"/>
                </a>
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
            <div class="fm-header-search hidden lg:block relative">
                @include('frontend.fm.partials.header.search-bar')
            </div>

            {{-- Quick actions: account, wishlist, cart. The AI Lens pill is
                 gone from the bar: the search field already carries the camera
                 and mic triggers, and the drawer carries both labelled
                 buttons on phones. --}}
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