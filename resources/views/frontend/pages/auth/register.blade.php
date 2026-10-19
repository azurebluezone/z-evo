@extends('frontend.layouts.app')

@section('title', 'Register')
@section('meta_description', 'Create a new account')

@section('content')
<main>
    <div class="auth-section auth-section--light py-60" style="background-image: url('{{ asset('assets/images/frontend/auth_pages_bg/676295a0936801734514080.png') }}');">
        <div class="container">
            <div class="row g-4 gy-lg-0 justify-content-between align-items-center">

                <div class="col-lg-6 col-xxl-7 d-none d-lg-block">
                    <div class="text-center pe-xl-5">
                        <img src="{{ asset('assets/images/frontend/register_page/660256dc91c6c1711429340.png') }}" alt="image" class="img-fluid">
                    </div>
                </div>

                <div class="col-lg-6 col-xxl-5">
                    <div class="auth-form">
                        <div class="auth-form__head text-center">
                            <div class="logo mb-3">
                                <a href="{{ url('/') }}"><img src="{{ asset('assets/images/logo_icon/logo_dark.png') }}" alt="logo"></a>
                            </div>
                            <p class="text-muted">Join us and unlock exclusive deals and seamless shopping!</p>
                        </div>

                        <div class="auth-form__body">
                            <form action="{{ route('customer.store') }}" method="POST" class="verify-gcaptcha">
                                @csrf

                                <div class="form-group">
                                    <label class="form--label">Name</label>
                                    <input type="text" class="form-control form--control" name="name" value="{{ old('name') }}" required>
                                </div>

                                <div class="form-group">
                                    <label class="form--label">Phone</label>
                                    <input type="text" class="form-control form--control" name="phone" value="{{ old('phone') }}" required>
                                </div>

                                <div class="form-group">
                                    <label class="form--label" for="email">Email</label>
                                    <input id="email" type="email" class="form-control form--control" name="email" value="{{ old('email') }}" required>
                                    <small class="text-danger emailExist"></small>
                                </div>

                                <div class="form-group">
                                    <label class="form--label" for="password">Password</label>
                                    <input id="password" type="password" class="form-control form--control" name="password" required autocomplete="new-password">
                                </div>

                                <div class="form-group">
                                    <label class="form--label" for="password-confirm">Confirm Password</label>
                                    <input id="password-confirm" class="form-control form--control" type="password" name="password_confirmation" required>
                                </div>

                                <div class="form-group">
                                    <div class="form-check form--check d-flex gap-2">
                                        <input class="form-check-input" type="checkbox" id="agree" name="agree" required>
                                        <label class="form-check-label m-0 p-0" for="agree">
                                            I agree with
                                            <a class="text--base" href="{{ url('/policy/privacy-policy') }}" target="_blank">Privacy Policy</a>,
                                            <a class="text--base" href="{{ url('/policy/terms-and-conditions') }}" target="_blank">Terms and Conditions</a>,
                                            <a class="text--base" href="{{ url('/policy/return-policy') }}" target="_blank">Return Policy</a>
                                        </label>
                                    </div>
                                </div>

                                <div class="aurt-form-btn">
                                    <button class="btn btn--md btn--base h-45 w-100" type="submit">Register</button>
                                </div>

                                <p class="mt-2 mb-0">
                                    Already have an account?
                                    <a href="{{ route('customer.login') }}" class="t-link t-link--base text--base">Login</a>
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
                                        <span class="text">Register with Your Google Account</span>
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
