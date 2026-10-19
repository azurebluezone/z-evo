<?php

namespace App\Services;

use App\Models\Courierapi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * One live connection test per courier, shared by the CLI command and the admin
 * screen.
 *
 * ---------------------------------------------------------------------------
 * WHY IT IS A SERVICE AND NOT CODE INSIDE A CONTROLLER
 * ---------------------------------------------------------------------------
 * "Is my courier configured correctly?" was answered in three different places -
 * CourierCheckCommand, CourierSettingsController::testSteadfastConnection() and
 * ::testPathaoConnection() - each with its own idea of the base URL, the auth
 * headers and what counts as success. They had already drifted: the settings
 * screen appended /aladdin unconditionally (wrong for Pathao's sandbox host),
 * and the command probed /get_balance with no way to tell "the host is down"
 * from "the key is revoked".
 *
 * This returns a structured, step-by-step result so the admin UI, the CLI and
 * any future code all report the same thing.
 *
 * ---------------------------------------------------------------------------
 * ORDER OF THE PROBES
 * ---------------------------------------------------------------------------
 * SteadFast is asked in two steps, deliberately:
 *
 *   1. GET /ping - needs NO credentials. If this fails, the base URL is wrong or
 *      the host is unreachable, and nothing later can succeed. Reporting the
 *      keys as "rejected" at that point would send the admin chasing the wrong
 *      problem.
 *   2. GET /get_balance - with the keys. 401 means the credentials are wrong.
 *      403 means the credentials are FINE but the account is inactive (KYC or
 *      email verification outstanding), which is an entirely different action.
 */
class CourierConnectionTester
{
    /** Bounded so an unreachable courier cannot hang an admin request. */
    private const TIMEOUT_SECONDS = 15;

    /**
     * Run the test.
     *
     * @return array{
     *     ok: bool,
     *     provider: string,
     *     steps: array<int, array{label: string, ok: bool, detail: ?string}>,
     *     message: string
     * }
     */
    public function test(Courierapi $courier): array
    {
        return match ($courier->type) {
            'steadfast' => $this->testSteadfast($courier),
            'pathao'    => $this->testPathao($courier),
            default     => [
                'ok' => false,
                'provider' => (string) $courier->type,
                'steps' => [],
                'message' => 'Unknown courier type: ' . (string) $courier->type,
            ],
        };
    }

    /**
     * @return array{ok: bool, provider: string, steps: array<int, array{label: string, ok: bool, detail: ?string}>, message: string}
     */
    private function testSteadfast(Courierapi $courier): array
    {
        $steps = [];

        if (! $courier->isConfigured()) {
            return [
                'ok' => false,
                'provider' => 'steadfast',
                'steps' => [[
                    'label' => 'Credentials present',
                    'ok' => false,
                    'detail' => 'Base URL, API key and secret key are all required.',
                ]],
                'message' => 'Credentials are incomplete.',
            ];
        }

        $base = $this->steadfastBase((string) $courier->url);

        // --- Step 1: is the service reachable at all? -------------------------
        $ping = $this->ping($base);
        $steps[] = [
            'label'  => 'Service reachable (GET /ping, no key needed)',
            'ok'     => $ping['ok'],
            'detail' => $ping['detail'],
        ];

        if (! $ping['ok']) {
            return [
                'ok' => false,
                'provider' => 'steadfast',
                'steps' => $steps,
                'message' => 'SteadFast could not be reached. Check the base URL and this server\'s outbound access.',
            ];
        }

        // --- Step 2: are the keys accepted? -----------------------------------
        $balance = $this->steadfastBalance($base, $courier);

        $steps[] = [
            'label'  => 'Credentials accepted (GET /get_balance)',
            'ok'     => $balance['ok'],
            'detail' => $balance['detail'],
        ];

        return [
            'ok' => $balance['ok'],
            'provider' => 'steadfast',
            'steps' => $steps,
            'message' => $balance['message'],
        ];
    }

    /**
     * @return array{ok: bool, provider: string, steps: array<int, array{label: string, ok: bool, detail: ?string}>, message: string}
     */
    private function testPathao(Courierapi $courier): array
    {
        if (! $courier->isConfigured()) {
            return [
                'ok' => false,
                'provider' => 'pathao',
                'steps' => [[
                    'label' => 'Credentials present',
                    'ok' => false,
                    'detail' => 'Base URL, client id, client secret, username and password are all required.',
                ]],
                'message' => 'Credentials are incomplete.',
            ];
        }

        // Pathao issues a bearer token first; everything else depends on it.
        $client = new PathaoClient($courier);
        $token  = $client->refreshToken();

        $steps = [[
            'label'  => 'Access token issued (POST ' . $client->url('api/v1/issue-token') . ')',
            'ok'     => $token !== null,
            'detail' => $token !== null
                ? 'A bearer token was returned and stored.'
                : (string) ($client->lastError ?? 'No token returned.'),
        ]];

        return [
            'ok' => $token !== null,
            'provider' => 'pathao',
            'steps' => $steps,
            'message' => $token !== null
                ? 'Pathao accepted the credentials.'
                : 'Pathao refused the credentials.',
        ];
    }

    /**
     * Reduce any documented SteadFast base shape to the api/v1 root.
     */
    private function steadfastBase(string $url): string
    {
        $url = rtrim(trim($url), '/');
        $url = preg_replace('#/(create_order|get_balance|ping)$#', '', $url) ?? $url;

        return $url;
    }

    /**
     * @return array{ok: bool, detail: string}
     */
    private function ping(string $base): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->get($base . '/ping');
        } catch (\Throwable $e) {
            Log::warning('SteadFast ping failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'detail' => 'Unreachable: ' . $e->getMessage()];
        }

        $body = $response->json();

        if ($response->successful() && is_array($body) && ($body['message'] ?? '') === 'pong') {
            $service = trim(($body['service'] ?? 'steadfast-api') . ' ' . ($body['version'] ?? ''));

            return ['ok' => true, 'detail' => 'pong from ' . $service];
        }

        return [
            'ok' => false,
            'detail' => 'Answered HTTP ' . $response->status() . ' but not the expected pong.',
        ];
    }

    /**
     * @return array{ok: bool, detail: string, message: string}
     */
    private function steadfastBalance(string $base, Courierapi $courier): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->withHeaders([
                    'Api-Key'    => (string) $courier->api_key,
                    'Secret-Key' => (string) $courier->secret_key,
                ])
                ->get($base . '/get_balance');
        } catch (\Throwable $e) {
            Log::warning('SteadFast balance check failed', ['error' => $e->getMessage()]);

            return [
                'ok' => false,
                'detail' => 'Unreachable: ' . $e->getMessage(),
                'message' => 'SteadFast could not be reached.',
            ];
        }

        $status = $response->status();
        $body   = $response->json();
        $text   = is_array($body) ? trim((string) ($body['message'] ?? '')) : '';

        if ($status === 200 && is_array($body) && array_key_exists('current_balance', $body)) {
            return [
                'ok' => true,
                'detail' => 'Accepted. Account balance: ' . $body['current_balance'],
                'message' => 'Credentials accepted. The account is usable.',
            ];
        }

        if ($status === 401) {
            return [
                'ok' => false,
                'detail' => 'HTTP 401 - the API key or secret key is wrong.' . ($text !== '' ? ' ' . $text : ''),
                'message' => 'SteadFast rejected the credentials (401).',
            ];
        }

        // 403 is not a credentials problem: SteadFast answers it when the account
        // itself is inactive, e.g. ownership/KYC details or email unverified.
        // Reporting that as "bad key" would send the admin down the wrong path.
        if ($status === 403) {
            return [
                'ok' => false,
                'detail' => 'HTTP 403 - the credentials are valid but the ACCOUNT is inactive. '
                    . 'Complete the ownership information and verify the email on SteadFast\'s side.'
                    . ($text !== '' ? ' Courier said: ' . $text : ''),
                'message' => 'The account is inactive, so parcels cannot be booked yet.',
            ];
        }

        return [
            'ok' => false,
            'detail' => 'HTTP ' . $status . ($text !== '' ? ' - ' . $text : ''),
            'message' => 'SteadFast answered HTTP ' . $status . '.',
        ];
    }
}
