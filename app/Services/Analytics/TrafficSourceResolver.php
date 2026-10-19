<?php

namespace App\Services\Analytics;

/**
 * Traffic source / channel resolver.
 *
 * Deterministic priority order — first match wins:
 *   1. explicit click IDs  (ttclid / fbclid / gclid)  — strongest ad signal
 *   2. utm_source / utm_medium / utm_campaign
 *   3. referrer host        (facebook.com, tiktok.com, google search, ...)
 *   4. no referrer at all   -> direct
 *
 * Search engines are deliberately split away from "google" so the dashboard can
 * distinguish paid Google traffic from organic Google traffic, which is the whole
 * point of the attribution grid.
 */
class TrafficSourceResolver
{
    public const CHANNEL_FACEBOOK = 'facebook';
    public const CHANNEL_GOOGLE = 'google';
    public const CHANNEL_TIKTOK = 'tiktok';
    public const CHANNEL_DIRECT = 'direct';
    public const CHANNEL_ORGANIC = 'organic';
    public const CHANNEL_REFERRAL = 'referral';

    /**
     * Channel labels used by the top filter bar.
     *
     * @var array<int, string>
     */
    public const CHANNEL_OPTIONS = [
        self::CHANNEL_FACEBOOK,
        self::CHANNEL_GOOGLE,
        self::CHANNEL_TIKTOK,
        self::CHANNEL_DIRECT,
        self::CHANNEL_ORGANIC,
        self::CHANNEL_REFERRAL,
    ];

    /**
     * Referrer host fragments that map to a known paid/social channel.
     *
     * @var array<string, string>
     */
    private const HOST_TO_CHANNEL = [
        'facebook.com' => self::CHANNEL_FACEBOOK,
        'fb.com' => self::CHANNEL_FACEBOOK,
        'm.facebook.com' => self::CHANNEL_FACEBOOK,
        'l.facebook.com' => self::CHANNEL_FACEBOOK,
        'instagram.com' => self::CHANNEL_FACEBOOK,
        'tiktok.com' => self::CHANNEL_TIKTOK,
        'tiktokv.com' => self::CHANNEL_TIKTOK,
        'vm.tiktok.com' => self::CHANNEL_TIKTOK,
        'google.com' => self::CHANNEL_GOOGLE,
        'google.co.bd' => self::CHANNEL_GOOGLE,
        'google.com.bd' => self::CHANNEL_GOOGLE,
        'youtube.com' => self::CHANNEL_GOOGLE,
        'youtu.be' => self::CHANNEL_GOOGLE,
    ];

    /**
     * Search-engine hosts — these count as organic, not paid.
     *
     * @var array<int, string>
     */
    private const SEARCH_ENGINES = [
        'google.',
        'bing.com',
        'duckduckgo.com',
        'yahoo.com',
        'search.yahoo.',
        'yandex.',
        'ecosia.org',
        'brave.com',
        'startpage.com',
        'qwant.com',
    ];

    /**
     * Resolve the channel from query params + referrer.
     *
     * @param  array<string, mixed>  $query  request query (utm_*, fbclid, ttclid, gclid)
     * @param  string|null  $referer  full referrer URL or bare host
     * @return string one of the CHANNEL_* constants
     */
    public function resolve(array $query, ?string $referer): string
    {
        $lower = static fn (?string $v): string => strtolower(trim((string) $v));

        $ttclid = $lower($query['ttclid'] ?? null);
        $fbclid = $lower($query['fbclid'] ?? null);
        $gclid = $lower($query['gclid'] ?? null);

        // 1. Click IDs are unambiguous proof of a paid click.
        if ($ttclid !== '') {
            return self::CHANNEL_TIKTOK;
        }
        if ($fbclid !== '') {
            return self::CHANNEL_FACEBOOK;
        }
        if ($gclid !== '') {
            return self::CHANNEL_GOOGLE;
        }

        // 2. Explicit UTM tagging from an ad manager.
        $utmSource = $lower($query['utm_source'] ?? null);
        if ($utmSource !== '') {
            $mapped = $this->channelFromName($utmSource);
            if ($mapped !== null) {
                return $mapped;
            }

            $utmMedium = $lower($query['utm_medium'] ?? null);
            if ($utmMedium !== '') {
                $mapped = $this->channelFromName($utmMedium);
                if ($mapped !== null) {
                    return $mapped;
                }
            }

            return self::CHANNEL_REFERRAL;
        }

        // 3. Referrer host.
        $host = $this->hostFromReferer($referer);
        if ($host === '') {
            return self::CHANNEL_DIRECT;
        }

        foreach (self::HOST_TO_CHANNEL as $fragment => $channel) {
            if (str_contains($host, $fragment)) {
                // A google.com referrer from a search results page is organic,
                // not paid Google traffic.
                if ($channel === self::CHANNEL_GOOGLE && $this->isOrganicSearch($referer)) {
                    return self::CHANNEL_ORGANIC;
                }

                return $channel;
            }
        }

        foreach (self::SEARCH_ENGINES as $fragment) {
            if (str_contains($host, $fragment)) {
                return self::CHANNEL_ORGANIC;
            }
        }

        return self::CHANNEL_REFERRAL;
    }

    /**
     * Mark whether the session arrived on a search engine result page.
     */
    public function isOrganicSearch(?string $referer): bool
    {
        $host = $this->hostFromReferer($referer);

        if ($host === '') {
            return false;
        }

        foreach (self::SEARCH_ENGINES as $fragment) {
            if (str_contains($host, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract just the host out of a referrer, tolerating a bare host input.
     */
    public function hostFromReferer(?string $referer): string
    {
        $referer = trim((string) $referer);

        if ($referer === '') {
            return '';
        }

        $host = parse_url($referer, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return strtolower($host);
        }

        // Not a full URL — the caller may have passed "facebook.com/xyz" already.
        $candidate = strtolower(explode('/', $referer)[0]);

        return str_contains($candidate, '.') ? $candidate : '';
    }

    /**
     * True when the URL points at a host outside our own domain — used to build
     * the outbound-click leg of the Sankey diagram.
     */
    public function isOutbound(?string $url, ?string $siteHost): bool
    {
        $host = $this->hostFromReferer($url);

        if ($host === '' || $siteHost === '') {
            return false;
        }

        $site = strtolower($siteHost);

        // Compare on registrable-ish boundary to avoid example.com matching
        // notexample.com.
        return !($host === $site || str_ends_with($host, '.' . $site));
    }

    /**
     * Map a loose source/medium name onto a known channel.
     */
    private function channelFromName(string $name): ?string
    {
        $needle = strtolower($name);

        foreach (self::HOST_TO_CHANNEL as $fragment => $channel) {
            $bare = preg_replace('/\.(com|net|co\.bd)$/', '', $fragment) ?? $fragment;
            if (str_contains($needle, $bare)) {
                return $channel;
            }
        }

        if (str_contains($needle, 'tiktok')) {
            return self::CHANNEL_TIKTOK;
        }

        if (str_contains($needle, 'facebook') || str_contains($needle, 'meta') || str_contains($needle, 'instagram')) {
            return self::CHANNEL_FACEBOOK;
        }

        if (str_contains($needle, 'google') || str_contains($needle, 'youtube') || str_contains($needle, 'bing')) {
            return self::CHANNEL_GOOGLE;
        }

        if (str_contains($needle, 'organic') || str_contains($needle, 'seo')) {
            return self::CHANNEL_ORGANIC;
        }

        if (str_contains($needle, 'direct') || str_contains($needle, 'none') || $needle === '(direct)') {
            return self::CHANNEL_DIRECT;
        }

        if (str_contains($needle, 'referral') || str_contains($needle, 'link')) {
            return self::CHANNEL_REFERRAL;
        }

        return null;
    }
}