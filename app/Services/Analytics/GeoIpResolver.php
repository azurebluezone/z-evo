<?php

namespace App\Services\Analytics;

use App\Models\IpGeoCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

/**
 * Geo-IP resolver with a database-backed cache.
 *
 * Resolution order:
 *   1. private/reserved IP  -> short-circuit, never leave the box
 *   2. ip_geo_cache table   -> one successful lookup per IP
 *   3. stevebauman/location (MaxMind + its configured fallbacks)
 *   4. free HTTP providers  -> ipwho.is, ipapi.co, ip-api.com
 *   5. persist the answer back into the cache
 *
 * Negative answers are cached for six hours, not forever. The old code cached
 * them permanently, so one transient provider failure meant an IP could never
 * be resolved again. A short negative TTL keeps the providers from being hit on
 * every pageview while still allowing a retry after an outage.
 */
class GeoIpResolver
{
    /** How long a failed lookup is trusted before the providers are retried. */
    private const NEGATIVE_TTL_HOURS = 6;

    private const TIMEOUT_SECONDS = 4;

    /**
     * Resolve an IP into a normalised geo payload.
     *
     * @return array{country:?string, city:?string, division:?string, district:?string,
     *               isp:?string, latitude:?string, longitude:?string, timezone:?string,
     *               status:string, driver:?string, cached:bool}
     */
    public function resolve(?string $ip): array
    {
        $ip = is_string($ip) ? trim(explode(',', $ip)[0]) : '';

        if ($ip === '' || $this->isLocalAddress($ip)) {
            return $this->emptyPayload();
        }

        $cached = $this->readCache($ip);
        if ($cached !== null) {
            return $cached;
        }

        $payload = $this->fromLocationPackage($ip) ?? $this->fromFreeApis($ip);

        if ($payload === null) {
            $this->writeCache($ip, array_merge($this->emptyPayload(), ['status' => 'error']));
            return $this->emptyPayload();
        }

        $this->writeCache($ip, $payload);

        return $payload + ['cached' => false];
    }

    /**
     * The configured package: MaxMind first, then its own fallback list.
     */
    private function fromLocationPackage(string $ip): ?array
    {
        try {
            $position = Location::get($ip);
        } catch (\Throwable $e) {
            Log::info('Geo-IP package lookup failed', ['ip' => $ip, 'error' => $e->getMessage()]);
            return null;
        }

        if (!$position) {
            return null;
        }

        $payload = $this->payload([
            'country' => $position->countryName ?? $position->country ?? null,
            'city' => $position->cityName ?? $position->city ?? null,
            'district' => $position->regionName ?? $position->region ?? null,
            'isp' => $position->isp ?? $position->org ?? $position->organization ?? null,
            'latitude' => $position->latitude ?? null,
            'longitude' => $position->longitude ?? null,
            'timezone' => $position->timezone ?? null,
        ], 'stevebauman/location');

        return $payload['country'] || $payload['city'] || $payload['latitude'] ? $payload : null;
    }

    /**
     * No API key required. `IP_API_KEY` upgrades the last provider to the
     * pro endpoint behind members.ip-api.com; the free endpoint is the default.
     */
    private function fromFreeApis(string $ip): ?array
    {
        $errors = [];

        foreach ($this->providerUrls($ip) as $driver => $url) {
            try {
                $response = Http::acceptJson()
                    ->timeout(self::TIMEOUT_SECONDS)
                    ->connectTimeout(2)
                    ->get($url);

                if (!$response->successful()) {
                    $errors[$driver] = 'HTTP ' . $response->status();
                    continue;
                }

                $payload = $this->payloadFromProvider($driver, (array) $response->json());

                if ($payload !== null && ($payload['country'] || $payload['city'] || $payload['latitude'])) {
                    return $payload;
                }

                $errors[$driver] = 'no usable geo fields';
            } catch (\Throwable $e) {
                $errors[$driver] = $e->getMessage();
            }
        }

        if ($errors !== []) {
            Log::info('Geo-IP providers returned nothing', ['ip' => $ip, 'errors' => $errors]);
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function providerUrls(string $ip): array
    {
        $key = (string) config('services.ip_api.key', '');

        return [
            'ipwho.is' => 'https://ipwho.is/' . $ip,
            'ipapi.co' => 'https://ipapi.co/' . $ip . '/json/',
            'ip-api.com' => $key !== ''
                ? 'https://pro.ip-api.com/json/' . $ip . '?key=' . rawurlencode($key)
                    . '&fields=status,country,regionName,city,lat,lon,timezone,isp,org'
                : 'http://ip-api.com/json/' . $ip
                    . '?fields=status,country,regionName,city,lat,lon,timezone,isp,org',
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     */
    private function payloadFromProvider(string $driver, array $json): ?array
    {
        if (($json['success'] ?? null) === false || ($json['error'] ?? false) === true) {
            return null;
        }

        return match ($driver) {
            'ipwho.is' => $this->payload([
                'country' => $json['country'] ?? null,
                'city' => $json['city'] ?? null,
                'district' => $json['region'] ?? null,
                'isp' => $json['connection']['isp'] ?? $json['connection']['org'] ?? null,
                'latitude' => $json['latitude'] ?? null,
                'longitude' => $json['longitude'] ?? null,
                'timezone' => $json['timezone']['id'] ?? null,
            ], $driver),

            'ipapi.co' => $this->payload([
                'country' => $json['country_name'] ?? null,
                'city' => $json['city'] ?? null,
                'district' => $json['region'] ?? null,
                'isp' => $json['org'] ?? null,
                'latitude' => $json['latitude'] ?? null,
                'longitude' => $json['longitude'] ?? null,
                'timezone' => $json['timezone'] ?? null,
            ], $driver),

            'ip-api.com' => ($json['status'] ?? null) === 'success'
                ? $this->payload([
                    'country' => $json['country'] ?? null,
                    'city' => $json['city'] ?? null,
                    'district' => $json['regionName'] ?? null,
                    'isp' => $json['isp'] ?? $json['org'] ?? null,
                    'latitude' => $json['lat'] ?? null,
                    'longitude' => $json['lon'] ?? null,
                    'timezone' => $json['timezone'] ?? null,
                ], $driver)
                : null,

            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, string $driver): array
    {
        $country = $this->text($data['country'] ?? null);
        $city = $this->text($data['city'] ?? null);
        $district = $this->text($data['district'] ?? null);
        $isp = $this->text($data['isp'] ?? null);
        $latitude = $this->coordinate($data['latitude'] ?? null);
        $longitude = $this->coordinate($data['longitude'] ?? null);

        return [
            'country' => $country,
            'city' => $city,
            'division' => BangladeshGeo::divisionFor($district)
                ?? BangladeshGeo::divisionFor($city),
            'district' => $district,
            'isp' => BangladeshGeo::canonicalIsp($isp),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => $this->text($data['timezone'] ?? null),
            'status' => 'ok',
            'driver' => $driver,
            'cached' => false,
        ];
    }

    /**
     * Read a usable cached answer, or null when the cache should be retried.
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

        $status = (string) ($row->lookup_status ?? 'ok');
        $lookedUpAt = $row->last_lookup_at;

        if ($status !== 'ok') {
            $fresh = $lookedUpAt !== null
                && $lookedUpAt->greaterThan(now()->subHours(self::NEGATIVE_TTL_HOURS));

            if (!$fresh) {
                return null;
            }
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
            'status' => $status,
            'driver' => $row->driver,
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
                    'driver' => $payload['driver'] ?? null,
                    'last_lookup_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Geo-IP cache write failed', ['ip' => $ip, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        return [
            'country' => null,
            'city' => null,
            'division' => null,
            'district' => null,
            'isp' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
            'status' => 'null',
            'driver' => null,
            'cached' => false,
        ];
    }

    private function text($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function coordinate($value): ?string
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Loopback, private and reserved ranges never have public geo data.
     */
    private function isLocalAddress(string $ip): bool
    {
        if ($ip === '') {
            return true;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
