{{--
    Classic customer forgot-password ---------------------------------------
    Was @extends('frontend.layouts.master') - the legacy shell, whose header is
    marked "modern-header" and whose /frontEnd/* assets do not exist in public/.
    That put a classic-template page outside the classic header and footer.

    Now on frontend.layouts.app with the shared auth-form card, matching
    frontend.pages.auth.login. Form contract is unchanged: POST
    customer.forgot.verify with field email. The modern template renders
    frontend.fm.pages.customer.forgot_password instead (see FrontendTheme).
--}}
@extends('frontend.layouts.app')

@section('title', 'Forgot Password')

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
                            <h5 class="mt-3 mb-1">Forgot your password?</h5>
                            <p class="sm-text mb-0">Enter the email on your account and we will send an OTP.</p>
                        </div>

                        <div class="auth-form__body">
                            <form action="{{ route('customer.forgot.verify') }}" method="POST">
                                @csrf

                                <div class="form-group">
                                    <label class="form--label" for="email">Email</label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                                           class="form--control @error('email') is-invalid @enderror"
                                           placeholder="you@example.com" required autofocus>
                                    @error('email')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <button class="btn btn--base w-100" type="submit" style="min-height:45px;">Send OTP</button>
                            </form>

                            <p class="sm-text text-center mt-3 mb-0">
                                Remembered it?
                                <a href="{{ route('customer.login') }}" class="t-link t-link--base text--base">Back to sign in</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
