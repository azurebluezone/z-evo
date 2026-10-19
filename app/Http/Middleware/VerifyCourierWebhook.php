<?php

namespace App\Http\Middleware;

use App\Models\Courierapi;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates an inbound courier webhook.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS EXISTS
 * ---------------------------------------------------------------------------
 * Before this middleware, POST /api/pathao-webhook had NO authentication at
 * all. Anyone who could reach the URL could POST
 *
 *     {"merchant_order_id":"INV-1","order_status":"delivered"}
 *
 * and move a real order to "delivered" — corrupting delivery records, COD
 * reconciliation and the fraud score that the admin order screen displays.
 *
 * SteadFast documents that its callbacks carry a Bearer token ("SteadFast sends
 * real-time status updates to your callback URL using a Bearer token for
 * authentication"), so the shared secret is compared against the
 * Authorization header.
 *
 * ---------------------------------------------------------------------------
 * HOW A REQUEST IS AUTHENTICATED
 * ---------------------------------------------------------------------------
 * 1. The courier is identified from the route: `steadfast` or `pathao`.
 * 2. Its row is read from courier_apis and its webhook_token compared to the
 *    supplied bearer token using hash_equals() - constant time, so the
 *    comparison does not leak the secret by timing.
 * 3. The courier must also be active. A disabled courier is a deliberate
 *    off-switch; it should not be able to write.
 *
 * ---------------------------------------------------------------------------
 * IF NO TOKEN IS CONFIGURED
 * ---------------------------------------------------------------------------
 * The request is REJECTED (401), not allowed through.
 *
 * This matters: a courier row is seeded with status = 0 and a token already
 * generated, so "no token configured" only happens if an admin deliberately
 * cleared it. Falling back to "accept anything" in that case would turn a
 * misconfiguration into an open endpoint, which is the exact bug this
 * middleware exists to close.
 *
 * ---------------------------------------------------------------------------
 * RATE LIMITING
 * ---------------------------------------------------------------------------
 * A fixed-window counter keyed on courier + client IP caps a single source at
 * 60 requests per minute, matching SteadFast's own outbound rate limit. A
 * webhook storm (courier retry loop, or someone fuzzing the URL) is throttled
 * before it reaches the database.
 *
 * The cache store is whatever the app is configured for; the key includes the
 * IP so one abusive host cannot lock out the real courier.
 */
class VerifyCourierWebhook
{
    /** Requests allowed per minute, per courier + IP. */
    private const RATE_LIMIT = 60;

    /** Seconds the rate-limit window lasts. */
    private const RATE_WINDOW = 60;

    public function handle(Request $request, Closure $next, string $courier = ''): Response
    {
        $courier = strtolower(trim($courier));

        if ($courier === '') {
            return $this->deny($request, 'unknown_courier', 'No courier on this endpoint.');
        }

        if (! $this->rateLimitAllows($request, $courier)) {
            return response()->json([
                'ok' => false,
                'message' => 'Too many requests',
            ], 429);
        }

        $config = Courierapi::where('type', $courier)->first();

        if (! $config) {
            return $this->deny($request, $courier, 'Courier is not configured.');
        }

        if (! $config->status) {
            return $this->deny($request, $courier, 'Courier is disabled.', 403);
        }

        $expected = (string) ($config->webhook_token ?? '');
        $supplied = $this->bearerToken($request);

        if ($expected === '') {
            // Deliberately a 401, not a pass. See the class header.
            Log::warning('Courier webhook rejected: no token configured', [
                'courier' => $courier,
            ]);

            return $this->deny($request, $courier, 'Webhook token is not configured.', 401);
        }

        if ($supplied === '' || ! hash_equals($expected, $supplied)) {
            Log::warning('Courier webhook rejected: bad token', [
                'courier' => $courier,
                'ip' => $request->ip(),
            ]);

            return $this->deny($request, $courier, 'Invalid webhook token.', 401);
        }

        return $next($request);
    }

    /**
     * Pull the bearer credential out of the Authorization header.
     *
     * Header parsing is intentionally forgiving about case because several
     * gateways and PHP SAPI combinations vary it, but the scheme itself has to
     * match - "Token abc" or a bare token with no scheme is not accepted.
     */
    private function bearerToken(Request $request): string
    {
        $header = (string) $request->header('Authorization', '');

        if ($header === '') {
            // Fall back to a query/body credential for couriers whose webhook
            // configurator cannot set custom headers.
            $fallback = (string) $request->input('webhook_token', '');
            return trim($fallback);
        }

        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches) === 1) {
            return trim($matches[1]);
        }

        return '';
    }

    private function rateLimitAllows(Request $request, string $courier): bool
    {
        $key = 'courier-webhook:' . $courier . ':' . ($request->ip() ?? 'unknown');

        try {
            $hits = (int) Cache::get($key, 0);

            if ($hits >= self::RATE_LIMIT) {
                return false;
            }

            // add() with the window keeps a fixed window per key; the first hit
            // of a window creates the entry, later hits just increment.
            if (! Cache::has($key)) {
                Cache::put($key, 1, self::RATE_WINDOW);
            } else {
                Cache::increment($key);
            }

            return true;
        } catch (\Throwable $e) {
            // A cache outage must not silently disable authentication, and it
            // must not silently disable the webhook either. The token check
            // below is the real control, so allow the request through to it.
            Log::warning('Courier webhook rate limiter unavailable', [
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }

    private function deny(Request $request, string $courier, string $message, int $status = 401): Response
    {
        Log::info('Courier webhook denied', [
            'courier' => $courier,
            'reason' => $message,
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);

        return response()->json([
            'ok' => false,
            'message' => $message,
        ], $status);
    }
}
