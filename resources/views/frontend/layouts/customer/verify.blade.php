{{--
    Classic customer verify ------------------------------------------------
    Was @extends('frontend.layouts.master'), which is the legacy shell with its
    own "modern-header" and /frontEnd/* asset paths (that directory does not
    exist under public/, so its stylesheets 404). The result was a classic
    template page that rendered outside the classic header/footer.

    Rebuilt on frontend.layouts.app - the same shell frontend.pages.auth.login
    uses - and the auth-form classes from assets/templates/basic/css/main.css,
    so the page keeps one look with the rest of the classic storefront.

    The modern template never reaches this file: FrontendTheme::modernViewFor()
    maps this view name to frontend.fm.pages.customer.verify.

    Form contract is unchanged: POST customer.account.verify, field otp, plus
    the resend endpoint in a second form.
--}}
@extends('frontend.layouts.app')

@section('title', 'Customer Verify')

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
                            <h5 class="mt-3 mb-1">Verify your account</h5>
                            <p class="sm-text mb-0">Enter the OTP sent to your phone to finish signing in.</p>
                        </div>

                        <div class="auth-form__body">
                            <form action="{{ route('customer.account.verify') }}" method="POST">
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

                                <button class="btn btn--base w-100" type="submit" style="min-height:45px;">Verify</button>
                            </form>

                            <form action="{{ route('customer.resendotp') }}" method="POST" class="mt-3 text-center">
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
