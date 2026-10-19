{{--
    FM:PARTIAL account/temp-password ------------------------------------------
    The temporary password a guest was issued after their first order.

    ---------------------------------------------------------------------------
    WHY THIS IS NOT JUST A FLASH MESSAGE
    ---------------------------------------------------------------------------
    The plaintext lives in the SESSION, never in the database
    (see GuestCheckoutAccountService). It is deliberately NOT shown once and
    lost, because a guest who closes the tab would then be stranded with an
    account they cannot log into. Instead it stays visible on the panel for a
    fixed window so the customer can copy it into a password manager.

    The window is enforced server side: temporaryPasswordFor() re-reads the
    timestamp and returns null once it has passed, so the countdown below is a
    courtesy, not the control. Even a manipulated clock cannot reveal the value
    after the TTL, because the page simply never receives it.

    What this guarantees, per the store owner's decision:
      - An administrator reading the customers table sees only a bcrypt hash.
        There is no column, no admin screen and no log line carrying the value.
      - It is scoped to one browser session and disappears on logout.
      - It is retired immediately if the customer sets a real password, from
        either the change-password form or the forgot-password OTP flow.

    Expects nothing. Reads its own state from the service.
-------------------------------------------------------------------------- --}}

@php
    $tempPasswordService = app(\App\Services\GuestCheckoutAccountService::class);

    // $customer is passed by the account page; it scopes the value to the
    // account actually signed in, so a session that switched users cannot show
    // one customer's password to another.
    $tempPassword = $tempPasswordService->temporaryPasswordFor($customer ?? null);
    $tempSeconds  = $tempPasswordService->secondsRemaining();
@endphp

@if($tempPassword !== null)
    <div class="fm-acct__notice fm-acct__notice--info mb-4"
         role="status"
         data-fm-temp-password
         data-expires-in="{{ $tempSeconds }}">
        <i class="bi bi-key" aria-hidden="true"></i>

        <div class="w-100">
            <p class="mb-1">
                <strong>Your account is ready.</strong>
                We made you an account when you placed your first order.
                Sign in with your phone number and this temporary password:
            </p>

            {{-- The value itself. monospace + user-select so copying is
                 reliable; NOT an input, because a text input invites a
                 password manager to try to save it. --}}
            <p class="mb-2">
                <span class="fm-au__code"
                      style="font-size:1rem;letter-spacing:.06em;user-select:all;"
                      data-fm-temp-value>{{ $tempPassword }}</span>
            </p>

            <p class="mb-2 small" style="color:#6b7a70;">
                <i class="bi bi-clock" aria-hidden="true"></i>
                This password disappears in
                <strong data-fm-temp-countdown
                        data-seconds="{{ $tempSeconds }}">{{ gmdate('i:s', $tempSeconds) }}</strong>
                and cannot be shown again.
            </p>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('customer.change_pass') }}" class="btn btn-brand-primary btn-sm">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    Set your own password
                </a>
                {{-- The existing OTP reset, which stays available afterwards. --}}
                <a href="{{ route('customer.forgot.password') }}" class="btn btn-brand-outline btn-sm">
                    Reset by phone instead
                </a>
            </div>

            <p class="mt-2 mb-0 small" style="color:#6b7a70;">
                Nobody at the shop can see this password - we only stored a
                one-way hash of it.
            </p>
        </div>
    </div>
@endif
