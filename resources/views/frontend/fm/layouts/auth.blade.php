{{--
    FM:SECTION auth-layout -----------------------------------------------------
    Shell for the narrow, centred "one job per page" screens that must work
    without a session:

        frontend/fm/pages/auth/login.blade.php
        frontend/fm/pages/auth/register.blade.php
        frontend/fm/pages/customer/forgot_password.blade.php
        frontend/fm/pages/customer/forgot_reset.blade.php
        frontend/fm/pages/customer/verify.blade.php

    Chain: page  ->  this layout  ->  frontend.fm.layouts

    WHY NOT frontend.fm.layouts.account
    That shell renders the customer rail and reads Auth::guard('customer'). Every
    page using this layout is guest reachable (all of them are in the controller's
    `except` list), so the rail would be empty on exactly the screens that are
    meant to CREATE the session. This shell has no identity concept at all.

    HOW A PAGE USES IT
        @extends('frontend.fm.layouts.auth', [
            'fmAuthTitle'   => 'Welcome back',
            'fmAuthSub'     => 'Sign in to track your orders.',
            'fmAuthEyebrow' => 'Account',
            'fmAuthShowAside' => true,   // optional, default true
        ])
        @section('fm-auth-body')  ...the form...
        @endsection

    WHY THE VALUES TRAVEL THROUGH @extends AND NOT @section
    A parent that writes @section('title', $fmAuthTitle) evaluates that
    expression when the PARENT renders. A child's @section('fm-auth-title', …)
    is a different key, so the classic "child sets a section, parent reads a
    variable" pattern silently always printed the fallback. @extends' data
    argument is the one form where the parent genuinely sees the child's value,
    and it keeps each string written once per page.

    The aside half is decorative and CSS-hidden below 768px
    (fm-auth.css), so a phone sees the card alone instead of scrolling past a
    logo panel to reach the form.
-------------------------------------------------------------------------- --}}
@extends('frontend.fm.layouts')

@php
    $fmAuthTitle    = $fmAuthTitle    ?? 'Account';
    $fmAuthSub      = $fmAuthSub      ?? null;
    $fmAuthEyebrow  = $fmAuthEyebrow  ?? 'Account';
    $fmAuthAside    = $fmAuthAside    ?? true;

    // Aside content is a plain array so a page can override one row without
    // restating the whole panel.
    $fmAuthPoints   = $fmAuthPoints   ?? [];
    $fmAuthShopName = $fmAuthShopName ?? ($generalsetting->name ?? config('app.name'));
    $fmAuthLogo     = (string) ($generalsetting->dark_logo ?: ($generalsetting->white_logo ?? ''));
    $fmAuthLogoUrl  = $fmAuthLogo !== '' ? \App\Support\MediaPath::url($fmAuthLogo, 'settings') : '';
    $fmAuthPhone    = trim((string) ($generalsetting->support_phone ?? ($contact->phone ?? '')));
@endphp

@section('title', $fmAuthTitle)

{{--
    This layout OWNS fm-auth.css, for the same reason
    frontend/fm/layouts/account.blade.php owns fm-account.css: the shell and the
    .fm-au-* primitives are one unit, pushed once here. Pages that extend
    frontend.fm.layouts DIRECTLY (order_track, order_success, invoice …) push
    this sheet themselves, because they skip this layout.
--}}
@push('css')
    <link rel="stylesheet" href="{{ asset('frontend/css/fm-auth.css') }}">
@endpush

@section('content')
    <section class="fm-au">
        <div class="fm-au__container">

            @hasSection('fm-auth-crumbs')
                <ol class="fm-au__crumbs">
                    @yield('fm-auth-crumbs')
                </ol>
            @endif

            <div class="fm-au__panel">
                <div class="fm-au__main">
                    @hasSection('fm-auth-notice')
                        @yield('fm-auth-notice')
                    @endif

                    <p class="fm-au__eyebrow">{{ $fmAuthEyebrow }}</p>
                    <h1 class="fm-au__title">{{ $fmAuthTitle }}</h1>
                    @if($fmAuthSub)
                        <p class="fm-au__sub">{{ $fmAuthSub }}</p>
                    @endif

                    @yield('fm-auth-body')

                    @hasSection('fm-auth-foot')
                        <p class="fm-au__foot">
                            @yield('fm-auth-foot')
                        </p>
                    @endif
                </div>

                @if($fmAuthAside)
                    <aside class="fm-au__aside">
                        @if($fmAuthLogoUrl !== '')
                            <img src="{{ $fmAuthLogoUrl }}" alt="{{ $fmAuthShopName }}" class="fm-au__aside-logo">
                        @endif

                        <h2>{{ $fmAuthAsideTitle ?? $fmAuthShopName }}</h2>

                        @if($fmAuthAsideText ?? null)
                            <p>{{ $fmAuthAsideText }}</p>
                        @endif

                        @if($fmAuthPoints !== [])
                            <ul class="fm-au__aside-list">
                                @foreach($fmAuthPoints as $fmAuthIcon => $fmAuthPoint)
                                    <li>
                                        <i class="bi bi-{{ $fmAuthIcon }}" aria-hidden="true"></i>
                                        <span>{{ $fmAuthPoint }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($fmAuthPhone !== '')
                            <p class="mt-3">
                                <i class="bi bi-headset" aria-hidden="true"></i>
                                Need a hand? Call
                                <a href="tel:{{ preg_replace('/\D/', '', $fmAuthPhone) }}"
                                   class="text-white text-decoration-none fw-semibold">{{ $fmAuthPhone }}</a>
                            </p>
                        @endif
                    </aside>
                @endif
            </div>
        </div>
    </section>
@endsection
