{{--
    FM:PARTIAL header/nav-links
    =========================================================================
    The site destinations, rendered twice: as the horizontal strip under the
    header on desktop and as the link block inside the mobile drawer.

    They are ONE partial on purpose. The drawer and the strip are two entry
    points to the same places, and they used to be two hand-maintained copies of
    the same six <li>s - adding a destination meant editing both, and forgetting
    one left a phone visitor with no way to reach it. Only the <ul> wrapper class
    differs between the variants.

    Props
        variant  string  'strip' (default, desktop) or 'drawer' (mobile)

    Usage
        @include('frontend.fm.partials.header.nav-links')
        @include('frontend.fm.partials.header.nav-links', ['variant' => 'drawer'])
--}}

@php
    $fmNavVariant = $variant ?? 'strip';

    /*
     * The destination list.
     *
     *   route     route name the link points at
     *   also      extra route names that must also light this link up
     *   label     text
     *
     * One weight, one colour, one size for every label. "Special offers" used
     * to carry a red fill and a lightning bolt while its neighbours were grey
     * 14px text, so the strip had two different type sizes and the item read as
     * a separate component even though it is just a destination. Emphasis is
     * now carried by the active state only (green + tinted pill), which is the
     * same treatment every other link gets.
     *
     * "Shop Catalog" matches the three routes that all show catalog results:
     * the paginated grid, a single category and a single brand. The drawer's
     * old copy only matched products.page, so /category/{slug} highlighted
     * nothing in the drawer while it highlighted in the strip - the two entry
     * points disagreed about which page you were on. One rule now serves both.
     */
    $fmNavItems = [
        ['route' => 'home', 'label' => 'Home'],
        ['route' => 'products.page', 'also' => ['category', 'products.brand'], 'label' => 'Shop Catalog'],
        ['route' => 'categories.page', 'label' => 'Categories'],
        ['route' => 'offers.index', 'label' => 'Special offers'],
        ['route' => 'hotdeals', 'label' => 'Hot deals'],
        ['route' => 'contact', 'label' => 'Contact'],
    ];

    $fmNavListClass = $fmNavVariant === 'drawer'
        ? 'fm-drawer-links list-unstyled m-0'
        : 'app-nav-menu';
@endphp

<ul class="{{ $fmNavListClass }}">
    @foreach ($fmNavItems as $fmNavItem)
        @php
            $fmNavRoutes  = array_merge([$fmNavItem['route']], $fmNavItem['also'] ?? []);
            $fmNavActive  = request()->routeIs(...$fmNavRoutes) ? 'active' : '';
            $fmNavClass   = trim(($fmNavItem['class'] ?? '') . ' ' . $fmNavActive);
        @endphp
        <li>
            <a class="{{ $fmNavClass }}" href="{{ route($fmNavItem['route']) }}">
                {{ $fmNavItem['label'] }}
            </a>
        </li>
    @endforeach
</ul>