{{--
    FM:SECTION page-customer-forgot-password ------------------------------------
    Classic source : resources/views/frontend/layouts/customer/forgot_password.blade.php
    Controller     : Frontend\CustomerController@forgot_password()  (no variables)
    Handler        : Frontend\CustomerController@forgot_verify()
    Route          : GET  /customer/forgot-password        (customer.forgot.password)
                     POST /customer/forgot-verify          (customer.forgot.verify)
    Sections       : fm-auth-body, fm-auth-foot

    WHY type="tel", NOT type="number"
    The classic used type="number". A numeric input silently discards a leading
    zero, and the lookup below is Customer::where('phone', …) - so "1XXXXXXXXX"
    never matches and the customer is told their number was not found.

    @error('phone') is kept even though forgot_verify() performs no
    $this->validate() call: it is the field the customer can get wrong, it costs
    nothing, and if validation is ever added to the handler this page is already
    correct.

    forgot_verify() sends the OTP and puts the phone in the session under
    'verify_phone'; forgot_reset() then refuses to render without it. That is why
    there is no account context here and why the card is standalone.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.auth', [
    'fmAuthTitle'   => 'Reset your password',
    'fmAuthSub'     => 'Enter the phone number on your account and we will send you a one-time code.',
    'fmAuthEyebrow' => 'Password',
    'fmAuthAside'   => false,
])


@section('fm-auth-body')
    <form action="{{ route('customer.forgot.verify') }}" method="POST" class="fm-au__form" novalidate>
        @csrf

        <div class="fm-au__field">
            <label class="fm-au__label" for="forgotPhone">
                Phone number <span class="req">*</span>
            </label>
            <input type="tel"
                   id="forgotPhone"
                   name="phone"
                   class="fm-au__input @error('phone') is-invalid @enderror"
                   value="{{ old('phone') }}"
                   placeholder="01XXXXXXXXX"
                   inputmode="numeric"
                   autocomplete="tel"
                   autofocus
                   required>
            <p class="fm-au__hint">The number you used when you registered.</p>
            <p class="fm-au__error">@error('phone') {{ $message }} @enderror</p>
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
