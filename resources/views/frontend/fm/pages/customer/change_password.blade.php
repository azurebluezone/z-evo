{{--
    FM:SECTION page-customer-change-password -----------------------------------
    Classic source : resources/views/frontend/layouts/customer/change_password.blade.php
    Controller     : Frontend\CustomerController@change_pass()  (no variables)
    Handler        : Frontend\CustomerController@password_update()
    Sections       : fm-acct-body
    Consumes       : nothing beyond the rail

    ---------------------------------------------------------------------------
    CONTRACT
    ---------------------------------------------------------------------------
    Action route 'customer.password_update', POST, @csrf.
    Three field names, unchanged: old_password, new_password, confirm_password.

    Those three are exactly the keys password_update() validates, so they are
    exactly the keys that can appear in the error bag - @error() on any other
    key would be permanently empty.

    ---------------------------------------------------------------------------
    WHAT WAS DROPPED
    ---------------------------------------------------------------------------
    The classic page pushed parsley.min.js, form-validation.init.js and
    select2.min.js and then initialised $('.select2').select2(). None of the
    three is loaded by the modern chrome (there is no jQuery), and this form has
    no select2 class to initialise at all - the select2 call was a copy-paste
    from profile_edit. Dropping all three removes three render-blocking requests
    from a page that needed none of them.

    The one script kept is the show/hide toggle below, written in vanilla JS with
    no global dependency, so the page still works standalone.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.account', [
    'fmAcctTitle'   => 'Change Password',
    'fmAcctSub'     => 'Choose a password you do not use anywhere else.',
    'fmAcctEyebrow' => 'Security',
])

@section('fm-acct-body')
    <form action="{{ route('customer.password_update') }}" method="POST" novalidate>
        @csrf

        {{-- ------------------------- old_password ------------------------- --}}
        <div class="fm-acct__field">
            <label class="fm-acct__label" for="oldPassword">
                Current password <span class="req">*</span>
            </label>
            <div class="input-group">
                <input type="password"
                       id="oldPassword"
                       name="old_password"
                       class="form-control @error('old_password') is-invalid @enderror"
                       autocomplete="current-password"
                       required>
                <button type="button"
                        class="btn btn-outline-secondary"
                        data-fm-reveal="oldPassword"
                        aria-label="Show current password">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            <p class="fm-acct__error">@error('old_password') {{ $message }} @enderror</p>
        </div>

        {{-- ------------------------- new_password ------------------------- --}}
        <div class="fm-acct__field">
            <label class="fm-acct__label" for="newPassword">
                New password <span class="req">*</span>
            </label>
            <div class="input-group">
                <input type="password"
                       id="newPassword"
                       name="new_password"
                       class="form-control @error('new_password') is-invalid @enderror"
                       autocomplete="new-password"
                       required>
                <button type="button"
                        class="btn btn-outline-secondary"
                        data-fm-reveal="newPassword"
                        aria-label="Show new password">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            <p class="fm-acct__hint">
                Stored hashed. Six characters is the only length the server checks.
            </p>
            <p class="fm-acct__error">@error('new_password') {{ $message }} @enderror</p>
        </div>

        {{-- ---------------------- confirm_password ----------------------- --}}
        <div class="fm-acct__field">
            <label class="fm-acct__label" for="confirmPassword">
                Confirm new password <span class="req">*</span>
            </label>
            <div class="input-group">
                <input type="password"
                       id="confirmPassword"
                       name="confirm_password"
                       class="form-control @error('confirm_password') is-invalid @enderror"
                       autocomplete="new-password"
                       required>
                <button type="button"
                        class="btn btn-outline-secondary"
                        data-fm-reveal="confirmPassword"
                        aria-label="Show confirmation password">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            <p class="fm-acct__error">@error('confirm_password') {{ $message }} @enderror</p>
        </div>

        <div class="fm-acct__actions">
            <button type="submit" class="btn btn-brand-primary">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                Update password
            </button>
            <a href="{{ route('customer.account') }}" class="btn btn-brand-outline">
                Cancel
            </a>
        </div>
    </form>
@endsection

@push('script')
<script>
    (function () {
        'use strict';

        Array.prototype.forEach.call(
            document.querySelectorAll('[data-fm-reveal]'),
            function (button) {
                button.addEventListener('click', function () {
                    var input = document.getElementById(button.getAttribute('data-fm-reveal'));
                    if (!input) return;

                    var hidden = input.type === 'password';
                    input.type = hidden ? 'text' : 'password';

                    var icon = button.querySelector('i');
                    if (icon) {
                        icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
                    }
                    button.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
                });
            }
        );
    })();
</script>
@endpush
