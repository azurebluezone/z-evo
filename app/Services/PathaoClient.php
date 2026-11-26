<?php

namespace App\Services;

use App\Models\Courierapi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The one client for Pathao's courier (merchant) API.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS EXISTS
 * ---------------------------------------------------------------------------
 * Four places used to build a Pathao URL, fetch a Pathao token and POST a
 * Pathao order, each with its own idea of the base path, the timeout and what
 * counts as success:
 *
 *   PathaoClient::url()                    skipped /aladdin on any "sandbox" host
 *   OrderController::pathaoUrl()           did the same thing, separately
 *   OrderController::order_pathao_auto()   Http::post() with NO timeout at all
 *   CourierCheckCommand::checkPathao()     {base}/issue-token (no /aladdin, no api/v1)
 *
 * All four were wrong in the same direction, and that is what produced:
 *
 *   Could not reach Pathao: cURL error 28: Operation timed out after 5001 ms
 *   ... for https://courier-api-sandbox.pathao.com/api/v1/issue-token
 *
 * ---------------------------------------------------------------------------
 * WHERE /aladdin COMES FROM (verified live, not assumed)
 * ---------------------------------------------------------------------------
 * The official merchant API documentation lists:
 *
 *   base_url  https://courier-api-sandbox.pathao.com
 *   endpoint  /aladdin/api/v1/issue-token
 *
 * Measured against the sandbox with the documentation's own test credentials:
 *
 *   POST /aladdin/api/v1/issue-token   -> 200, access_token, expires_in 7776000
 *   POST /api/v1/issue-token           -> 404 "404 page not found"
 *
 * and BOTH took ~20 seconds to answer. So the missing /aladdin was a real 404,
 * and the 5 second timeout hid it: the request was abandoned before Pathao had
 * even finished saying "not found". That transport timeout then surfaced on
 * screen as "Pathao refused the credentials", which sent the admin chasing keys
 * that were in fact correct.
 *
 * buildUrl() is now the ONLY place the base path is decided. Every caller -
 * the admin test, the order screen, the senders and the CLI - goes through it,
 * so they cannot drift apart again.
 *
 * ---------------------------------------------------------------------------
 * TOKEN LIFETIME IS THE API'S OWN NUMBER, NOT A GUESS
 * ---------------------------------------------------------------------------
 * The sandbox returns expires_in = 7776000 (90 days). Rather than assume an
 * hour, refreshToken() reads the number the API returns and marks the token
 * fresh for slightly less than that, falling back to Pathao's documented hour
 * when it is missing.
 */
class PathaoClient
{
    /**
     * Hard bounds on any single call.
     *
     * Connect is fast (0.13s measured); it is the RESPONSE that is slow
     * (~20s on every sandbox endpoint). A total bound tight enough to protect
     * an admin request therefore has to sit above 20s, otherwise every healthy
     * call is reported as a timeout.
     */
    private const CONNECT_TIMEOUT_SECONDS = 10;

    private const TIMEOUT_SECONDS = 30;

    /** Cache key holding "the stored token is still good". */
    private const FRESH_KEY = 'admin.courier.pathao.token.fresh';

    /** Why the last token fetch failed, for the caller to report. */
    public ?string $lastError = null;

    /**
     * HTTP status of the last failed token fetch.
     *
     * null means Pathao never answered (network/timeout), which is a completely
     * different problem from a 401 and must not be described as "bad keys".
     */
    public ?int $lastStatus = null;

    public function __construct(private Courierapi $config)
    {
    }

    /**
     * The Pathao account, but only when it is switched on AND fully filled in.
     *
     * Returns null for the common case of a disabled courier, which lets callers
     * answer with a specific message instead of silently doing nothing.
     */
    public static function enabled(): ?self
    {
        $config = Courierapi::query()->where('type', 'pathao')->first();

        if (! $config || ! $config->isReady()) {
            return null;
        }

        return new self($config);
    }

    /**
     * The Pathao account whenever its credentials exist, even if it is switched
     * off. Used by the admin "test connection" button, which must be able to
     * check a courier before enabling it.
     */
    public static function configured(): ?self
    {
        $config = Courierapi::query()->where('type', 'pathao')->first();

        if (! $config || ! $config->isConfigured()) {
            return null;
        }

        return new self($config);
    }

    public function url(string $path): string
    {
        return self::buildUrl((string) $this->config->url, $path);
    }

    /**
     * Build a Pathao URL from whatever base the admin saved.
     *
     * Pathao serves the merchant API under /aladdin on BOTH the production host
     * (api-hermes.pathao.com) and the sandbox host — see the class header for
     * the live measurement. A base that already carries the API root is reduced
     * back to the host first, so pasting the full documented URL does not
     * produce /aladdin/aladdin or /api/v1/aladdin.
     */
    public static function buildUrl(string $base, string $path): string
    {
        $base = rtrim(trim($base), '/');
        $path = ltrim(trim($path), '/');

        // A caller that already passes the full documented path wins.
        if (str_starts_with($path, 'aladdin/')) {
            return $base . '/' . $path;
        }

        foreach (['/aladdin/api/v1', '/aladdin', '/api/v1'] as $suffix) {
            if (str_ends_with($base, $suffix)) {
                $base = substr($base, 0, -strlen($suffix));

                break;
            }
        }

        return $base . '/aladdin/' . $path;
    }

    /**
     * A usable bearer token, refreshing only when there is reason to.
     */
    public function token(bool $force = false): ?string
    {
        if (! $force && filled($this->config->token) && Cache::has(self::FRESH_KEY)) {
            return (string) $this->config->token;
        }

        return $this->refreshToken();
    }

    /**
     * Exchange the stored credentials for a fresh token.
     *
     * Returns null and sets $lastError/$lastStatus on failure; never throws,
     * because a courier being down must not take an admin page with it.
     */
    public function refreshToken(): ?string
    {
        $this->lastError  = null;
        $this->lastStatus = null;

        try {
            $response = Http::asJson()
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS)
                ->post($this->url('api/v1/issue-token'), [
                    'client_id'     => $this->config->client_id,
                    'client_secret' => $this->config->client_secret,
                    'grant_type'    => 'password',
                    'username'      => $this->config->username,
                    'password'      => $this->config->password,
                ]);
        } catch (\Throwable $e) {
            $this->lastError = 'Could not reach Pathao: ' . $e->getMessage();
            Log::warning('Pathao token request failed', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            $this->lastStatus = $response->status();

            // Pathao's body carries the useful part when there is one, but the
            // token endpoint answers an empty 500 for bad client credentials -
            // so do not end the sentence with a dangling colon.
            $detail = trim(substr((string) $response->body(), 0, 300));

            $this->lastError = 'Pathao refused the request (HTTP ' . $response->status() . ')'
                . ($detail !== '' ? ': ' . $detail : '.');

            Log::warning('Pathao refused the token request', ['status' => $response->status()]);

            return null;
        }

        $data  = $response->json();
        $token = is_array($data) ? ($data['access_token'] ?? $data['token'] ?? null) : null;

        if (! filled($token)) {
            $this->lastError = 'Pathao answered without an access_token.';
            Log::warning('Pathao token response had no access_token');

            return null;
        }

        $this->config->token = (string) $token;
        $this->config->save();

        // Trust the API's own lifetime, less a safety margin, instead of
        // assuming a fixed one. The sandbox returns 90 days.
        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        Cache::put(self::FRESH_KEY, true, now()->addSeconds(max(60, $expiresIn - 120)));

        return (string) $token;
    }

    /**
     * GET a Pathao resource.
     *
     * @return array{ok: bool, status: int, body: mixed, message: ?string}
     */
    public function get(string $path): array
    {
        return $this->authorised('GET', $path);
    }

    /**
     * POST a Pathao resource.
     *
     * @return array{ok: bool, status: int, body: mixed, message: ?string}
     */
    public function post(string $path, array $payload): array
    {
        return $this->authorised('POST', $path, $payload);
    }

    /**
     * The merchant's stores, as Pathao returns them.
     *
     * @return array{ok: bool, stores: array<int, array<string, mixed>>, message: ?string}
     */
    public function stores(): array
    {
        $result = $this->get('api/v1/stores');

        if (! $result['ok']) {
            return [
                'ok'      => false,
                'stores'  => [],
                'message' => $result['message'] ?? 'Pathao stores could not be fetched.',
            ];
        }

        $stores = data_get($result['body'], 'data.data');

        if (! is_array($stores)) {
            return [
                'ok'      => false,
                'stores'  => [],
                'message' => 'Pathao answered the store list in an unexpected shape.',
            ];
        }

        return ['ok' => true, 'stores' => $stores, 'message' => null];
    }

    /**
     * The store a parcel should be booked from.
     *
     * Pathao's store list is not ordered for us: a merchant may hold several
     * stores, and the sandbox account is SHARED between every tester (33
     * stores observed). Taking `$stores[0]` - which the bulk sender used to do -
     * books the parcel from whichever store Pathao happens to list first.
     *
     * Order of preference:
     *   1. the merchant's own default store,
     *   2. the first active store that is NOT the default return store (parcels
     *      booked from a return store are collected from the returns desk),
     *   3. the first active store.
     *
     * @param  array<int, array<string, mixed>>  $stores
     * @return array<string, mixed>|null
     */
    public static function preferredStore(array $stores): ?array
    {
        $active = array_values(array_filter(
            $stores,
            fn ($store) => is_array($store) && (int) ($store['is_active'] ?? 1) === 1
        ));

        if ($active === []) {
            return null;
        }

        foreach ($active as $store) {
            if (! empty($store['is_default_store'])) {
                return $store;
            }
        }

        foreach ($active as $store) {
            if (empty($store['is_default_return_store'])) {
                return $store;
            }
        }

        return $active[0];
    }

    /**
     * Book one parcel. See PathaoOrderSender for the payload.
     *
     * @return array{ok: bool, status: int, body: mixed, message: ?string}
     */
    public function createOrder(array $payload): array
    {
        return $this->post('api/v1/orders', $payload);
    }

    /**
     * Make an authenticated request, refreshing once if the token expired.
     *
     * @return array{ok: bool, status: int, body: mixed, message: ?string}
     */
    private function authorised(string $method, string $path, ?array $payload = null): array
    {
        $token = $this->token($this->shouldForceRefresh());

        /*
         * Do not send a request we already know must fail.
         *
         * Without this guard the next line would send `Authorization: Bearer `
         * with an empty token, and Pathao answers 400 "bearer token missing" -
         * which describes the symptom, not the cause, and buries the real reason
         * (bad client credentials) inside the token fetch that just failed.
         * Reporting $lastError instead keeps the cause visible.
         */
        if ($token === null) {
            return [
                'ok'      => false,
                'status'  => 0,
                'body'    => null,
                'message' => $this->lastError ?? 'No Pathao access token could be obtained.',
            ];
        }

        $result = $this->attempt($method, $path, $token, $payload);

        // A token can expire between refreshes (clock skew, a server that
        // revokes early). One forced retry turns that into a slower success
        // rather than a visible failure.
        if ($result['status'] === 401) {
            $fresh = $this->refreshToken();

            if ($fresh !== null) {
                $result = $this->attempt($method, $path, $fresh, $payload);
            }
        }

        return $result;
    }

    /**
     * Only force a refresh when we have no token at all; otherwise let token()
     * use the cache and let authorised() handle a 401 on its own.
     */
    private function shouldForceRefresh(): bool
    {
        return blank($this->config->token);
    }

    /**
     * One HTTP attempt, with every failure mode normalised.
     *
     * @return array{ok: bool, status: int, body: mixed, message: ?string}
     */
    private function attempt(string $method, string $path, ?string $token, ?array $payload = null): array
    {
        try {
            $pending = Http::withHeaders([
                'Authorization' => 'Bearer ' . (string) $token,
                'Accept'        => 'application/json',
            ])
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS);

            $response = $method === 'POST'
                ? $pending->asJson()->post($this->url($path), $payload ?? [])
                : $pending->get($this->url($path));
        } catch (\Throwable $e) {
            Log::warning('Pathao request failed', ['path' => $path, 'error' => $e->getMessage()]);

            return [
                'ok'      => false,
                'status'  => 0,
                'body'    => null,
                'message' => 'Could not reach Pathao: ' . $e->getMessage(),
            ];
        }

        $body = $response->json();

        if ($response->successful()) {
            return [
                'ok'      => true,
                'status'  => $response->status(),
                'body'    => $body,
                'message' => null,
            ];
        }

        return [
            'ok'      => false,
            'status'  => $response->status(),
            'body'    => $body ?? substr((string) $response->body(), 0, 300),
            'message' => 'Pathao answered HTTP ' . $response->status() . '.',
        ];
    }
}
