<?php

/**
 * Tracking host and vendor configuration.
 *
 * Nothing here needs its own domain. The storefront's own host is the default,
 * and every browser endpoint is called with a relative path (/events/collect,
 * /api/facebook/track), so the tracker follows whatever host served the page.
 *
 * A separate host only becomes relevant if we adopt Google's server-side
 * container, and even then a *subdomain* of the existing registrable domain is
 * enough: same eTLD+1, so cookies, CORS and ad-blocker behaviour match the shop.
 *
 *   TRACKING_HOST unset        -> null, use the request host (today)
 *   TRACKING_HOST=t.example.com -> the sGTM container host
 *
 * Server container URL, in order of precedence:
 *   1. TRACKING_SERVER_CONTAINER_URL (a full URL, so option B - the /t/ path
 *      behind the same web server - is expressible without a DNS change);
 *   2. https://<TRACKING_HOST>;
 *   3. derived from APP_URL: <scheme>://t.<host of APP_URL>, but only for a real
 *      domain. localhost/127.0.0.1 has no meaningful "t." sibling, so dev stays
 *      null and no page ever points at a host that cannot resolve.
 *
 * The GA4 measurement id and API secret are environment values, not database
 * columns: the secret is a credential, and the plan forbids schema changes for
 * tracking. Leaving them unset disables the server-side sender completely.
 */
$appUrl = (string) env('APP_URL', 'http://localhost');
$appHost = parse_url($appUrl, PHP_URL_HOST) ?: null;
$appScheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';

$trackingHost = env('TRACKING_HOST') ?: null;

$serverContainerUrl = env('TRACKING_SERVER_CONTAINER_URL') ?: null;

if (!$serverContainerUrl && $trackingHost) {
    $serverContainerUrl = $appScheme . '://' . $trackingHost;
}

if (!$serverContainerUrl
    && env('TRACKING_GTM_SERVER_ID')
    && is_string($appHost)
    && $appHost !== ''
    && !in_array($appHost, ['localhost', '127.0.0.1', '::1'], true)) {
    $serverContainerUrl = $appScheme . '://t.' . $appHost;
}

return [
    'host' => $trackingHost,

    'server_container' => [
        // Where gtag.js sends its hits when a server container exists.
        'url' => $serverContainerUrl,
    ],

    'gtm' => [
        // Web container. The existing admin "GTM code" field still wins; this
        // is the env fallback for deployments that never touch the admin UI.
        'container_id' => env('TRACKING_GTM_ID') ?: null,
        // Server container id, used only to derive the default host above.
        'server_container_id' => env('TRACKING_GTM_SERVER_ID') ?: null,
    ],

    'ga4' => [
        'measurement_id' => env('TRACKING_GA4_ID') ?: null,
        'api_secret' => env('TRACKING_GA4_API_SECRET') ?: null,
        // Off by default: the Measurement Protocol copy of a purchase is a
        // fallback for a blocked browser tag, not a second event stream.
        'server_side' => (bool) env('TRACKING_GA4_SERVER_SIDE', false),
        // When true the sender posts to /debug/mp/collect and logs the
        // validation messages instead of silently accepting the payload.
        'debug' => (bool) env('TRACKING_GA4_DEBUG', false),
        // 'global' (www.google-analytics.com) or 'eu' (region1...).
        'region' => env('TRACKING_GA4_REGION') ?: 'global',
    ],
];
