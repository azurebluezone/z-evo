{{--
    FM:SECTION page-auth-login --------------------------------------------------
    Classic source : resources/views/frontend/pages/auth/login.blade.php
    Controller     : Frontend\CustomerController@login()   (no variables)
    Handler        : Frontend\CustomerController@signin()
    Route          : GET /customer/login  (customer.login)
    Sections       : fm-auth-body, fm-auth-foot, fm-auth-crumbs
    Consumes       : the sign-in form partial, which owns the field contract.

    The form itself lives in frontend/fm/partials/auth/login-form.blade.php so
    this page and frontend/fm/pages/customer/login.blade.php cannot drift apart.
    That partial's header documents the contract and the four classic bugs it
    fixes; the most important are the reCAPTCHA class that made every submit
    throw a TypeError, and the "forgot password" link that pointed at Laravel's
    default-web-guard reset form instead of the customer guard's own.

    KNOWN INHERIT LIMITATION, REPORTED NOT PAPERED OVER
    signin() calls Auth::guard('customer')->attempt([…]) without passing
    $request->remember, so the "Remember me" checkbox has no effect. Adding it is
    a one-line controller change that starts writing a long-lived cookie - a real
    security decision, so it is not made silently inside a template migration.

    Also: signin() has no $this->validate(), so Laravel can never populate the
    error bag here and @error() would always be empty. The failure path is a
    Toastr flash plus redirect()->back().
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


@section('fm-auth-crumbs')
    <li>
        <a href="{{ route('home') }}">
            <i class="bi bi-house-door" aria-hidden="true"></i>
            <span>Home</span>
        </a>
    </li>
    <li aria-hidden="true">/</li>
    <li aria-current="page">Login</li>
@endsection

@section('fm-auth-body')
    @include('frontend.fm.partials.auth.login-form')
@endsection

@section('fm-auth-foot')
    New here?
    <a href="{{ route('customer.register') }}">Create an account</a>
@endsection
