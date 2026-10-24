<?php

/**
 * Tracking host resolution.
 *
 * Nothing here needs its own domain. The storefront's own host is the default,
 * and every browser endpoint is called with a relative path (/events/collect,
 * /api/facebook/track), so the tracker follows whatever host served the page.
 *
 * A separate host only becomes relevant if we adopt Google's server-side
 * container, and even then a *subdomain* of the existing registrable domain is
 * enough: same eTLD+1, so cookies, CORS and ad-blocker behaviour match the shop.
 *
 *   TRACKING_HOST unset  -> null, use the request host (today)
 *   TRACKING_HOST=t.example.com -> the sGTM container host
 *
 * Derivation when it is needed:
 *   't.' . parse_url(config('app.url'), PHP_URL_HOST)
 */
return [
    'host' => env('TRACKING_HOST') ?: null,
];
