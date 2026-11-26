{{--
    FM:SECTION page-customer-forgot-password ------------------------------------
    Classic source : resources/views/frontend/layouts/customer/forgot_password.blade.php
    Controller     : Frontend\CustomerController@forgot_password()  (no variables)
    Handler        : Frontend\CustomerController@forgot_verify()
    Route          : GET  /customer/forgot-password        (customer.forgot.password)
                     POST /customer/forgot-verify          (customer.forgot.verify)
    Sections       : fm-auth-body, fm-auth-foot

    EMAIL OTP ONLY
    forgot_verify() validates `email`, looks the customer up by email and mails
    the one-time code. The SMS path was removed from this flow, so there is no
    phone field here at all.

    forgot_verify() puts the email in the session under 'verify_email';
    forgot_reset() then refuses to render without it. That is why there is no
    account context here and why the card is standalone.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.auth', [
    'fmAuthTitle'   => 'Reset your password',
    'fmAuthSub'     => 'Enter the email address on your account and we will send you a one-time code.',
    'fmAuthEyebrow' => 'Password',
    'fmAuthAside'   => false,
])


@section('fm-auth-body')
    <form action="{{ route('customer.forgot.verify') }}" method="POST" class="fm-au__form" novalidate>
        @csrf

        <div class="fm-au__field">
            <label class="fm-au__label" for="forgotEmail">
                Email address <span class="req">*</span>
            </label>
            <input type="email"
                   id="forgotEmail"
                   name="email"
                   class="fm-au__input @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   placeholder="you@example.com"
                   autocomplete="email"
                   autofocus
                   required>
            <p class="fm-au__hint">The address you used when you registered.</p>
            <p class="fm-au__error">@error('email') {{ $message }} @enderror</p>
        </div>

        <button type="submit" class="fm-au__btn">
            <i class="bi bi-send" aria-hidden="true"></i>
            Send code
        </button>
    </form>
@endsection

@section('fm-auth-foot')
    Remembered it?
    <a href="{{ route('customer.login') }}">Back to sign in</a>
@endsection
