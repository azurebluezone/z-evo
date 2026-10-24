<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * GA4 configuration and payload building, kept free of HTTP and Eloquent so the
 * exact body we send can be validated against Google's own validation server in
 * a test (tests/tracking/ga4-validate.php).
 *
 * Two things matter for correctness:
 *
 *  - `client_id` should be the one gtag is already using, otherwise the server
 *    event creates a second user. It is read from the `_ga` cookie; when that
 *    cookie is missing (blocked, or no gtag yet) a stable id is derived from our
 *    own visitor_id so the same visitor keeps the same id across orders.
 *
 *  - GA4 does not deduplicate arbitrary events between the browser and the
 *    Measurement Protocol. It *does* deduplicate purchases that share a
 *    `transaction_id`, which is why the server-side sender is purchase-only and
 *    always uses the invoice number (see the "Minimize duplicate key events
 *    with transaction IDs" help page).
 */
final class Ga4
{
    public static function measurementId(): ?string
    {
        $id = config('tracking.ga4.measurement_id');

        return is_string($id) && trim($id) !== '' ? trim($id) : null;
    }

    public static function apiSecret(): ?string
    {
        $secret = config('tracking.ga4.api_secret');

        return is_string($secret) && trim($secret) !== '' ? trim($secret) : null;
    }

    /** Both credentials present and the sender explicitly switched on. */
    public static function serverSideEnabled(): bool
    {
        return (bool) config('tracking.ga4.server_side')
            && self::measurementId() !== null
            && self::apiSecret() !== null;
    }

    public static function debug(): bool
    {
        return (bool) config('tracking.ga4.debug');
    }

    /** The collection URL. `debug` swaps in the validation server. */
    public static function endpoint(bool $debug = false): string
    {
        $host = config('tracking.ga4.region') === 'eu'
            ? 'https://region1.google-analytics.com'
            : 'https://www.google-analytics.com';

        return $host . ($debug ? '/debug/mp/collect' : '/mp/collect');
    }

    /**
     * The GA4 client id: `<random>.<timestamp>`.
     *
     * `_ga` looks like `GA1.1.1234567890.1700000000`; the part after the second
     * dot is the client id. If it is absent the value is derived from
     * visitor_id, which is stable for a year, so a visitor who blocks Google
     * cookies still produces one coherent user instead of a new one per order.
     */
    public static function clientId(?Request $request = null): string
    {
        $request = $request ?? request();

        $ga = $request->cookie('_ga');

        if (is_string($ga) && preg_match('/^GA\d+\.\d+\.(\d+\.\d+)$/', $ga, $m) === 1) {
            return $m[1];
        }

        $visitorId = (string) ($request->attributes->get('visitor_id') ?: $request->cookie('visitor_id'));

        if ($visitorId === '') {
            // No visitor identity at all (CLI, blocked cookies, admin preview).
            // A random GA4-shaped id is honest here: there is nothing to stitch,
            // and a constant like "0.0" would pile every such order onto one
            // fake user in the reports.
            return random_int(100000000, 999999999) . '.' . time();
        }

        $hash = hash('sha256', 'ga4|' . $visitorId);

        return max(1, hexdec(substr($hash, 0, 8)) % 2147483647)
            . '.' . max(1, hexdec(substr($hash, 8, 8)) % 2147483647);
    }

    /**
     * GA4 wants a positive integer session id. Our session_id is a UUID, so it
     * is hashed into the 31-bit positive range. This is a derived id, not the
     * number the browser's gtag uses - documented in the impl record, and the
     * reason the Measurement Protocol path is purchase-only.
     */
    public static function sessionId(?Request $request = null): int
    {
        $request = $request ?? request();

        $sessionId = (string) ($request->attributes->get('session_id') ?: $request->cookie('session_id'));

        if ($sessionId === '') {
            return 0;
        }

        $value = crc32($sessionId) & 0x7fffffff;

        return $value > 0 ? $value : 1;
    }

    /**
     * @param  array{id?: mixed, invoice_id?: mixed, amount?: mixed, discount?: mixed, shipping?: mixed, currency?: mixed}  $order
     * @param  array<int, array{item_id?: string, item_name?: string, price?: float, quantity?: int}>  $items
     * @return array{client_id: string, events: array<int, array{name: string, params: array<string, mixed>}>}
     */
    public static function purchasePayload(array $order, array $items, string $clientId, int $sessionId = 0): array
    {
        $transactionId = (string) ($order['invoice_id'] ?? $order['id'] ?? '');

        $params = [
            // The dedupe key. GA4 drops a second purchase with the same
            // transaction_id, so the browser tag and this copy count once.
            'transaction_id' => $transactionId,
            'value' => round((float) ($order['amount'] ?? 0), 2),
            'currency' => (string) ($order['currency'] ?? 'BDT'),
            'items' => array_values($items),
            // Without this the event is not attached to any session, and a
            // purchase that arrived only server-side would be invisible in
            // session-scoped reports.
            'engagement_time_msec' => 100,
        ];

        $shipping = $order['shipping'] ?? null;
        if (is_numeric($shipping) && (float) $shipping > 0) {
            $params['shipping'] = round((float) $shipping, 2);
        }

        $discount = $order['discount'] ?? null;
        if (is_numeric($discount) && (float) $discount > 0) {
            $params['discount'] = round((float) $discount, 2);
        }

        if ($sessionId > 0) {
            $params['session_id'] = $sessionId;
        }

        return [
            'client_id' => $clientId,
            'events' => [[
                'name' => 'purchase',
                'params' => $params,
            ]],
        ];
    }

    /**
     * A single-item purchase line in GA4's `items` shape.
     *
     * @return array{item_id: string, item_name: string, price: float, quantity: int}
     */
    public static function item($productId, $productName, $price, $quantity = 1): array
    {
        return [
            'item_id' => (string) ($productId ?? ''),
            'item_name' => (string) ($productName ?? ''),
            'price' => round((float) ($price ?? 0), 2),
            'quantity' => max(1, (int) ($quantity ?? 1)),
        ];
    }
}
