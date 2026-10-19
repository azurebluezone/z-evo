@extends('frontend.layouts.app')

@section('title', 'Login')
@section('meta_description', 'Login to your account')

@section('content')
<main>
    <div class="auth-section auth-section--light py-60" style="background-image: url('{{ asset('assets/images/frontend/auth_pages_bg/676295a0936801734514080.png') }}');">
        <div class="container">
            <div class="row g-4 gy-lg-0 justify-content-between flex-wrap-reverse align-items-center">
                <div class="col-lg-6 col-xxl-7 d-none d-lg-block">
                    <div class="text-center pe-xl-5">
                        <img src="{{ asset('assets/images/frontend/login_page/66f7cd2b8c4621727515947.png') }}" alt="image" class="img-fluid">
                    </div>
                </div>

                <div class="col-lg-6 col-xxl-5">
                    <div class="auth-form">
                        <div class="auth-form__head text-center">
                            <div class="logo">
                                <a href="{{ url('/') }}"><img src="{{ asset('assets/images/logo_icon/logo_dark.png') }}" alt="logo"></a>
                            </div>
                        </div>

                        <div class="auth-form__body">
                            <form method="POST" action="{{ route('customer.signin') }}" class="verify-gcaptcha">
                                @csrf

                                <div class="form-group">
                                    <label class="form--label">Phone</label>
                                    <input class="form--control" type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone" required autofocus>
                                </div>

                                <div class="form-group">
                                    <label class="form--label" for="password">Password</label>
                                    <input id="password" type="password" name="password" class="form--control" placeholder="Enter Your Password" required autocomplete="current-password">
                                </div>

                                <div class="form-group">
                                    <div class="d-flex gap-1 flex-wrap justify-content-between">
                                        <div class="form-check form--check">
                                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="remember">Remember Me</label>
                                        </div>

                                        <a href="{{ route('password.request') }}" class="t-link d-block text-end text--base heading-clr sm-text fw-md">Forgot Password?</a>
                                    </div>
                                </div>

                                <button class="btn btn--md btn--base sm-text h-45 w-100" type="submit">Login Account</button>

                                <p class="mt-2 mb-0">
                                    Don't have an account?
                                    <a href="{{ route('customer.register') }}" class="t-link t-link--base text--base">Create An Account</a>
                                </p>
                            </form>

                            <div class="auth-divide col-12">
                                <span class="auth-divide-text">Or</span>
                            </div>

                            <ul class="social-auth-list col-12">
                                <li class="social-auth-item">
                                    <a href="{{ url('/user/social-login/google') }}" class="social-auth-link">
                                        <span class="icon">
                                            <img src="{{ asset('assets/templates/basic/images/google.svg') }}" alt="Google">
                                        </span>
                                        <span class="text">Login Google Account</span>
                                    </a>
                                </li>
                            </ul>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
@endsection
