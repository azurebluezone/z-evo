{{--
    FM:SECTION page-customer-verify --------------------------------------------
    Classic source : resources/views/frontend/layouts/customer/verify.blade.php
    Controller     : Frontend\CustomerController@verify()   (no variables)
    Handlers       : account_verify() POST /customer/verify-account
                     resendotp()      POST /customer/resend-otp
    Route          : GET /customer/verify  (customer.verify)
    Sections       : fm-auth-body

    BUG FIXED: THE WRONG ERROR KEY
    The classic bound the OTP input's error block to @error('phone'), but the
    input is name="otp" and account_verify() validates exactly 'otp' =>
    'required'. The one error this screen could realistically produce could
    therefore never be displayed. Here the block reads @error('otp').

    type="tel" again, deliberately: type="number" strips a leading zero from the
    OTP.

    A LAYER ABOVE THIS PAGE MAY BE UNREACHABLE - REPORTED, NOT CHANGED
    verify() renders this page, but nothing in the codebase ever puts
    Session::put('verify_phone', …) on the registration path: store() sets
    $store->verify = 1 directly, so a new account is born verified. Both
    resendotp() and account_verify() resolve the customer through
    Customer::where('phone', session::get('verify_phone')) and then call
    ->verify = … on the result, which fatals if that lookup returned null.
    So this page is reachable only by hand-crafting a session, and its handler
    is unsafe when the session key is absent.

    That is a controller defect with security weight (an unguarded null
    dereference on an unauthenticated POST). It is left alone here because
    fixing it changes auth behaviour, not presentation, and is called out in the
    handover notes for an explicit decision.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.auth', [
    'fmAuthTitle'   => 'Verify your account',
    'fmAuthSub'     => 'Enter the one-time code we sent to your phone.',
    'fmAuthEyebrow' => 'Verification',
    'fmAuthAside'   => false,
])


@section('fm-auth-body')
    <form action="{{ route('customer.account.verify') }}" method="POST" class="fm-au__form" novalidate>
        @csrf

        <div class="fm-au__field">
            <label class="fm-au__label" for="verifyOtp">
                One-time code <span class="req">*</span>
            </label>
            <input type="tel"
                   id="verifyOtp"
                   name="otp"
                   class="fm-au__input @error('otp') is-invalid @enderror"
                   value="{{ old('otp') }}"
                   placeholder="Enter the code"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   autofocus
                   required>
            {{-- @error('otp'), not @error('phone') - see the header note. --}}
            <p class="fm-au__error">@error('otp') {{ $message }} @enderror</p>
        </div>

        <button type="submit" class="fm-au__btn">
            <i class="bi bi-patch-check" aria-hidden="true"></i>
            Verify account
        </button>
    </form>

    {{-- Separate form: a <form> cannot be nested inside another <form>. --}}
    <div class="mt-3 text-center">
        <form action="{{ route('customer.resendotp') }}" method="POST">
            @csrf
            <button type="submit" class="fm-au__btn fm-au__btn--link">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                Resend code
            </button>
        </form>
    </div>
@endsection

@section('fm-auth-foot')
    <a href="{{ route('customer.login') }}">Back to sign in</a>
@endsection
