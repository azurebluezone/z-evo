{{--
    FM:PARTIAL auth/login-form -------------------------------------------------
    The sign-in form, shared by two view names that must stay in sync:

        frontend.pages.auth.login       -> frontend/fm/pages/auth/login.blade.php
        frontend.layouts.customer.login -> frontend/fm/pages/customer/login.blade.php

    Both are mapped by FrontendTheme::modernViewFor(); only the first is
    rendered today (CustomerController@login() returns frontend.pages.auth.login
    and nothing returns frontend.layouts.customer.login), but the coverage report
    lists both, and a missing file there would keep reporting the second as
    CLASSIC forever.

    Contract (CustomerController@signin): POST customer.signin, @csrf, fields
    phone / password / remember. Do not rename a field.
-------------------------------------------------------------------------- --}}

<form action="{{ route('customer.signin') }}" method="POST" class="fm-au__form" novalidate>
    @csrf

    <div class="fm-au__field">
        <label class="fm-au__label" for="loginPhone">
            Phone number <span class="req">*</span>
        </label>
        <input type="tel"
               id="loginPhone"
               name="phone"
               class="fm-au__input"
               value="{{ old('phone') }}"
               placeholder="01XXXXXXXXX"
               inputmode="numeric"
               autocomplete="tel"
               autofocus
               required>
    </div>

    <div class="fm-au__field">
        <label class="fm-au__label" for="loginPassword">
            Password <span class="req">*</span>
        </label>
        <input type="password"
               id="loginPassword"
               name="password"
               class="fm-au__input"
               placeholder="Enter your password"
               autocomplete="current-password"
               required>
    </div>

    <div class="fm-au__row">
        <label class="fm-au__check mb-0" for="loginRemember">
            <input type="checkbox"
                   id="loginRemember"
                   name="remember"
                   value="1"
                   {{ old('remember') ? 'checked' : '' }}>
            <span>Remember me</span>
        </label>

        {{-- customer.forgot.password, NOT password.request: password.request is
             Laravel's default-web-guard reset form and cannot reset a customer
             account. The classic page pointed here and dead-ended. --}}
        <a href="{{ route('customer.forgot.password') }}" class="fm-au__link">
            Forgot password?
        </a>
    </div>

    {{-- No .verify-gcaptcha class: app.blade.php binds a submit handler to it
         that calls grecaptcha.getResponse(), and this template set loads no
         reCAPTCHA script and renders no widget, so carrying the class made every
         submit throw a TypeError. --}}
    <button type="submit" class="fm-au__btn">
        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
        Login Account
    </button>
</form>
