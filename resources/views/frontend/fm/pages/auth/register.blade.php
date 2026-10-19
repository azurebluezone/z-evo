{{--
    FM:SECTION page-auth-register -----------------------------------------------
    Classic source : resources/views/frontend/pages/auth/register.blade.php
    Controller     : Frontend\CustomerController@register()  (no variables)
    Handler        : Frontend\CustomerController@store()
    Route          : GET /customer/register  (customer.register)
    Sections       : fm-auth-body, fm-auth-foot, fm-auth-crumbs
    Consumes       : the registration form partial, which owns the field contract.

    The form lives in frontend/fm/partials/auth/register-form.blade.php so this
    page and frontend/fm/pages/customer/register.blade.php cannot drift apart.
    That partial's header documents the contract and the five classic bugs it
    fixes - most visibly that the classic page rendered NO @error() at all, so a
    duplicate phone number or a five-character password produced a silent round
    trip with nothing on screen to explain it.
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


@section('fm-auth-crumbs')
    <li>
        <a href="{{ route('home') }}">
            <i class="bi bi-house-door" aria-hidden="true"></i>
            <span>Home</span>
        </a>
    </li>
    <li aria-hidden="true">/</li>
    <li aria-current="page">Register</li>
@endsection

@section('fm-auth-body')
    @include('frontend.fm.partials.auth.register-form')
@endsection

@section('fm-auth-foot')
    Already have an account?
    <a href="{{ route('customer.login') }}">Sign in</a>
@endsection
