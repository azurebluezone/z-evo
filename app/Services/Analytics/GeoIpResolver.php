<?php

namespace App\Services\Analytics;

use App\Models\IpGeoCache;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

/**
 * Geo-IP resolver with a database-backed cache.
 *
 * Resolution order:
 *   1. private/reserved IP  -> short-circuit, never leave the box
 *   2. ip_geo_cache table   -> one lookup per IP, forever
 *   3. stevebauman/location (MaxMind + configured fallbacks)
 *   4. persist the answer (including negative answers) back into the cache
 *
 * Negative results are cached too, otherwise every request from an IP the
 * providers cannot resolve re-hits the network — which is exactly the case that
 * makes an admin panel feel slow.
 */
class GeoIpResolver
{
    /**
     * Resolve an IP into a normalised geo payload.
     *
     * @return array{country:?string, city:?string, division:?string, district:?string,
     *               isp:?string, latitude:?string, longitude:?string, timezone:?string,
     *               cached:bool}
     */
    public function resolve(?string $ip): array
    {
        $empty = [
            'country' => null,
            'city' => null,
            'division' => null,
            'district' => null,
            'isp' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
            'cached' => false,
        ];

        $ip = is_string($ip) ? trim($ip) : '';

        if ($ip === '' || $this->isLocalAddress($ip)) {
            return $empty;
        }

        $cached = $this->readCache($ip);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $position = Location::get($ip);
        } catch (\Throwable $e) {
            Log::warning('Geo-IP lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            $this->writeCache($ip, [
                'country' => null,
                'city' => null,
                'division' => null,
                'district' => null,
                'isp' => null,
                'latitude' => null,
                'longitude' => null,
                'timezone' => null,
                'status' => 'error',
            ]);

            return $empty;
        }

        if (!$position) {
            $this->writeCache($ip, [
                'country' => null,
                'city' => null,
                'division' => null,
                'district' => null,
                'isp' => null,
                'latitude' => null,
                'longitude' => null,
                'timezone' => null,
                'status' => 'null',
            ]);

            return $empty;
        }

        $country = $position->countryName ?? $position->country ?? null;
        $city = $position->cityName ?? $position->city ?? null;
        $district = $position->districtName ?? $position->district ?? null;
        $isp = $position->isp ?? $position->org ?? $position->organization ?? null;

        $payload = [
            'country' => $country,
            'city' => $city,
            'division' => BangladeshGeo::divisionFor($district) ?? BangladeshGeo::divisionFor($city),
            'district' => $district,
            'isp' => BangladeshGeo::canonicalIsp($isp),
            'latitude' => $position->latitude ?? null,
            'longitude' => $position->longitude ?? null,
            'timezone' => $position->timezone ?? null,
            'status' => 'ok',
        ];

        $this->writeCache($ip, $payload);

        $payload['cached'] = false;

        return $payload;
    }

    /**
     * Read a previously cached resolution, or null when not cached.
     *
     * @return array<string, mixed>|null
     */
    private function readCache(string $ip): ?array
    {
        try {
            $row = IpGeoCache::where('ip', $ip)->first();
        } catch (\Throwable $e) {
            // A missing table must never break a pageview.
            return null;
        }

        if (!$row) {
            return null;
        }

        return [
            'country' => $row->country,
            'city' => $row->city,
            'division' => $row->division,
            'district' => $row->district,
            'isp' => $row->isp,
            'latitude' => $row->latitude,
            'longitude' => $row->longitude,
            'timezone' => $row->timezone,
            'cached' => true,
        ];
    }

    /**
     * Persist a resolution so the next request for this IP is free.
     *
     * @param  array<string, mixed>  $payload
     */
    private function writeCache(string $ip, array $payload): void
    {
        try {
            IpGeoCache::updateOrCreate(
                ['ip' => $ip],
                [
                    'country' => $payload['country'] ?? null,
                    'city' => $payload['city'] ?? null,
                    'division' => $payload['division'] ?? null,
                    'district' => $payload['district'] ?? null,
                    'isp' => $payload['isp'] ?? null,
                    'latitude' => $payload['latitude'] ?? null,
                    'longitude' => $payload['longitude'] ?? null,
                    'timezone' => $payload['timezone'] ?? null,
                    'lookup_status' => $payload['status'] ?? 'ok',
                    'driver' => 'location',
                    'last_lookup_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Geo-IP cache write failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Loopback, private and reserved ranges never have public geo data.
     */
    private function isLocalAddress(string $ip): bool
    {
        $raw = $ip;

        if (str_contains($raw, ',')) {
            // TrustProxies may hand us "client, proxy1, proxy2" — take the first.
            $raw = trim(explode(',', $raw)[0]);
        }

        if ($raw === '') {
            return true;
        }

        if (filter_var($raw, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }
}