<?php

/**
 * GA4 Measurement Protocol payload check (T3).
 *
 * Google's validation server (https://www.google-analytics.com/debug/mp/collect)
 * validates the *structure* of a payload and, per Google's own documentation,
 * does NOT validate `api_secret` or `measurement_id`. That is what makes this
 * test possible without an Analytics account: the exact body our service would
 * send is posted to Google's server with placeholder credentials, and the
 * response must contain no validation messages.
 *
 * Run:  php tests/tracking/ga4-validate.php
 * Exit: 0 = every check passed, 1 = at least one check failed.
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\Ga4;

$endpoint = 'https://www.google-analytics.com/debug/mp/collect'
    . '?measurement_id=G-XXXXXXXXXX&api_secret=shape_test_only';

$failures = 0;

function check(string $label, bool $ok, string $detail = ''): void
{
    global $failures;

    if (!$ok) {
        $failures++;
    }

    echo ($ok ? 'PASS' : 'FAIL') . '  ' . $label . ($detail !== '' ? ' :: ' . $detail : '') . PHP_EOL;
}

/**
 * @param  array<string, mixed>  $body
 * @return array{status: int, json: array<string, mixed>|null, raw: string}
 */
function postJson(string $url, array $body): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($body),
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    $status = 0;

    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m) === 1) {
            $status = (int) $m[1];
        }
    }

    return [
        'status' => $status,
        'json' => is_string($raw) ? json_decode($raw, true) : null,
        'raw' => is_string($raw) ? $raw : '',
    ];
}

// ---------------------------------------------------------------------------
// 1. The payload our service builds for a real order.
// ---------------------------------------------------------------------------

$order = [
    'id' => 4242,
    'invoice_id' => 'INV-4242',
    'amount' => 2450.00,
    'discount' => 100.00,
    'shipping' => 80.00,
    'currency' => 'BDT',
];

$items = [
    Ga4::item(101, 'Cotton Baby Frock', 1190.00, 2),
    Ga4::item(202, 'Soft Wrap Blanket', 170.00, 1),
];

$payload = Ga4::purchasePayload($order, $items, '1234567890.1700000000', 1700000000);

check('payload has a client_id in GA4 shape', (bool) preg_match('/^\d+\.\d+$/', (string) $payload['client_id']), (string) $payload['client_id']);
check('payload has exactly one event', count($payload['events']) === 1, 'count=' . count($payload['events']));

$params = $payload['events'][0]['params'] ?? [];

check('event name is purchase', ($payload['events'][0]['name'] ?? '') === 'purchase');
check('transaction_id is the invoice number', ($params['transaction_id'] ?? '') === 'INV-4242', (string) ($params['transaction_id'] ?? ''));
check('value is numeric', is_float($params['value'] ?? null) || is_int($params['value'] ?? null), var_export($params['value'] ?? null, true));
check('currency is BDT', ($params['currency'] ?? '') === 'BDT');
check('session_id is a positive integer', is_int($params['session_id'] ?? null) && $params['session_id'] > 0, var_export($params['session_id'] ?? null, true));
check('two line items', count($params['items'] ?? []) === 2, 'count=' . count($params['items'] ?? []));
check('shipping included', ($params['shipping'] ?? null) === 80.0, var_export($params['shipping'] ?? null, true));
check('discount included', ($params['discount'] ?? null) === 100.0, var_export($params['discount'] ?? null, true));

// ---------------------------------------------------------------------------
// 2. Google's validation server, with the exact body.
// ---------------------------------------------------------------------------

$response = postJson($endpoint, $payload);
$messages = $response['json']['validationMessages'] ?? null;

check('validation server answered 2xx', $response['status'] >= 200 && $response['status'] < 300, 'status=' . $response['status']);
check('no validation messages for our payload', is_array($messages) && $messages === [], $response['raw'] !== '' ? substr($response['raw'], 0, 200) : 'empty body');

// ---------------------------------------------------------------------------
// 3. Negative controls: prove the endpoint is actually checking, so an empty
//    array above is a real pass and not a server that accepts anything.
// ---------------------------------------------------------------------------

$badName = postJson($endpoint, [
    'client_id' => '1234567890.1700000000',
    'events' => [['name' => '_badEventName', 'params' => new stdClass()]],
]);
$badNameCodes = array_column($badName['json']['validationMessages'] ?? [], 'validationCode');

check('negative control: invalid event name is rejected', in_array('NAME_INVALID', $badNameCodes, true), implode(',', $badNameCodes));

$noClient = postJson($endpoint, [
    'events' => [['name' => 'page_view', 'params' => new stdClass()]],
]);
$noClientCodes = array_column($noClient['json']['validationMessages'] ?? [], 'validationCode');

check('negative control: missing client_id is rejected', in_array('VALUE_REQUIRED', $noClientCodes, true), implode(',', $noClientCodes));

// ---------------------------------------------------------------------------
// 4. Debug endpoint selection is environment driven, not hardcoded.
// ---------------------------------------------------------------------------

config(['tracking.ga4.region' => 'global']);
check('endpoint() is the global collector', Ga4::endpoint() === 'https://www.google-analytics.com/mp/collect', Ga4::endpoint());
check('endpoint(true) is the validation server', Ga4::endpoint(true) === 'https://www.google-analytics.com/debug/mp/collect', Ga4::endpoint(true));

config(['tracking.ga4.region' => 'eu']);
check('region=eu switches to region1', str_starts_with(Ga4::endpoint(), 'https://region1.google-analytics.com/'), Ga4::endpoint());
config(['tracking.ga4.region' => 'global']);

echo PHP_EOL . ($failures === 0 ? 'RESULT: PASS (0 failures)' : 'RESULT: FAIL (' . $failures . ' failures)') . PHP_EOL;

exit($failures === 0 ? 0 : 1);
