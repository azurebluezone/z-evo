{{--
    FM:SECTION page-customer-forgot-reset --------------------------------------
    Classic source : resources/views/frontend/layouts/customer/forgot_reset.blade.php
    Controller     : Frontend\CustomerController@forgot_reset()  (no variables)
    Handlers       : forgot_store()  POST /customer/forgot-password/store
                     forgot_resend() POST /customer/forgot-password/resendotp
    Routes         : GET /customer/forgot-password/reset  (customer.forgot.reset)
    Sections       : fm-auth-body

    THE SESSION CONTRACT
    forgot_reset() refuses to render unless Session::get('verify_phone') is set,
    and forgot_verify() is what sets it. So this page can only ever be reached
    after a successful phone lookup - there is nothing here to load and nothing
    to guard.

    BUG FIXED: THE WRONG ERROR KEY
    The classic wired the OTP input's error block to @error('phone'). The input
    is name="otp" and account_verify/forgot_store validate the OTP, so that
    message could never have appeared - the same copy-paste bug the classic
    verify.blade.php also had. Here the block reads @error('otp').

    The resend form is a SEPARATE <form> because HTML forbids nesting a form
    inside another. The classic had the same two-form structure; it is kept, with
    the nested-in-a-div mistake (the resend form was inside the outer form in some
    edits) avoided.

    forgot_store() has no $this->validate() either, so its failure path is a
    Toastr flash plus back().
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.auth', [
    'fmAuthTitle'   => 'Choose a new password',
    'fmAuthSub'     => 'Enter the code we sent to your phone, then set a new password.',
    'fmAuthEyebrow' => 'Password',
    'fmAuthAside'   => false,
])


@section('fm-auth-body')
    <form action="{{ route('customer.forgot.store') }}" method="POST" class="fm-au__form" novalidate>
        @csrf

        <div class="fm-au__field">
            <label class="fm-au__label" for="resetOtp">
                One-time code <span class="req">*</span>
            </label>
            {{-- type="tel": the OTP is numeric, and a numeric input would strip
                 a leading zero from the code exactly as it would from a phone
                 number. --}}
            <input type="tel"
                   id="resetOtp"
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

        <div class="fm-au__field">
            <label class="fm-au__label" for="resetPassword">
                New password <span class="req">*</span>
            </label>
            <input type="password"
                   id="resetPassword"
                   name="password"
                   class="fm-au__input @error('password') is-invalid @enderror"
                   placeholder="At least 6 characters"
                   autocomplete="new-password"
                   required>
            <p class="fm-au__hint">At least 6 characters.</p>
            <p class="fm-au__error">@error('password') {{ $message }} @enderror</p>
        </div>

        <button type="submit" class="fm-au__btn">
            <i class="bi bi-check2-circle" aria-hidden="true"></i>
            Set new password
        </button>
    </form>

    {{-- Separate form: HTML does not allow a <form> nested in another <form>. --}}
    <div class="mt-3 text-center">
        <form action="{{ route('customer.forgot.resendotp') }}" method="POST">
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
