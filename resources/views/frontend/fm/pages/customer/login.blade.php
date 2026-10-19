{{--
    FM:SECTION page-customer-login (legacy path alias) -------------------------
    Classic source : resources/views/frontend/layouts/customer/login.blade.php
    Controller     : none. No controller returns this view name today.

    WHY THIS FILE EXISTS
    App\Support\FrontendTheme::modernViewFor() maps BOTH
        frontend.pages.auth.login        -> frontend.fm.pages.auth.login
        frontend.layouts.customer.login  -> frontend.fm.pages.customer.login
    and FrontendTheme::coverage() - the admin screen's conversion report - lists
    every file under resources/views/frontend/layouts/customer. Without a modern
    file here, that report would keep listing this page as CLASSIC even after
    the real sign-in screen had been converted, which is exactly the kind of
    false negative that makes a migration report untrustworthy.

    So this page exists to be the modern counterpart of a legacy view name. It
    renders the identical screen as frontend/fm/pages/auth/login.blade.php and
    shares its form partial, because two hand-written copies of a sign-in form is
    how the two paths would drift.

    If a future route ever starts returning frontend.layouts.customer.login, it
    will land here and get the modern page - which is the correct outcome.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.auth', [
    'fmAuthTitle'      => 'Welcome back',
    'fmAuthSub'        => 'Sign in with the phone number you registered with.',
    'fmAuthEyebrow'    => 'Sign in',
    'fmAuthAsideTitle' => 'Your account, your orders',
    'fmAuthAsideText'  => 'One place to see every order, download an invoice, and update your delivery details.',
    'fmAuthPoints'     => [
        'box-seam'     => 'Every order in one list',
        'receipt'      => 'Invoices you can print or save',
        'truck'        => 'Courier status for each shipment',
        'person-badge' => 'Delivery details saved once',
    ],
])


@section('fm-auth-body')
    @include('frontend.fm.partials.auth.login-form')
@endsection

@section('fm-auth-foot')
    New here?
    <a href="{{ route('customer.register') }}">Create an account</a>
@endsection
