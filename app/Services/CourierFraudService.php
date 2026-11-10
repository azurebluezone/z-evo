<?php

namespace App\Services;

use App\Models\CourierFraudCheck;
use App\Models\CourierShipment;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourierFraudService
{
    /**
     * Pathao's merchant portal is a different host from the courier API.
     *
     * Courier bookings go to api-hermes.pathao.com; the delivery-history lookup
     * lives on the merchant portal and authenticates with the merchant's own
     * username/password, not the courier client credentials.
     */
    private const PATHAO_MERCHANT_BASE = 'https://merchant.pathao.com';

    /** Pathao tokens are short lived; refresh before they expire mid-checkout. */
    private const PATHAO_TOKEN_TTL_MINUTES = 50;

    /**
     * Check phone number fraud score using courier history and SteadFast API
     */
    public function checkPhone(string $phone, ?string $provider = null, int $cacheMinutes = 720): CourierFraudCheck
    {
        $phone = $this->normalizePhone($phone);
        $provider = $provider ? strtolower(trim($provider)) : null;

        // Check cache first
        $cached = CourierFraudCheck::query()
            ->where('phone', $phone)
            ->when($provider, fn ($q) => $q->where('provider', $provider))
            ->whereNotNull('checked_at')
            ->orderByDesc('checked_at')
            ->first();

        if ($cached && $cached->checked_at && $cached->checked_at->gt(Carbon::now()->subMinutes(max(5, $cacheMinutes)))) {
            return $cached;
        }

        /*
         * The courier's own record first, our local history second.
         *
         * checkPhoneFromCourierAPIs() was written and then never reached from
         * here: this method went straight to calculateFraudScore(), which only
         * counts OUR OWN shipments. So the richer signal the courier publishes -
         * including "what other merchants have reported" - was never collected,
         * and on a young shop every customer scored "unknown" simply because we
         * had never shipped to them before.
         *
         * Order matters:
         *   1. courier API  - a number WE also have, seen across every merchant.
         *   2. local history - our own shipments, works before any API is set up.
         *
         * The local path is not removed: if the courier is unconfigured, answers
         * 403 because ownership details are not confirmed, or sends a shape we do
         * not recognise, it returns null and we fall through, so a checkout is
         * never blocked by a fraud lookup.
         */
        $fromCourier = $this->checkPhoneFromCourierAPIs($phone);

        if ($fromCourier !== null) {
            $fromCourier->update([
                'checked_at' => Carbon::now(),
                'checked_by' => Auth::check() ? (int) Auth::id() : null,
            ]);

            return $fromCourier;
        }

        // Fall back to our own delivery history.
        $result = $this->calculateFraudScore($phone);

        return CourierFraudCheck::query()->create([
            'phone' => $phone,
            'provider' => $provider ?? 'internal',
            'success_rate_percent' => $result['success_rate_percent'],
            'risk_label' => $result['risk_label'],
            'raw_payload' => $result['raw_payload'],
            'checked_at' => Carbon::now(),
            'checked_by' => Auth::check() ? (int) Auth::id() : null,
        ]);
    }

    /**
     * Calculate fraud score based on courier delivery history
     */
    private function calculateFraudScore(string $phone): array
    {
        // Get all shipments for this phone number
        $shipments = CourierShipment::query()
            ->whereHas('order.shipping', function ($q) use ($phone) {
                $q->where('phone', 'like', '%' . $phone . '%')
                  ->orWhere('phone', 'like', '%' . substr($phone, -10) . '%');
            })
            ->get();

        if ($shipments->isEmpty()) {
            return [
                'success_rate_percent' => null,
                'risk_label' => 'unknown',
                'raw_payload' => [
                    'message' => 'No delivery history found for this phone number',
                    'total_orders' => 0,
                    'delivered' => 0,
                    'cancelled' => 0,
                    'pending' => 0,
                ],
            ];
        }

        $total = $shipments->count();
        $delivered = $shipments->filter(fn ($s) => $this->isDeliveredStatus($s->current_status))->count();
        $cancelled = $shipments->filter(fn ($s) => $this->isCancelledStatus($s->current_status))->count();
        $pending = $total - $delivered - $cancelled;

        // Calculate success rate (delivered / (delivered + cancelled))
        $completedOrders = $delivered + $cancelled;
        $successRate = $completedOrders > 0 ? (int) round(($delivered / $completedOrders) * 100) : null;

        $riskLabel = $this->determineRiskLabel($successRate, $total, $cancelled);

        return [
            'success_rate_percent' => $successRate,
            'risk_label' => $riskLabel,
            'raw_payload' => [
                'total_orders' => $total,
                'delivered' => $delivered,
                'cancelled' => $cancelled,
                'pending' => $pending,
                'success_rate_percent' => $successRate,
                'calculation_method' => 'courier_history_based',
                'last_shipment_date' => $shipments->max('last_synced_at')?->toDateTimeString(),
            ],
        ];
    }

    /**
     * Check if status is considered delivered
     */
    private function isDeliveredStatus(?string $status): bool
    {
        if (!$status) return false;
        $deliveredStatuses = ['delivered', 'partial_delivered', 'delivered_approval_pending', 'partial_delivered_approval_pending'];
        return in_array(strtolower($status), $deliveredStatuses);
    }

    /**
     * Check if status is considered cancelled
     */
    private function isCancelledStatus(?string $status): bool
    {
        if (!$status) return false;
        $cancelledStatuses = ['cancelled', 'cancelled_approval_pending', 'cancel', 'canceled'];
        return in_array(strtolower($status), $cancelledStatuses);
    }

    /**
     * Determine risk label based on success rate and history
     */
    private function determineRiskLabel(?int $successRate, int $totalOrders, int $cancelledOrders): string
    {
        if ($successRate === null) {
            return $totalOrders > 0 ? 'risky' : 'unknown';
        }

        // High cancellation rate indicates high risk
        if ($totalOrders > 0 && ($cancelledOrders / $totalOrders) > 0.5) {
            return 'high_risk';
        }

        if ($successRate < 70) {
            return 'high_risk';
        }
        if ($successRate < 85) {
            return 'risky';
        }
        return 'normal';
    }

    public function attachToOrder(Order $order, CourierFraudCheck $check, string $source): void
    {
        $order->risk_label = $check->risk_label;
        $order->risk_score_percent = $check->success_rate_percent;
        $order->risk_last_checked_at = $check->checked_at;
        $order->risk_source = $source;
        $order->risk_provider = $this->providerLabel($check);
        $order->save();
    }

    /**
     * Human label for the source that produced the score.
     *
     * A combined check stores `courier_apis` and keeps the individual answers
     * under raw_payload.api_data. The order row must show those names, because
     * "Pathao" and "our own shipments" are not the same evidence.
     */
    private function providerLabel(CourierFraudCheck $check): string
    {
        $payload = $check->raw_payload;

        if (is_array($payload)) {
            $apiData = $payload['api_data'] ?? null;

            if (is_array($apiData) && $apiData !== []) {
                return implode(' + ', array_map(
                    static fn (string $key): string => $key === 'steadfast' ? 'SteadFast' : ucfirst($key),
                    array_keys($apiData)
                ));
            }

            if (($payload['calculation_method'] ?? '') === 'courier_history_based') {
                return 'Local history';
            }
        }

        return match ((string) $check->provider) {
            'internal', 'internal_history' => 'Local history',
            'courier_apis' => 'Courier API',
            default => ucfirst((string) $check->provider),
        };
    }

    /**
     * Check phone from courier history (public method for sync)
     */
    public function checkPhoneFromCourierHistory(string $phone): ?CourierFraudCheck
    {
        $phone = $this->normalizePhone($phone);
        
        // Check cache first
        $cached = CourierFraudCheck::query()
            ->where('phone', $phone)
            ->whereNotNull('checked_at')
            ->orderByDesc('checked_at')
            ->first();

        if ($cached && $cached->checked_at && $cached->checked_at->gt(Carbon::now()->subMinutes(720))) {
            return $cached;
        }

        $result = $this->calculateFraudScore($phone);

        if ($result['raw_payload']['total_orders'] === 0) {
            return null; // No history found
        }

        return CourierFraudCheck::query()->create([
            'phone' => $phone,
            'provider' => 'internal_history',
            'success_rate_percent' => $result['success_rate_percent'],
            'risk_label' => $result['risk_label'],
            'raw_payload' => $result['raw_payload'],
            'checked_at' => Carbon::now(),
            'checked_by' => Auth::check() ? (int) Auth::id() : null,
        ]);
    }

    /**
     * Check phone from courier APIs (SteadFast and Pathao)
     */
    public function checkPhoneFromCourierAPIs(string $phone): ?CourierFraudCheck
    {
        $phone = $this->normalizePhone($phone);

        // Try SteadFast first
        $steadfastResult = $this->checkFromSteadFastAPI($phone);
        
        // Try Pathao
        $pathaoResult = $this->checkFromPathaoAPI($phone);

        // Combine results
        $totalDelivered = 0;
        $totalCancelled = 0;
        $totalPending = 0;
        $apiData = [];

        if ($steadfastResult) {
            $totalDelivered += $steadfastResult['delivered'] ?? 0;
            $totalCancelled += $steadfastResult['cancelled'] ?? 0;
            $totalPending += $steadfastResult['pending'] ?? 0;
            $apiData['steadfast'] = $steadfastResult;
        }

        if ($pathaoResult) {
            $totalDelivered += $pathaoResult['delivered'] ?? 0;
            $totalCancelled += $pathaoResult['cancelled'] ?? 0;
            $totalPending += $pathaoResult['pending'] ?? 0;
            $apiData['pathao'] = $pathaoResult;
        }

        $totalOrders = $totalDelivered + $totalCancelled + $totalPending;

        if ($totalOrders === 0) {
            return null; // No data from APIs
        }

        $completedOrders = $totalDelivered + $totalCancelled;
        $successRate = $completedOrders > 0 ? (int) round(($totalDelivered / $completedOrders) * 100) : null;

        $riskLabel = $this->determineRiskLabel($successRate, $totalOrders, $totalCancelled);

        return CourierFraudCheck::query()->create([
            'phone' => $phone,
            'provider' => 'courier_apis',
            'success_rate_percent' => $successRate,
            'risk_label' => $riskLabel,
            'raw_payload' => [
                'total_orders' => $totalOrders,
                'delivered' => $totalDelivered,
                'cancelled' => $totalCancelled,
                'pending' => $totalPending,
                'success_rate_percent' => $successRate,
                'calculation_method' => 'courier_apis',
                'api_data' => $apiData,
                // The order row reads this list to say "Pathao" / "SteadFast"
                // instead of the opaque storage value `courier_apis`.
                'providers' => array_keys($apiData),
            ],
            'checked_at' => Carbon::now(),
            'checked_by' => Auth::check() ? (int) Auth::id() : null,
        ]);
    }

    /**
     * Check phone from SteadFast API
     */
    /**
     * Ask SteadFast for a phone number's real delivery record.
     *
     * GET /fraud_check/score/{phone}
     *   "A customer's delivery record: how much of it arrived, how much came
     *    back, and what other merchants have reported."
     *
     * ---------------------------------------------------------------------------
     * THIS REPLACES A "STEADFAST HAS NO PHONE API" SHORTCUT
     * ---------------------------------------------------------------------------
     * The method previously read:
     *
     *     // SteadFast doesn't have a direct phone search API
     *     // So we search from our LOCAL shipments that match this phone
     *
     * and then recounted our own courier_shipments rows. That is a far weaker
     * signal than the endpoint provides:
     *
     *   - It only knows what WE sent. A buyer who never received one parcel from
     *     us looks brand new here, while SteadFast has the record from every
     *     other merchant that has shipped to them.
     *   - For a new shop there are no local rows at all, so every customer
     *     scored "unknown" - which is the opposite of what a fraud check is for.
     *   - The real endpoint also reports "what other merchants have reported",
     *     i.e. chargebacks and abuse flags we cannot see from inside.
     *
     * ---------------------------------------------------------------------------
     * WHY THE PARSER IS DEFENSIVE
     * ---------------------------------------------------------------------------
     * The account this runs on is currently INACTIVE, so the endpoint answers
     * 403 and the successful response shape has not been observed. Rather than
     * guess one exact key and silently score 0 for every real customer, the
     * parser accepts the several shapes the same numbers commonly arrive under
     * and returns null if none of them are present - which makes the caller
     * fall back to the local history rather than record a wrong score.
     *
     * NOTE this is `NOT_TESTED` against a real 200 payload. It is tested only in
     * the sense that a 403 degrades to null and the caller falls back.
     */
    private function checkFromSteadFastAPI(string $phone): ?array
    {
        try {
            $config = \App\Models\Courierapi::where('type', 'steadfast')
                ->where('status', 1)
                ->first();

            if (!$config || !$config->isConfigured()) {
                return null;
            }

            $base = rtrim((string) $config->url, '/');
            $base = preg_replace('#/(create_order|get_balance|fraud_check/score/.*)$#', '', $base) ?? $base;

            $url = $base . '/fraud_check/score/' . rawurlencode($phone);

            $response = Http::withHeaders([
                'Api-Key'    => (string) $config->api_key,
                'Secret-Key' => (string) $config->secret_key,
                'Accept'     => 'application/json',
            ])->timeout(20)->get($url);

            $status = $response->status();

            // 403 here is the normal state for an account whose ownership
            // details are not confirmed yet. It is not an error worth logging
            // loudly on every checkout - it is a known, actionable state.
            if ($status === 403 || $status === 401) {
                Log::info('Courier fraud score unavailable', [
                    'provider' => 'steadfast',
                    'status' => $status,
                ]);

                return null;
            }

            if (! $response->successful()) {
                return null;
            }

            $body = $response->json();

            if (! is_array($body)) {
                return null;
            }

            $parsed = $this->parseCourierFraudScore($body);

            if ($parsed === null) {
                return null;
            }

            $parsed['provider'] = 'steadfast';

            return $parsed;
        } catch (\Throwable $e) {
            Log::error('SteadFast fraud check failed', [
                'error' => $e->getMessage(),
                'phone' => $phone,
            ]);

            return null;
        }
    }

    /**
     * Pull delivered / cancelled / total out of whatever shape the courier used.
     *
     * Returns null when nothing recognisable is present, which is what makes
     * "unknown" honest rather than a fabricated zero.
     */
    private function parseCourierFraudScore(array $body): ?array
    {
        // The count can sit at the top level, under `data`, or under `score`.
        $node = $body;
        foreach (['data', 'score', 'result'] as $wrapper) {
            if (isset($node[$wrapper]) && is_array($node[$wrapper])) {
                $node = $node[$wrapper] + $node;   // keep outer keys as a fallback
            }
        }

        $total = $this->firstNumber($node, ['total', 'total_orders', 'total_parcels', 'total_orders_received']);

        // An explicit delivered/cancelled pair is enough on its own.
        $delivered = $this->firstNumber($node, ['delivered', 'delivered_count', 'total_delivered', 'successful']);
        $cancelled = $this->firstNumber($node, ['cancelled', 'canceled', 'cancelled_count', 'total_cancelled', 'returned']);

        if ($delivered === null && $cancelled === null && $total === null) {
            return null;
        }

        $delivered ??= 0;
        $cancelled ??= 0;

        if ($total === null) {
            $total = $delivered + $cancelled;
        }

        return [
            'total'     => (int) $total,
            'delivered' => (int) $delivered,
            'cancelled' => (int) $cancelled,
            'pending'   => max(0, (int) $total - (int) $delivered - (int) $cancelled),
            // Kept so the admin screen can show WHY, not just a number.
            'score_payload' => $body,
        ];
    }

    /** First present, numeric value among a set of candidate keys. */
    private function firstNumber(array $node, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (isset($node[$key]) && is_numeric($node[$key])) {
                return (int) $node[$key];
            }
        }

        return null;
    }

    /**
     * Ask Pathao's merchant portal for this phone number's delivery record.
     *
     * POST /api/v1/login        {username, password}  -> access_token
     * POST /api/v1/user/success {phone}               -> data.customer
     *
     * The response carries `successful_delivery` and `total_delivery`; the
     * cancelled count is the difference. That is a cross-merchant signal: it
     * knows about a buyer who never ordered from this shop, which the local
     * `courier_shipments` table cannot.
     *
     * Failure policy: every error returns null and the caller falls back to
     * local history. A checkout must never be blocked because Pathao is down,
     * rate limiting, or holding an unconfirmed merchant account.
     */
    private function checkFromPathaoAPI(string $phone): ?array
    {
        try {
            $config = \App\Models\Courierapi::where('type', 'pathao')
                ->where('status', 1)
                ->first();

            // The merchant portal needs the merchant login. The courier
            // client_id/client_secret pair is for booking parcels and cannot
            // authenticate this endpoint.
            if (! $config || ! filled($config->username) || ! filled($config->password)) {
                return null;
            }

            $token = $this->pathaoMerchantToken($config);

            if ($token === null) {
                return null;
            }

            $response = $this->pathaoSuccessRequest($phone, $token);

            // A cached token can be revoked before its TTL. Refresh once and
            // retry rather than scoring the customer as "unknown" all day.
            if ($response->status() === 401) {
                $token = $this->pathaoMerchantToken($config, true);

                if ($token === null) {
                    return null;
                }

                $response = $this->pathaoSuccessRequest($phone, $token);
            }

            if (! $response->successful()) {
                Log::info('Pathao fraud score unavailable', [
                    'status' => $response->status(),
                    'phone' => $phone,
                ]);

                return null;
            }

            $successful = (int) $response->json('data.customer.successful_delivery', 0);
            $total = (int) $response->json('data.customer.total_delivery', 0);

            // Zeros mean Pathao has no record. Returning a score of 0/0 would
            // be a fabricated "0% success"; let the local history answer.
            if ($total <= 0 && $successful <= 0) {
                return null;
            }

            $successful = max(0, min($successful, $total));
            $cancelled = max(0, $total - $successful);

            return [
                'provider' => 'pathao',
                'total' => $total,
                'delivered' => $successful,
                'cancelled' => $cancelled,
                'pending' => 0,
                'source' => 'merchant_api',
                'score_payload' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('Pathao API check failed', [
                'error' => $e->getMessage(),
                'phone' => $phone,
            ]);

            return null;
        }
    }

    /**
     * Cached merchant token. One login per 50 minutes instead of one per
     * checkout, and a forced refresh on a 401.
     */
    private function pathaoMerchantToken(\App\Models\Courierapi $config, bool $force = false): ?string
    {
        $cacheKey = 'courier.pathao.merchant_token.' . $config->id;

        if (! $force) {
            $cached = Cache::get($cacheKey);

            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $response = Http::acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->post(self::PATHAO_MERCHANT_BASE . '/api/v1/login', [
                'username' => (string) $config->username,
                'password' => (string) $config->password,
            ]);

        if (! $response->successful()) {
            Log::info('Pathao merchant login unavailable', [
                'status' => $response->status(),
            ]);

            return null;
        }

        $token = $response->json('access_token')
            ?? $response->json('data.access_token');

        if (! is_string($token) || trim($token) === '') {
            Log::warning('Pathao merchant login returned no access token');

            return null;
        }

        $token = trim($token);

        Cache::put($cacheKey, $token, now()->addMinutes(self::PATHAO_TOKEN_TTL_MINUTES));

        return $token;
    }

    /** The one place that knows the phone lookup request shape. */
    private function pathaoSuccessRequest(string $phone, string $token)
    {
        return Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->post(self::PATHAO_MERCHANT_BASE . '/api/v1/user/success', [
                'phone' => $phone,
            ]);
    }

    public function checkAndAttachToOrder(Order $order, string $phone, ?string $provider = null, string $source = 'checkout', int $cacheMinutes = 720): ?CourierFraudCheck
    {
        try {
            $check = $this->checkPhone($phone, $provider, $cacheMinutes);
            $this->attachToOrder($order, $check, $source);
            return $check;
        } catch (\Throwable $e) {
            Log::error('Fraud check failed', ['error' => $e->getMessage(), 'phone' => $phone]);
            return null;
        }
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = preg_replace('/[^0-9\+]/', '', $phone);
        if (str_starts_with($phone, '88') && strlen($phone) > 11) {
            $phone = ltrim($phone, '88');
        }
        if (str_starts_with($phone, '+88')) {
            $phone = substr($phone, 3);
        }
        return $phone;
    }
}
