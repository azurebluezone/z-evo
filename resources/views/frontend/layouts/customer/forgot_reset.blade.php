{{--
    Classic customer forgot-password reset ---------------------------------
    Was @extends('frontend.layouts.master') (legacy shell, modern-header, dead
    /frontEnd/* asset paths). Rebuilt on frontend.layouts.app with the shared
    auth-form card so it renders inside the classic header and footer.

    Form contract is unchanged: POST customer.forgot.store with fields otp and
    password, plus the resend endpoint in a second form. The modern template
    renders frontend.fm.pages.customer.forgot_reset instead.
--}}
@extends('frontend.layouts.app')

@section('title', 'Reset Password')

@section('content')
    @php
        // Same logo source as the classic header: the demo asset above is the ViserMart artwork, which does not belong on this storefront.
        $authLogo = preg_replace('~^public/~', '', (string) ($generalsetting->dark_logo ?? 'assets/images/logo_icon/logo_dark.png'));
    @endphp
    <section class="auth-section auth-section--light py-60">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-11 col-md-8 col-lg-6">
                    <div class="auth-form">
                        <div class="auth-form__head text-center">
                            <div class="logo">
                                <a href="{{ url('/') }}">
                                    <img src="{{ asset($authLogo) }}" alt="{{ $generalsetting->name ?? 'logo' }}">
                                </a>
                            </div>
                            <h5 class="mt-3 mb-1">Choose a new password</h5>
                            <p class="sm-text mb-0">Enter the OTP we just sent and your new password.</p>
                        </div>

                        <div class="auth-form__body">
                            <form action="{{ route('customer.forgot.store') }}" method="POST">
                                @csrf

                                <div class="form-group">
                                    <label class="form--label" for="otp">OTP</label>
                                    <input type="text" inputmode="numeric" autocomplete="one-time-code"
                                           id="otp" name="otp" value="{{ old('otp') }}"
                                           class="form--control @error('otp') is-invalid @enderror"
                                           placeholder="Enter OTP" required autofocus>
                                    @error('otp')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form--label" for="password">New password</label>
                                    <input type="password" id="password" name="password"
                                           class="form--control @error('password') is-invalid @enderror"
                                           placeholder="At least 6 characters" required autocomplete="new-password">
                                    @error('password')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <button class="btn btn--base w-100" type="submit" style="min-height:45px;">Reset password</button>
                            </form>

                            <form action="{{ route('customer.forgot.resendotp') }}" method="POST" class="mt-3 text-center">
                                @csrf
                                <button type="submit" class="t-link t-link--base text--base sm-text border-0 bg-transparent">
                                    <i class="las la-redo-alt me-1"></i>Resend OTP
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
