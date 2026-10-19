{{--
    FM:PARTIAL auth/register-form ----------------------------------------------
    The registration form, shared by two view names that must stay in sync:

        frontend.pages.auth.register       -> frontend/fm/pages/auth/register.blade.php
        frontend.layouts.customer.register -> frontend/fm/pages/customer/register.blade.php

    Only the first is rendered today (CustomerController@register() returns
    frontend.pages.auth.register); the second exists so FrontendTheme::coverage()
    stops reporting it as CLASSIC.

    Contract (CustomerController@store): POST customer.store, @csrf, fields
    name / phone / email / password / password_confirmation / agree.

    store() validates only name (required), phone (required|unique) and password
    (required|min:6) - which is exactly why the three @error() blocks below are
    wired to those keys. The classic page had NO @error() anywhere, so a
    duplicate phone number produced a silent round trip.
-------------------------------------------------------------------------- --}}

<form action="{{ route('customer.store') }}" method="POST" class="fm-au__form" novalidate>
    @csrf

    <div class="fm-au__field">
        <label class="fm-au__label" for="regName">
            Full name <span class="req">*</span>
        </label>
        <input type="text"
               id="regName"
               name="name"
               class="fm-au__input @error('name') is-invalid @enderror"
               value="{{ old('name') }}"
               placeholder="Your name"
               autocomplete="name"
               autofocus
               required>
        <p class="fm-au__error">@error('name') {{ $message }} @enderror</p>
    </div>

    <div class="fm-au__field">
        <label class="fm-au__label" for="regPhone">
            Phone number <span class="req">*</span>
        </label>
        <input type="tel"
               id="regPhone"
               name="phone"
               class="fm-au__input @error('phone') is-invalid @enderror"
               value="{{ old('phone') }}"
               placeholder="01XXXXXXXXX"
               inputmode="numeric"
               autocomplete="tel"
               required>
        <p class="fm-au__hint">This is your username - you sign in with it.</p>
        <p class="fm-au__error">@error('phone') {{ $message }} @enderror</p>
    </div>

    {{-- Required, but note: store() never validates email and customers.email
         is NOT NULL, so an empty value is a server-side problem. The browser
         requirement blocks the common case; the gap is a controller fix. --}}
    <div class="fm-au__field">
        <label class="fm-au__label" for="regEmail">
            Email address <span class="req">*</span>
        </label>
        <input type="email"
               id="regEmail"
               name="email"
               class="fm-au__input @error('email') is-invalid @enderror"
               value="{{ old('email') }}"
               placeholder="you@example.com"
               autocomplete="email"
               required>
        <p class="fm-au__error">@error('email') {{ $message }} @enderror</p>
    </div>

    <div class="fm-au__field">
        <label class="fm-au__label" for="regPassword">
            Password <span class="req">*</span>
        </label>
        <input type="password"
               id="regPassword"
               name="password"
               class="fm-au__input @error('password') is-invalid @enderror"
               placeholder="At least 6 characters"
               autocomplete="new-password"
               required>
        <p class="fm-au__hint">At least 6 characters.</p>
        <p class="fm-au__error">@error('password') {{ $message }} @enderror</p>
    </div>

    {{-- Rendered because it is part of the form contract, but store() has no
         'confirmed' rule, so the server does not currently enforce a match. --}}
    <div class="fm-au__field">
        <label class="fm-au__label" for="regPasswordConfirm">
            Confirm password <span class="req">*</span>
        </label>
        <input type="password"
               id="regPasswordConfirm"
               name="password_confirmation"
               class="fm-au__input @error('password_confirmation') is-invalid @enderror"
               placeholder="Repeat your password"
               autocomplete="new-password"
               required>
        <p class="fm-au__error">@error('password_confirmation') {{ $message }} @enderror</p>
    </div>

    {{-- Plain text, not three anchors: the classic linked
         url('/policy/privacy-policy'), url('/policy/terms-and-conditions') and
         url('/policy/return-policy'), none of which exist in routes/web.php. --}}
    <label class="fm-au__check" for="regAgree">
        <input type="checkbox"
               id="regAgree"
               name="agree"
               value="1"
               required>
        <span>
            I agree to create an account on {{ $generalsetting->name ?? config('app.name') }}.
        </span>
    </label>

    <button type="submit" class="fm-au__btn">
        <i class="bi bi-person-plus" aria-hidden="true"></i>
        Create account
    </button>
</form>
