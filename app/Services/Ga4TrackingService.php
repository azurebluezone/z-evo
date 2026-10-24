<?php

namespace App\Services;

use App\Models\Order;
use App\Support\Consent;
use App\Support\Ga4;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side GA4 sender (Measurement Protocol).
 *
 * Scope is deliberately narrow: **purchase only**. GA4 has no general
 * browser/server dedupe, so forwarding every event here would double every
 * page view. Purchases are the exception because GA4 deduplicates them by
 * `transaction_id`, which the browser tag and this sender both set to the
 * invoice number.
 *
 * The sender is off unless TRACKING_GA4_SERVER_SIDE is on *and* both the
 * measurement id and the API secret exist. It is a fallback for the visitor
 * whose browser tag was blocked, not a second analytics pipeline.
 *
 * Consent: analytics permission is required. First-party storage is not touched
 * here - it already happened in the caller, with the decision recorded.
 */
final class Ga4TrackingService
{
    public function isEnabled(): bool
    {
        return Ga4::serverSideEnabled();
    }

    /**
     * Send the purchase for an order. Returns true only when Google's endpoint
     * answered 2xx (and, in debug mode, reported no validation messages).
     * Never throws: a lost analytics event must not fail a paid order.
     */
    public function sendPurchase(Order $order): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (!Consent::analytics()) {
            Log::info('GA4 Measurement Protocol skipped: analytics consent not granted', [
                'order_id' => $order->id,
            ]);

            return false;
        }

        $items = [];

        foreach ($order->orderdetails ?? [] as $line) {
            $items[] = Ga4::item(
                $line->product_id ?? null,
                $line->product_name ?? null,
                $line->sale_price ?? 0,
                $line->qty ?? 1
            );
        }

        $payload = Ga4::purchasePayload([
            'id' => $order->id,
            'invoice_id' => $order->invoice_id,
            'amount' => $order->amount,
            'discount' => $order->discount,
            'shipping' => $order->shipping_charge,
            'currency' => 'BDT',
        ], $items, Ga4::clientId(), Ga4::sessionId());

        return $this->send($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(array $payload): bool
    {
        $measurementId = Ga4::measurementId();
        $apiSecret = Ga4::apiSecret();

        if ($measurementId === null || $apiSecret === null) {
            return false;
        }

        $debug = Ga4::debug();

        $url = Ga4::endpoint($debug)
            . '?measurement_id=' . urlencode($measurementId)
            . '&api_secret=' . urlencode($apiSecret);

        try {
            $response = Http::timeout(4)
                ->acceptJson()
                ->post($url, $payload);
        } catch (\Throwable $e) {
            // The request URL carries the API secret, and Guzzle puts the URI
            // into its exception message. Log the class only - never the body.
            Log::warning('GA4 Measurement Protocol request failed', [
                'exception' => get_class($e),
            ]);

            return false;
        }

        if (!$response->successful()) {
            Log::warning('GA4 Measurement Protocol rejected the request', [
                'status' => $response->status(),
            ]);

            return false;
        }

        if ($debug) {
            $messages = $response->json('validationMessages');

            if (is_array($messages) && $messages !== []) {
                Log::warning('GA4 validation messages', ['messages' => $messages]);

                return false;
            }
        }

        return true;
    }
}
