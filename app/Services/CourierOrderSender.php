<?php

namespace App\Services;

use App\Models\CourierShipment;
use App\Models\Courierapi;
use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Sends an order to a courier and records the consignment.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS EXISTS RATHER THAN MORE inline code in OrderController
 * ---------------------------------------------------------------------------
 * Two call sites already POSTed to /create_order (OrderController@process and
 * @bulk_courier), with this payload:
 *
 *     'recipient_name'    => $order->shipping->name    ?? 'InboxHat',
 *     'recipient_phone'   => $order->shipping->phone   ?? '01750578495',
 *     'recipient_address' => $order->shipping->address ?? '01750578495',
 *
 * Those fallbacks are dangerous. If the shipping row is missing or a field is
 * blank, the order is not rejected - a real parcel is created addressed to a
 * hardcoded number that has nothing to do with the customer, and SteadFast
 * charges for it. A silent wrong delivery is far worse than a refusal.
 *
 * So the builder here REQUIRES the three delivery fields and refuses when any
 * is blank, naming the missing field so the admin knows what to fix.
 *
 * It also adds what the inline versions lacked:
 *   - a request timeout, so a hung courier API cannot hang the admin screen,
 *   - a try/catch that turns a network failure into a readable result instead
 *     of a 500,
 *   - an isReady() check, so an unconfigured courier fails with an explanation
 *     rather than an upstream 401,
 *   - a structured result object so the UI can show success or a real reason.
 *
 * ---------------------------------------------------------------------------
 * DOCUMENTED API SHAPE (SteadFast, per official docs)
 * ---------------------------------------------------------------------------
 *   POST {base}/create_order
 *   Headers: Api-Key, Secret-Key, Content-Type: application/json
 *   Required: recipient_name, recipient_phone, recipient_address, cod_amount
 *   Optional: note, item_description, recipient_email, alternative_phone,
 *             total_lot, delivery_type (0 home / 1 point)
 *   Rate limit: 60 requests per minute
 *
 * The response nests the useful fields under `consignment`:
 *   { "status": 200, "message": "...", "consignment": {
 *       "consignment_id": 1424107, "invoice": "Aa12-das4",
 *       "tracking_code": "15BAEB8A", "status": "in_review" } }
 *
 * ---------------------------------------------------------------------------
 * NO REAL CREDENTIALS WERE AVAILABLE
 * ---------------------------------------------------------------------------
 * courier_apis has never held a working key on this project, so live calls were
 * not made. Every branch below that depends on a real response is
 * NOT_TESTED; the validation, refusal and not-configured paths ARE tested.
 */
class CourierOrderSender
{
    /** Give up rather than leave the admin waiting on a hung courier API. */
    private const TIMEOUT_SECONDS = 30;

    public function __construct(private ?Client $client = null)
    {
        $this->client = $client ?: new Client([
            'timeout'         => self::TIMEOUT_SECONDS,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Build the exact payload that would be sent, without sending it.
     *
     * Used by the preview screen so an admin can see the recipient, address and
     * amount BEFORE a real consignment is created. Anything irreversible - a
     * charge, a parcel - should never be the first time the data is seen.
     *
     * @return array{ok:bool, message:?string, payload:?array, endpoint:?string}
     */
    public function preview(Order $order, string $provider): array
    {
        /*
         * B3: this sender speaks ONLY the SteadFast protocol (Api-Key /
         * Secret-Key headers, POST {url}/create_order). Pathao needs an OAuth
         * token + store lookup + a different payload, and its real flow lives in
         * OrderController::order_pathao_auto (bulk). Accepting 'pathao' here used
         * to build a SteadFast payload and POST it to the Pathao URL with empty
         * SteadFast headers - a guaranteed wrong-protocol failure once keys exist.
         * Refuse it honestly instead.
         */
        if ($provider === 'pathao') {
            return $this->fail(
                'Per-order Pathao sending is not supported by this screen. '
                . 'Use the bulk "Send to Pathao" action, which handles Pathao auth and stores.'
            );
        }

        $config = Courierapi::where('type', $provider)->first();

        if (! $config) {
            return $this->fail('This courier has not been set up yet.');
        }

        if (! $config->isReady()) {
            return $this->fail($this->notReadyReason($config));
        }

        $built = $this->buildPayload($order, $config);

        if (! $built['ok']) {
            return $built;
        }

        return [
            'ok'       => true,
            'message'  => null,
            'payload'  => $built['payload'],
            'endpoint' => $this->endpoint($config),
        ];
    }

    /**
     * Send one order to the courier and record the consignment.
     *
     * @return array{ok:bool, message:string, consignment_id:?string,
     *               tracking_code:?string, status:?string, payload:?array}
     */
    public function send(Order $order, string $provider, string $note = ''): array
    {
        $preview = $this->preview($order, $provider);

        if (! $preview['ok']) {
            return [
                'ok' => false,
                'message' => $preview['message'],
                'consignment_id' => null,
                'tracking_code' => null,
                'status' => null,
                'payload' => null,
            ];
        }

        $config = Courierapi::where('type', $provider)->firstOrFail();

        // Never create two consignments for one order by accident: a retry
        // after a timeout would otherwise bill the customer twice.
        $existing = CourierShipment::where('order_id', $order->id)
            ->where('provider', $provider)
            ->whereNotNull('consignment_id')
            ->where('consignment_id', '!=', '')
            ->first();

        if ($existing) {
            return [
                'ok' => false,
                'message' => 'This order already has a consignment with this courier ('
                    . $existing->consignment_id . '). Delete it first if you really need to resend.',
                'consignment_id' => $existing->consignment_id,
                'tracking_code' => $existing->tracking_code,
                'status' => $existing->current_status,
                'payload' => null,
            ];
        }

        $payload = $preview['payload'];

        if ($note !== '') {
            // The courier truncates note at 480 and strips {} ; < > $, so do it
            // here rather than let their copy differ from ours.
            $payload['note'] = $this->sanitise($note, 480);
        }

        try {
            $response = $this->client->post($preview['endpoint'], [
                'json' => $payload,
                'headers' => [
                    'Api-Key'       => (string) $config->api_key,
                    'Secret-Key'    => (string) $config->secret_key,
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
        } catch (\Throwable $e) {
            Log::error('Courier send failed', [
                'provider' => $provider,
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                // Deliberately vague to the admin but precise in the log: an
                // upstream outage is not something they can act on, and the
                // courier may not have created anything.
                'message' => 'Could not reach ' . $config->label() . '. Nothing was created - please try again.',
                'consignment_id' => null,
                'tracking_code' => null,
                'status' => null,
                'payload' => null,
            ];
        }

        if (! is_array($body)) {
            return [
                'ok' => false,
                'message' => $config->label() . ' returned an unreadable response. Check the API settings.',
                'consignment_id' => null,
                'tracking_code' => null,
                'status' => null,
                'payload' => null,
            ];
        }

        // The courier reports failures with a non-"success" message and often a
        // 200, so the message has to be inspected rather than trusting the
        // status code alone.
        $consignment = $body['consignment'] ?? $body['data'] ?? [];
        $consignmentId = trim((string) ($consignment['consignment_id'] ?? ''));
        $trackingCode  = trim((string) ($consignment['tracking_code'] ?? ''));

        if ($consignmentId === '') {
            $reason = trim((string) ($body['message'] ?? 'No consignment id was returned.'));

            return [
                'ok' => false,
                'message' => $config->label() . ' refused the order: ' . $reason,
                'consignment_id' => null,
                'tracking_code' => null,
                'status' => null,
                'payload' => $body,
            ];
        }

        $status = trim((string) ($consignment['status'] ?? 'created'));

        // Keep the consignment so the webhook can find this parcel again and so
        // support has the courier's own words on record.
        CourierShipment::updateOrCreate(
            [
                'provider' => $provider,
                'order_id' => (int) $order->id,
            ],
            [
                'merchant_order_id' => (string) $order->invoice_id,
                'consignment_id'    => $consignmentId,
                'tracking_code'     => $trackingCode !== '' ? $trackingCode : null,
                'current_status'    => $status,
                'raw_payload'       => $body,
                'last_synced_at'     => now(),
            ]
        );

        return [
            'ok' => true,
            'message' => 'Sent to ' . $config->label() . '. Consignment ' . $consignmentId
                . ($trackingCode !== '' ? ', tracking ' . $trackingCode : '') . '.',
            'consignment_id' => $consignmentId,
            'tracking_code'  => $trackingCode !== '' ? $trackingCode : null,
            'status'         => $status,
            'payload'        => $payload,
        ];
    }

    /**
     * The recipient data, or a refusal naming what is missing.
     *
     * No invented fallbacks - see the class header.
     */
    private function buildPayload(Order $order, Courierapi $config): array
    {
        $shipping = $order->shipping;

        $name    = trim((string) ($shipping->name ?? ''));
        $phone   = trim((string) ($shipping->phone ?? ''));
        $address = trim((string) ($shipping->address ?? ''));

        $missing = [];

        if ($name === '')    { $missing[] = 'recipient name'; }
        if ($phone === '')   { $missing[] = 'recipient phone'; }
        if ($address === '') { $missing[] = 'recipient address'; }

        if ($missing !== []) {
            return [
                'ok' => false,
                'message' => 'This order cannot be sent yet - missing ' . implode(', ', $missing)
                    . '. Fill it in on the order first.',
                'payload' => null,
            ];
        }

        // SteadFast documents recipient_phone as 11 digits.
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) === 13 && str_starts_with($digits, '880')) {
            $digits = '0' . substr($digits, 3);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0' . $digits;
        }

        if (! preg_match('/^01[3-9]\d{8}$/', $digits)) {
            return [
                'ok' => false,
                'message' => 'Recipient phone "' . $phone . '" is not a valid Bangladeshi mobile number, '
                    . 'so ' . $config->label() . ' would reject it.',
                'payload' => null,
            ];
        }

        // cod_amount must be a number >= 0.
        $cod = (float) $order->amount;

        if ($cod < 0) {
            $cod = 0.0;
        }

        /*
         * Length limits are the courier's documented ones, not guesses:
         *   invoice 100 | recipient_name 100 | recipient_phone 40
         *   recipient_address 490 | note 480
         *
         * These are TRUNCATED by SteadFast rather than refused, so sending an
         * over-long value silently loses its tail instead of erroring - which
         * is how a delivery address loses its house number without anyone
         * noticing until the rider cannot find it.
         */
        $payload = [
            'invoice'           => mb_substr((string) $order->invoice_id, 0, 100),
            'recipient_name'    => mb_substr($name, 0, 100),
            'recipient_phone'   => mb_substr($digits, 0, 40),
            'recipient_address' => mb_substr($address, 0, 490),
            'cod_amount'        => $cod,
        ];

        // Optional, only when we actually know them.
        if ($order->shipping?->area) {
            $payload['note'] = $this->sanitise(
                'Delivery area: ' . $order->shipping->area,
                480
            );
        }

        $details = $order->orderdetails ?? [];

        if ($details->isNotEmpty()) {
            $payload['item_description'] = mb_substr(
                $details->pluck('product_name')->filter()->unique()->take(3)->implode(', '),
                0, 200
            );
            $payload['total_lot'] = (int) $details->sum('qty');
        }

        // A guest account has a NULL email by design; never send "1" or "".
        $email = trim((string) ($shipping->customer?->email ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $payload['recipient_email'] = $email;
        }

        return ['ok' => true, 'message' => null, 'payload' => $payload];
    }

    /**
     * Make a free-text value safe for the courier.
     *
     * SteadFast documents that it replaces each of  { } ; < > $  with a SPACE
     * before storing, so what it holds back is not always what was sent. Doing
     * the same substitution here keeps the database copy and the courier's copy
     * in agreement - otherwise an address containing "&" reads back differently
     * in our order screen than it does on the courier's, and support cannot
     * tell which one the rider saw.
     *
     * Over-long values are truncated, because the courier truncates anyway and
     * doing it explicitly means the stored copy is the whole truth.
     */
    private function sanitise(string $value, int $limit): string
    {
        $value = preg_replace('/[{};<>$]/u', ' ', $value) ?? $value;
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        return mb_substr($value, 0, $limit);
    }

    /**
     * Full endpoint for create_order, whatever base URL the admin entered.
     */
    private function endpoint(Courierapi $config): string
    {
        $url = rtrim((string) $config->url, '/');

        return str_contains($url, '/create_order') ? $url : $url . '/create_order';
    }

    /**
     * Which credential is missing, so the admin is not left guessing.
     */
    private function notReadyReason(Courierapi $config): string
    {
        if ($config->type === 'pathao') {
            $missing = array_filter([
                'URL'           => $config->url,
                'client id'     => $config->client_id,
                'client secret' => $config->client_secret,
                'username'      => $config->username,
                'password'      => $config->password,
            ]);

            return $missing === []
                ? 'Pathao is switched off. Turn it on in Courier API Settings.'
                : 'Pathao is missing: ' . implode(', ', array_keys($missing))
                    . '. Fill them in and switch it on in Courier API Settings.';
        }

        $missing = array_filter([
            'URL'        => $config->url,
            'API key'    => $config->api_key,
            'secret key' => $config->secret_key,
        ]);

        return $missing === []
            ? 'SteadFast is switched off. Turn it on in Courier API Settings.'
            : 'SteadFast is missing: ' . implode(', ', array_keys($missing))
                . '. Fill them in and switch it on in Courier API Settings.';
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message, 'payload' => null, 'endpoint' => null];
    }
}
