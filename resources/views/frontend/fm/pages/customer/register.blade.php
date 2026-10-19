{{--
    FM:SECTION page-customer-register (legacy path alias) ---------------------
    Classic source : resources/views/frontend/layouts/customer/register.blade.php
    Controller     : none. No controller returns this view name today.

    WHY THIS FILE EXISTS
    Same reason as frontend/fm/pages/customer/login.blade.php: the theme maps
    frontend.layouts.customer.register -> frontend.fm.pages.customer.register,
    and FrontendTheme::coverage() lists it. Without a modern file it would be
    reported as CLASSIC forever, which makes the conversion report lie.

    It shares its form partial with frontend/fm/pages/auth/register.blade.php so
    the two paths cannot drift.

    FIELDS SHOWN VS FIELDS ENFORCED
    The form renders name / phone / email / password / password_confirmation /
    agree. CustomerController@store() validates only name, phone and password -
    email is written unvalidated into a NOT NULL column, and
    password_confirmation has no 'confirmed' rule behind it. Both are recorded
    in the form partial's header as controller gaps rather than being silently
    worked around.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.auth', [
    'fmAuthTitle'      => 'Create your account',
    'fmAuthSub'        => 'One account for orders, invoices and delivery updates.',
    'fmAuthEyebrow'    => 'Register',
    'fmAuthAsideTitle' => 'What you get',
    'fmAuthAsideText'  => 'It takes a minute, and it makes checkout faster next time.',
    'fmAuthPoints'     => [
        'bag-check' => 'Checkout in a few taps',
        'receipt'   => 'Invoices and order history',
        'truck'     => 'Courier tracking per order',
        'bell'      => 'Order status notifications',
    ],
])


@section('fm-auth-body')
    @include('frontend.fm.partials.auth.register-form')
@endsection

@section('fm-auth-foot')
    Already have an account?
    <a href="{{ route('customer.login') }}">Sign in</a>
@endsection
