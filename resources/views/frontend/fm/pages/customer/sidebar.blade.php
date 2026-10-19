{{--
    FM:SECTION compat-customer-sidebar -----------------------------------------
    WHY THIS FILE EXISTS AT ALL
    A classic view still containing
        @include('frontend.layouts.customer.sidebar')
    does not reach the classic sidebar while the modern theme is active. Blade's
    @include goes through the view finder, and App\Support\ThemeViewFinder hands
    every lookup to App\Support\FrontendTheme::resolve(), which maps

        frontend.layouts.customer.<x>  ->  frontend.fm.pages.customer.<x>

    So that include resolves HERE. That is what lets a classic page keep asking
    for the old sidebar path and still be handed the modern rail - which is the
    whole point of the convention-based mapping in FrontendTheme::modernViewFor():
    a half-finished migration degrades to "old layout", never to a 500.

    Consequently this file MUST emit the modern rail. A copy of the classic
    .customer-sidebar markup here would render classic CSS classes inside a
    modern page.

    It is a partial, not a page: it has no @extends and no @section, because it
    is included rather than extended.

    The modern pages under frontend/fm/pages/customer/ do NOT use this file -
    they are rendered by frontend/fm.layouts.account, which includes
    frontend.fm.partials.account.nav directly, so the rail markup exists in one
    place per page. This shim exists purely for classic includers.
-------------------------------------------------------------------------- --}}

@include('frontend.fm.partials.account.nav', ['fmAcctCustomer' => $fmAcctCustomer ?? null])
