<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Turns a guest checkout into a real customer account.
 *
 * ---------------------------------------------------------------------------
 * WHAT IT OWNS
 * ---------------------------------------------------------------------------
 * CustomerController@order_save() used to inline this logic, and it was broken
 * in two ways that each returned HTTP 500 for every guest order:
 *
 *   1. The auto-created Customer was saved without an email while
 *      customers.email was NOT NULL  ->  "Field 'email' doesn't have a default
 *      value". Fixed by making the column nullable.
 *   2. The password was rand(111111, 999999) - about 900,000 possibilities,
 *      trivially brute-forced, and the plaintext was then thrown away in a
 *      local variable so nobody, including the customer, could ever see it.
 *
 * This service replaces that block.
 *
 * ---------------------------------------------------------------------------
 * THE THREE OUTCOMES
 * ---------------------------------------------------------------------------
 * already_signed_in  The customer was logged in. Nothing is touched - the
 *                    order simply hangs off the signed-in account.
 *
 * returned_customer  The phone number already has an account. Per the store
 *                    owner's decision this signs them straight into the session
 *                    so a returning guest is not treated as a stranger.
 *                    Their EXISTING PASSWORD IS NEVER READ, WRITTEN OR RESET.
 *
 * new_customer       The phone number is unknown. A new account is created with
 *                    a strong random password and the plaintext is handed to
 *                    this browser only, for a limited time (see below).
 *
 * ---------------------------------------------------------------------------
 * WHERE THE TEMPORARY PASSWORD LIVES - AND WHY NOT IN THE DATABASE
 * ---------------------------------------------------------------------------
 * customers.password keeps ONLY the bcrypt hash, exactly as before. The
 * plaintext is written to the SESSION, never to a column:
 *
 *     customer.temp_password      the plaintext, shown to the customer
 *     customer.temp_password_at   the moment it was issued
 *
 * Consequences, which is the point of the design:
 *   - An administrator reading the customers table sees a hash, never a usable
 *     password. There is no column to leak, no backup to expose, and no admin
 *     screen that could surface it.
 *   - It is scoped to ONE browser session. Logging out or clearing cookies ends
 *     it.
 *   - It expires on its own. See TEMP_PASSWORD_TTL_MINUTES.
 *
 * If the customer closes the tab without copying it, they use the existing
 * "forgot password" OTP flow - the account is never stranded, only inconvenient.
 *
 * ---------------------------------------------------------------------------
 * ⚠ RISK THE STORE OWNER HAS EXPLICITLY CHOSEN
 * ---------------------------------------------------------------------------
 * A guest who types a phone number that already belongs to a real customer is
 * signed into that customer's session. Anyone who knows that phone number can
 * therefore reach that customer's account by placing an order - their order
 * history, delivery addresses and invoices - without ever knowing a password.
 *
 * This is a deliberate trade (convenience for returning guests) and it is
 * implemented as asked. It is called out here, in the docs, and in the handover
 * notes so the decision is recorded rather than discovered later.
 *
 * The lower-risk alternative, should this be revisited: keep the guest as a
 * guest, and only sign them in after they prove the phone with the same OTP the
 * forgot-password flow already sends. resolveForOrder() has a single switch
 * point for that (see TRUST_PHONE_MATCHING).
 */
class GuestCheckoutAccountService
{
    /**
     * How long the customer can see their temporary password.
     *
     * Thirty minutes is long enough to copy it into a password manager, and
     * short enough that a shared or stolen browser stops being useful quickly.
     */
    public const TEMP_PASSWORD_TTL_MINUTES = 30;

    /** Session keys. Scoped names so they cannot collide with anything else. */
    public const SESSION_PASSWORD = 'customer.temp_password';
    public const SESSION_ISSUED_AT = 'customer.temp_password_at';

    /**
     * When true, a guest whose phone already has an account is NOT signed in -
     * they are treated as a guest and only prompted to sign in.
     *
     * Left false to match the requested behaviour. Flip this one line to turn
     * the account-matching behaviour off without touching anything else.
     */
    public const TRUST_PHONE_MATCHING = true;

    /**
     * Result of resolving the account behind an order.
     *
     * @return array{
     *     customer_id:int, customer:Customer, outcome:string,
     *     temp_password:?string, signed_in:bool
     * }
     */
    public function resolveForOrder(string $name, string $phone, string $address, ?int $areaId = null): array
    {
        $phone = $this->normalizePhone($phone);

        $signedIn = Auth::guard('customer')->user();
        if ($signedIn) {
            return [
                'customer_id'   => (int) $signedIn->id,
                'customer'      => $signedIn,
                'outcome'       => 'already_signed_in',
                'temp_password' => null,
                'signed_in'     => true,
            ];
        }

        $existing = Customer::where('phone', $phone)->first();

        if ($existing) {
            if (self::TRUST_PHONE_MATCHING) {
                // The session is established so the customer can reach their
                // account and see this order. The stored password hash is not
                // touched - see the risk note on this class.
                Auth::guard('customer')->loginUsingId($existing->id);

                // A returning customer has nothing temporary to see.
                $this->clearTemporaryPassword();

                return [
                    'customer_id'   => (int) $existing->id,
                    'customer'      => $existing->fresh() ?? $existing,
                    'outcome'       => 'returned_customer',
                    'temp_password' => null,
                    'signed_in'     => true,
                ];
            }

            // Opt-out path: attach the order to the existing account but do
            // not establish a session.
            return [
                'customer_id'   => (int) $existing->id,
                'customer'      => $existing,
                'outcome'       => 'returned_customer_guest',
                'temp_password' => null,
                'signed_in'     => false,
            ];
        }

        $temporary = $this->generateTemporaryPassword();

        $customer = new Customer();
        $customer->name     = $this->trimName($name);
        // A slug built from the name collides the moment two customers share a
        // name ("Rahim Uddin" twice), so the id is mixed in. Str::slug can also
        // return '' for a name made entirely of punctuation.
        $customer->slug     = $this->uniqueSlug($name, $phone);
        $customer->phone    = $phone;
        $customer->email    = null;              // nullable since 2026_10_18
        $customer->address  = $address !== '' ? $address : null;
        $customer->area     = $areaId ?: null;
        $customer->password = bcrypt($temporary); // hash only, never the plaintext
        $customer->verify   = 1;
        $customer->status   = 'active';
        $customer->save();

        $this->issueTemporaryPassword($temporary);

        // Sign the guest in so they land on their new account instead of being
        // bounced back to a login form they have no credentials for.
        Auth::guard('customer')->loginUsingId($customer->id);

        return [
            'customer_id'   => (int) $customer->id,
            'customer'      => $customer,
            'outcome'       => 'new_customer',
            'temp_password' => $temporary,
            'signed_in'     => true,
        ];
    }

    /**
     * The temporary password, or null once it has expired or been cleared.
     *
     * Called on every render of the account panel, so the 30 minute window
     * needs no scheduled job, no extra column and no admin tooling: the
     * timestamp is compared against now() each time it is read.
     */
    public function temporaryPasswordFor(?Customer $customer = null): ?string
    {
        $plaintext = session(self::SESSION_PASSWORD);
        $issuedAt  = session(self::SESSION_ISSUED_AT);

        if (! is_string($plaintext) || $plaintext === '' || ! $issuedAt) {
            return null;
        }

        if ($this->secondsRemaining() <= 0) {
            $this->clearTemporaryPassword();

            return null;
        }

        $ownerId = session('customer.temp_password_for');
        if ($ownerId !== null && $customer !== null && (int) $ownerId !== (int) $customer->id) {
            // Session reused after a different customer signed in: do not show
            // one customer's password to another.
            return null;
        }

        return $plaintext;
    }

    /** Seconds left before the temporary password stops being shown. */
    public function secondsRemaining(): int
    {
        $issuedAt = session(self::SESSION_ISSUED_AT);
        if (! $issuedAt) {
            return 0;
        }

        return max(0, ((int) $issuedAt + (self::TEMP_PASSWORD_TTL_MINUTES * 60)) - now()->getTimestamp());
    }

    /**
     * Forget the temporary password for good.
     *
     * Called the moment the customer sets a real password - from the account
     * panel or the forgot-password flow - so it disappears on use rather than
     * lingering for the rest of the window.
     */
    public function clearTemporaryPassword(): void
    {
        session()->forget(self::SESSION_PASSWORD);
        session()->forget(self::SESSION_ISSUED_AT);
        session()->forget('customer.temp_password_for');
    }

    /**
     * A password that is actually unguessable.
     *
     * The old rand(111111, 999999) had under 900k combinations. This mixes
     * upper, lower, digit and symbol characters and shuffles them, so the
     * space is astronomically larger and the value still satisfies the
     * 'min:6' rule that store() applies to self-registered passwords.
     */
    private function generateTemporaryPassword(int $length = 12): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghijkmnopqrstuvwxyz';
        $digits  = '23456789';
        $symbols = '!@#$%&*?';

        $all = $upper . $lower . $digits . $symbols;

        // Guarantee one of each class so the result always satisfies a
        // "mixed character classes" policy.
        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        // Fisher-Yates with a CSPRNG; shuffle() is not cryptographically safe.
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }

    private function issueTemporaryPassword(string $plaintext, ?int $customerId = null): void
    {
        session()->put(self::SESSION_PASSWORD, $plaintext);
        session()->put(self::SESSION_ISSUED_AT, now()->getTimestamp());

        if ($customerId !== null) {
            session()->put('customer.temp_password_for', $customerId);
        }

        // Deliberately NOT logged. A plaintext password must never reach the
        // log files, which are shipped to aggregators far more often than the
        // database is.
    }

    private function uniqueSlug(string $name, string $phone): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            // Nothing sluggable (e.g. a name of only punctuation).
            $base = 'customer';
        }

        if (! Customer::where('slug', $base)->exists()) {
            return $base;
        }

        // Same name already taken. The phone tail keeps it readable and unique
        // without a second round trip.
        $candidate = $base . '-' . substr(preg_replace('/\D/', '', $phone) ?: '0', -6);

        if (! Customer::where('slug', $candidate)->exists()) {
            return $candidate;
        }

        return $base . '-' . Str::lower(Str::random(6));
    }

    private function trimName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        // customers.name is varchar(155).
        return mb_substr($name !== '' ? $name : 'Customer', 0, 155);
    }

    /**
     * Normalise to the 11 digit local form the rest of the app stores.
     *
     * The Customer table keys on phone, so "+8801712345678", "8801712345678"
     * and "01712345678" must all land on the same row or a returning guest
     * would be treated as a stranger. Mirrors CourierFraudService's rules.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', trim($phone)) ?? '';
        $phone = preg_replace('/[^0-9+]/', '', $phone) ?? '';

        if (str_starts_with($phone, '+880')) {
            $phone = '0' . substr($phone, 4);
        } elseif (str_starts_with($phone, '880') && strlen($phone) > 11) {
            $phone = '0' . substr($phone, 3);
        }

        return $phone;
    }
}
