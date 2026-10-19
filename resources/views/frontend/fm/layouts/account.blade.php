{{--
    FM:SECTION account-layout ---------------------------------------------------
    Shell for every signed-in customer page.

    Chain: frontend.fm.pages.customer.*  ->  this layout  ->  frontend.fm.layouts

    Why a second layout at all: eleven of the customer views used to open the
    same classic grid by hand (col-sm-3 rail + col-sm-9 panel) and eleven
    copies drifted apart. This file is the single definition of that shell, so
    a page supplies only its own panel body.

    HOW A PAGE USES IT
        @extends('frontend.fm.layouts.account', [
            'fmAcctTitle'   => 'My Orders',   // <title> and the panel heading
            'fmAcctSub'     => 'optional',    // line under the heading, or null
            'fmAcctEyebrow' => 'Orders',      // small label above the heading
        ])
        @section('fm-acct-body')   ...required...
        @section('fm-acct-actions') ...optional footer strip...

    The three values are passed through @extends' data argument rather than as
    Blade sections on purpose. A parent layout that writes
    @section('title', $fmAcctTitle) evaluates that expression when the PARENT
    renders, and a child @section('fm-acct-title', ...) is a different key - so
    the classic pattern silently always printed the fallback title. Sharing the
    variables through @extends is the one form where the parent genuinely sees
    the child's value, and it keeps the heading string written once per page.

    Everything the classic layout did is still done, in the same order:
        - The modern chrome (announcement bar, header, nav, footer, drawers,
          toast host, back-to-top) comes from frontend.fm.layouts, so no page
          under this layout has to re-declare it.
        - The rail is the modern nav partial. It reads the customer off the
          customer guard exactly like the classic sidebar did.
        - ViewContent / dataLayer keep working because
          frontend.fm.layouts still includes frontend.partials.event-layer-head.

    $fmAcctCustomer - optional Customer model passed down to the nav so a page
    that already eager-loaded it (account) does not cause a second query.
-------------------------------------------------------------------------- --}}
@extends('frontend.fm.layouts')

@php
    $fmAcctTitle   = $fmAcctTitle   ?? 'My Account';
    $fmAcctSub     = $fmAcctSub     ?? null;
    $fmAcctEyebrow = $fmAcctEyebrow ?? 'Account';
@endphp

@section('title', $fmAcctTitle)

{{--
    This layout OWNS fm-account.css.

    The shell and every primitive the pages under it use (.fm-acct-*) live in
    one sheet, so the layout pushes it once instead of every page remembering
    to. A page that needs extra styles pushes them on top through the normal
    @push('css') stack; pages under this layout must NOT re-push
    fm-account.css or the browser would fetch it twice.
--}}
@push('css')
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-account.css') }}">
@endpush

@section('content')
    <section class="fm-acct" aria-labelledby="fm-acct-title">
        <div class="fm-acct__container">

            <div class="fm-acct__main">
                {{-- The rail is rendered once. Its CSS swaps between a left
                     column (>= 992px) and a horizontal scroller below that, so
                     there is no second copy of the markup to keep in sync and
                     no duplicate form id for the logout submit button. --}}
                <div class="fm-acct__rail">
                    @include('frontend.fm.partials.account.nav', [
                        'fmAcctCustomer' => $fmAcctCustomer ?? null,
                    ])
                </div>

                <div class="fm-acct__panel fm-card">
                    <header class="fm-acct__head">
                        <p class="fm-acct__eyebrow">{{ $fmAcctEyebrow }}</p>
                        <h1 class="fm-acct__title" id="fm-acct-title">{{ $fmAcctTitle }}</h1>
                        @if($fmAcctSub)
                            <p class="fm-acct__sub">{{ $fmAcctSub }}</p>
                        @endif
                    </header>

                    <div class="fm-acct__body">
                        @yield('fm-acct-body')
                    </div>

                    @hasSection('fm-acct-actions')
                        <footer class="fm-acct__actions">
                            @yield('fm-acct-actions')
                        </footer>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
