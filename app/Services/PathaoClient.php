<?php

namespace App\Services;

use App\Models\Courierapi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * A thin, honest client for Pathao's courier API.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS EXISTS
 * ---------------------------------------------------------------------------
 * Two endpoints in OrderController asked Pathao for a city's zones and a zone's
 * areas and looked like this:
 *
 *     if ($pathao_info) {
 *         $response = Http::get($this->pathaoUrl(...));
 *         return response()->json($response->json());
 *     } else {
 *         return response()->json([]);          // <-- the problem
 *     }
 *
 * Everything about that is quiet:
 *
 *   - `$pathao_info` is looked up with ->where('status', 1). Pathao ships
 *     disabled, so the lookups took the else branch and answered 200 [] - the
 *     correct shape for "this city has no zones" and the wrong answer for "we
 *     never asked". In the browser the two are indistinguishable, which is
 *     exactly how "the zone dropdown is empty and nothing is requested" reads
 *     as a broken API rather than a switched-off courier.
 *
 *   - No Authorization header was sent at all. Pathao requires
 *     `Authorization: Bearer <token>` on these routes, so a configured account
 *     would still have been refused - and again reported as an empty list.
 *
 *   - No timeout. A slow Pathao would have held the PHP worker until the
 *     default socket timeout.
 *
 *   - The token was fetched once when settings were saved and never refreshed.
 *     Stored tokens expire, so lookups that worked on day one start answering
 *     401 later, silently, with no way to tell from the UI.
 *
 * This class fixes those four things in one place and returns a shape the
 * caller can act on instead of an empty array that means nothing.
 *
 * ---------------------------------------------------------------------------
 * TOKEN LIFETIME IS THE API'S OWN NUMBER, NOT A GUESS
 * ---------------------------------------------------------------------------
 * Rather than assume Pathao tokens last an hour, refreshToken() reads the
 * `expires_in` the API returns and marks the token fresh for slightly less than
 * that. If the number is missing it falls back to 3600 (Pathao's documented
 * default). A 401 still triggers one forced refresh and retry, so a server that
 * expires a token early degrades to one extra round trip instead of a failure.
 *
 * ---------------------------------------------------------------------------
 * UNVERIFIED AGAINST A LIVE PATHAO ACCOUNT
 * ---------------------------------------------------------------------------
 * No Pathao credentials are configured on this install, so the successful
 * payload shape is NOT_TESTED. What IS tested is the refusal path: an account
 * that is disabled is refused locally without any request leaving the server,
 * and a request that does go out surfaces Pathao's own error text.
 */
class PathaoClient
{
    /** Hard bound on any single call, matching the reference-data guard. */
    private const TIMEOUT_SECONDS = 5;

    /** Cache key holding "the stored token is still good". */
    private const FRESH_KEY = 'admin.courier.pathao.token.fresh';

    /** Why the last token fetch failed, for the caller to report. */
    public ?string $lastError = null;

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

    /**
     * Build a Pathao URL.
     *
     * Pathao puts its API under /aladdin in production, but its sandbox host
     * serves it at the root. A base that already carries /aladdin is left alone
     * so an admin who pasted the full URL is not double-prefixed.
     */
    public function url(string $path): string
    {
        $base = rtrim((string) $this->config->url, '/');
        $path = ltrim($path, '/');

        if (str_contains($base, 'sandbox') || str_contains($base, '/aladdin') || str_starts_with($path, 'aladdin/')) {
            return $base . '/' . $path;
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
     * Returns null and sets $lastError on failure; never throws, because a
     * courier being down must not take an admin page with it.
     */
    public function refreshToken(): ?string
    {
        $this->lastError = null;

        try {
            $response = Http::asJson()
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
            // Pathao's body carries the useful part when there is one, but the
            // token endpoint answers an empty 500 for bad client credentials -
            // so do not end the sentence with a dangling colon.
            $detail = trim(substr((string) $response->body(), 0, 300));

            $this->lastError = 'Pathao refused the credentials (HTTP ' . $response->status() . ')'
                . ($detail !== '' ? ': ' . $detail : '.');

            Log::warning('Pathao refused credentials', ['status' => $response->status()]);

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
        // assuming a fixed one. A missing value falls back to Pathao's
        // documented hour.
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
                'ok' => false,
                'status' => 0,
                'body' => null,
                'message' => $this->lastError ?? 'No Pathao access token could be obtained.',
            ];
        }

        $result = $this->attempt($path, $token);

        // A token can expire between refreshes (clock skew, a server that
        // revokes early). One forced retry turns that into a slower success
        // rather than a visible failure.
        if ($result['status'] === 401) {
            $fresh = $this->refreshToken();

            if ($fresh !== null) {
                $result = $this->attempt($path, $fresh);
            }
        }

        return $result;
    }

    /**
     * Only force a refresh when we have no token at all; otherwise let token()
     * use the cache and let get() handle a 401 on its own.
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
    private function attempt(string $path, ?string $token): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . (string) $token,
                'Accept'        => 'application/json',
            ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->get($this->url($path));
        } catch (\Throwable $e) {
            Log::warning('Pathao request failed', ['path' => $path, 'error' => $e->getMessage()]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => null,
                'message' => 'Could not reach Pathao: ' . $e->getMessage(),
            ];
        }

        $body = $response->json();

        if ($response->successful()) {
            return [
                'ok' => true,
                'status' => $response->status(),
                'body' => $body,
                'message' => null,
            ];
        }

        return [
            'ok' => false,
            'status' => $response->status(),
            'body' => $body ?? substr((string) $response->body(), 0, 300),
            'message' => 'Pathao answered HTTP ' . $response->status() . '.',
        ];
    }
}
