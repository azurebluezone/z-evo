<?php

namespace App\Services;

use App\Models\SmsGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The one place an SMS leaves this application.
 *
 * ---------------------------------------------------------------------------
 * WHAT WAS WRONG BEFORE
 * ---------------------------------------------------------------------------
 * Four call sites in CustomerController each built the same payload and then did
 * this:
 *
 *     $ch = curl_init();
 *     curl_setopt($ch, CURLOPT_URL, $url);
 *     curl_setopt($ch, CURLOPT_POST, 1);
 *     curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
 *     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 *     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   // <-- see below
 *     $response = curl_exec($ch);
 *     curl_close($ch);
 *
 * Four separate defects, all of them invisible from the browser:
 *
 *  1. `$response` was never read. A rejected send - wrong API key, unapproved
 *     sender id, no balance - returned whatever the gateway felt like saying and
 *     the request was thrown away. The screen still printed "Resend code send
 *     successfully". That is the whole reason an SMS can disappear without a
 *     single error anywhere: nothing in the application ever asked whether it
 *     had been accepted.
 *
 *  2. No CURLOPT_TIMEOUT (nor CONNECTTIMEOUT). A gateway that accepts the
 *     connection and then stalls holds the PHP worker until the default socket
 *     timeout. Checkout and OTP screens both call this synchronously.
 *
 *  3. CURLOPT_SSL_VERIFYPEER = false disabled certificate verification. The
 *     payload carries an OTP, so this made the codes - and anything else sent -
 *     interceptable by anyone able to sit on the path. There is no reason to
 *     keep it: these gateways have valid certificates.
 *
 *  4. Failures were not logged, so "no SMS arrived" could not be investigated
 *     after the fact at all.
 *
 * ---------------------------------------------------------------------------
 * WHAT THIS RETURNS
 * ---------------------------------------------------------------------------
 * An array, never an exception:
 *
 *   ['sent' => bool, 'reason' => ?string, 'status' => ?int, 'body' => ?string]
 *
 * Callers are expected to branch on `sent`. The old code could not branch
 * because it had nothing to branch on.
 *
 * ---------------------------------------------------------------------------
 * THE BODY HEURISTIC
 * ---------------------------------------------------------------------------
 * Many Bangladeshi gateways answer HTTP 200 with an error string in the body
 * ("Invalid api key", "Insufficient balance"). A status code alone therefore is
 * not enough to call a send successful, so a 2xx whose body contains an obvious
 * failure word is also treated as a failure. This is a heuristic and is not a
 * substitute for reading the gateway's documentation - but silently accepting a
 * 200 that says "failed" is strictly worse, and the raw body is returned either
 * way so the admin screen can show exactly what came back.
 *
 * The payload keys (api_key, contacts, type, senderid, msg) are kept exactly as
 * they were, because they match the gateway this shop is configured for; they
 * are sent form-encoded, which every gateway in this family accepts.
 */
class SmsGatewayService
{
    /** Bounded so a stalled gateway cannot hold a checkout or OTP request. */
    private const TIMEOUT_SECONDS = 10;

    /**
     * Send one SMS through the enabled gateway.
     *
     * @param  string       $phone    recipient number, as stored
     * @param  string       $message  the body text
     * @param  string|null  $scope    'order' or 'forget_pass' to pick a purpose-specific
     *                                gateway, null for any enabled gateway
     * @return array{sent: bool, reason: ?string, gateway: ?string, status: ?int, body: ?string}
     */
    public function send(string $phone, string $message, ?string $scope = null): array
    {
        $gateway = $this->gateway($scope);

        if (! $gateway) {
            Log::info('SMS skipped: no enabled gateway for this scope.', ['scope' => $scope]);

            return $this->result(
                false,
                'no enabled SMS gateway is configured'
                    . ($scope ? " for '{$scope}'" : ''),
                $gateway,
                null,
                null
            );
        }

        $url = trim((string) $gateway->url);

        if ($url === '') {
            Log::warning('SMS skipped: gateway has an empty URL.', ['gateway_id' => $gateway->id]);

            return $this->result(false, 'the SMS gateway has an empty URL', $gateway, null, null);
        }

        $payload = [
            'api_key'  => (string) $gateway->api_key,
            'contacts' => $phone,
            'type'     => 'text',
            'senderid' => (string) $gateway->serderid,
            'msg'      => $message,
        ];

        try {
            // Http (Guzzle) verifies TLS certificates by default, which is the
            // opposite of the CURLOPT_SSL_VERIFYPEER=false this replaces.
            $response = Http::asForm()
                ->timeout(self::TIMEOUT_SECONDS)
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('SMS send failed before a response arrived.', [
                'gateway_id' => $gateway->id,
                'error'      => $e->getMessage(),
            ]);

            return $this->result(
                false,
                'could not reach the SMS gateway: ' . $e->getMessage(),
                $gateway,
                null,
                null
            );
        }

        $body   = substr((string) $response->body(), 0, 500);
        $status = $response->status();

        if (! $response->successful()) {
            Log::warning('SMS gateway refused the message.', [
                'gateway_id' => $gateway->id,
                'status'     => $status,
                'body'       => $body,
            ]);

            return $this->result(false, 'gateway answered HTTP ' . $status, $gateway, $status, $body);
        }

        // A 200 is not proof: see the note on the heuristic in the class docblock.
        if (preg_match('/\b(error|invalid|failed|failure|insufficient|denied|unauthori[sz]ed|blocked)\b/i', $body) === 1) {
            Log::warning('SMS gateway answered 2xx but the body reports a failure.', [
                'gateway_id' => $gateway->id,
                'status'     => $status,
                'body'       => $body,
            ]);

            return $this->result(
                false,
                'gateway answered HTTP ' . $status . ' but reported: ' . $body,
                $gateway,
                $status,
                $body
            );
        }

        Log::info('SMS accepted by the gateway.', [
            'gateway_id' => $gateway->id,
            'status'     => $status,
        ]);

        return $this->result(true, null, $gateway, $status, $body);
    }

    /**
     * The enabled gateway for a scope, or null.
     *
     * The `forget_pass` / `order` flags exist so a shop can route different
     * message types through different accounts; when the flag column has no row
     * switched on, no gateway is used for that purpose. Falling back to "any
     * enabled gateway" would send password codes through an order-only account,
     * which is why it is not done.
     */
    private function gateway(?string $scope): ?SmsGateway
    {
        $query = SmsGateway::query()->where('status', 1);

        if ($scope === 'order') {
            $query->where('order', 1);
        } elseif ($scope === 'forget_pass') {
            $query->where('forget_pass', 1);
        }

        return $query->first();
    }

    /**
     * @return array{sent: bool, reason: ?string, gateway: ?string, status: ?int, body: ?string}
     */
    private function result(bool $sent, ?string $reason, ?SmsGateway $gateway, ?int $status, ?string $body): array
    {
        return [
            'sent'    => $sent,
            'reason'  => $reason,
            'gateway' => $gateway?->id ? (string) $gateway->id : null,
            'status'  => $status,
            'body'    => $body,
        ];
    }
}
