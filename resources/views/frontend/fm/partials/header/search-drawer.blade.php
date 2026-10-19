{{--
    FM:PARTIAL header/search-drawer
    =========================================================================
    The phone search surface: a top offcanvas that carries the phone twin of the
    desktop omni-search bar. Tapping the magnifier in the action cluster
    (quick-actions.blade.php) opens it.

    Why a drawer and not a strip inside the bar: the bar has no room for the
    field below md, and the desktop field it replaces is the WHOLE component -
    category picker, text field, live-suggest panel and both AI triggers. The
    drawer renders that same partial in its desktop variant, so the phone gets
    every element the computer does, from one source of markup:

        search-category-select   "All categories" picker
        the text field           name="q", 44px, data-fm-search-input
        the two AI triggers      voice + camera
        the submit button        real GET submit to route('search')
        the suggest panel        data-fm-search-suggest, paired by data-fm-for

    It is included from layouts.blade.php, NOT from primary-header.blade.php:
    the header is position:sticky with z-index 1030, i.e. its own stacking
    context, while .offcanvas-backdrop is appended to <body> at z-index 1040.
    A drawer nested inside the header would therefore open underneath its own
    backdrop and be unusable.

    Props
        (none - the input id is fixed so the suggest pairing has one name)

    Usage
        @include('frontend.fm.partials.header.search-drawer')
--}}

<div aria-labelledby="fmSearchDrawerLabel" class="offcanvas offcanvas-top" id="fmSearchDrawer" tabindex="-1">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title text-[#1b5e20] font-bold" id="fmSearchDrawerLabel">
            <i class="bi bi-search me-2"></i>Search products
        </h5>
        <button aria-label="Close" class="btn-close" data-bs-dismiss="offcanvas" type="button"></button>
    </div>
    <div class="offcanvas-body">
        {{-- Desktop variant on purpose: it is the one that renders the category
             picker and the suggest panel. The AI triggers inside it are hidden
             on the bar below 1024px by fm-ai.css; #fmSearchDrawer restores them
             in that file, because this drawer is exactly the surface that is
             supposed to carry them at those widths. --}}
        @include('frontend.fm.partials.header.search-bar', [
            'id'          => 'fmDrawerSearchInput',
            'placeholder' => 'Search products by name or code',
        ])
    </div>
</div>
