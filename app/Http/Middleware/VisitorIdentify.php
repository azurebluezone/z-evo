<?php

namespace App\Http\Middleware;

use App\Models\VisitorMaster;
use App\Services\Analytics\GeoIpResolver;
use App\Services\Analytics\TrafficSourceResolver;
use App\Services\Analytics\UserAgentParser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Identifies the visitor and enriches their record for the analytics dashboard.
 *
 * Responsibilities (unchanged from before, plus analytics enrichment):
 *   - ensure a stable visitor_id cookie (UUID)
 *   - resolve geo (country / city / division / ISP) through the cached resolver
 *   - fingerprint the device (type / browser / OS / bot)
 *   - classify the traffic channel on first touch
 *   - keep the "last seen" heartbeat fresh for the live activity feed
 *   - honour the admin block flag
 */
class VisitorIdentify
{
    /** Live-feed heartbeat window: a visitor is "active" if seen within 5 minutes. */
    public const ACTIVE_WINDOW_MINUTES = 5;

    private GeoIpResolver $geo;

    private UserAgentParser $uaParser;

    private TrafficSourceResolver $sourceResolver;

    public function __construct()
    {
        $this->geo = new GeoIpResolver();
        $this->uaParser = new UserAgentParser();
        $this->sourceResolver = new TrafficSourceResolver();
    }

    public function handle(Request $request, Closure $next)
    {
        $visitorId = $request->cookie('visitor_id');

        if (empty($visitorId) || !Str::isUuid($visitorId)) {
            $visitorId = (string) Str::uuid();
            Cookie::queue(cookie('visitor_id', $visitorId, 60 * 24 * 365));
        }

        $ip = (string) $request->getClientIp();
        $userAgent = $request->userAgent();

        $visitor = VisitorMaster::where('visitor_id', $visitorId)->first();

        $fingerprint = $this->uaParser->parse($userAgent);

        // Geo is only resolved when we actually need it — a brand new visitor or
        // a record whose geo columns are still empty.
        $needsGeo = !$visitor
            || empty($visitor->country)
            || empty($visitor->division);

        $geo = null;
        if ($needsGeo) {
            try {
                $geo = $this->geo->resolve($ip);
            } catch (\Throwable $e) {
                Log::warning('Visitor geo enrichment failed', [
                    'visitor_id' => $visitorId,
                    'error' => $e->getMessage(),
                ]);
                $geo = null;
            }
        }

        $currentUrl = $this->currentUrl($request);
        $pagePath = '/' . ltrim($request->path(), '/');

        if (!$visitor) {
            $channel = $this->sourceResolver->resolve(
                $request->query(),
                $request->headers->get('referer')
            );

            $visitor = VisitorMaster::create([
                'visitor_id' => $visitorId,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'country' => $geo['country'] ?? null,
                'city' => $geo['city'] ?? null,
                'division' => $geo['division'] ?? null,
                'isp' => $geo['isp'] ?? null,
                'device_type' => $fingerprint['device_type'],
                'browser' => $fingerprint['browser'],
                'os' => $fingerprint['os'],
                'channel' => $channel,
                'referrer_host' => $this->sourceResolver->hostFromReferer($request->headers->get('referer')) ?: null,
                'referrer_url' => $this->clampUrl($request->headers->get('referer')),
                'utm_source' => $this->clamp($request->query('utm_source')),
                'utm_medium' => $this->clamp($request->query('utm_medium')),
                'utm_campaign' => $this->clamp($request->query('utm_campaign')),
                'utm_term' => $this->clamp($request->query('utm_term')),
                'utm_content' => $this->clamp($request->query('utm_content')),
                'landing_page' => $currentUrl,
                'first_page_path' => $this->clamp($pagePath, 500),
                'last_page_url' => $currentUrl,
                'last_seen_at' => now(),
                'status' => 1,
            ]);
        } else {
            $dirty = false;

            // --- Refresh mutable identity fields ---------------------------------
            if ($visitor->ip !== $ip) {
                $visitor->ip = $ip;
                $dirty = true;
                // A new IP means a new network: previous geo may no longer apply.
                if ($geo === null && !empty($visitor->country)) {
                    $geo = $this->geo->resolve($ip);
                }
            }

            if ($userAgent && $visitor->user_agent !== $userAgent) {
                $visitor->user_agent = $userAgent;
                $visitor->device_type = $fingerprint['device_type'];
                $visitor->browser = $fingerprint['browser'];
                $visitor->os = $fingerprint['os'];
                $dirty = true;
            } elseif ($visitor->device_type === null) {
                $visitor->device_type = $fingerprint['device_type'];
                $visitor->browser = $fingerprint['browser'];
                $visitor->os = $fingerprint['os'];
                $dirty = true;
            }

            // --- Geo enrichment (only fills blanks, never overwrites) -----------
            if ($geo !== null) {
                foreach ([
                    'country' => $geo['country'] ?? null,
                    'city' => $geo['city'] ?? null,
                    'division' => $geo['division'] ?? null,
                    'isp' => $geo['isp'] ?? null,
                ] as $column => $value) {
                    if (!empty($value) && empty($visitor->{$column})) {
                        $visitor->{$column} = $value;
                        $dirty = true;
                    }
                }
            }

            // --- First-touch attribution is sticky ------------------------------
            if (empty($visitor->channel)) {
                $visitor->channel = $this->sourceResolver->resolve(
                    $request->query(),
                    $request->headers->get('referer')
                );
                $visitor->referrer_host = $this->sourceResolver->hostFromReferer($request->headers->get('referer')) ?: null;
                $visitor->referrer_url = $this->clampUrl($request->headers->get('referer'));
                $dirty = true;
            }

            if (empty($visitor->landing_page)) {
                $visitor->landing_page = $currentUrl;
                $visitor->first_page_path = $this->clamp($pagePath, 500);
                $dirty = true;
            }

            // --- Live heartbeat ---------------------------------------------------
            $visitor->last_seen_at = now();
            $visitor->last_page_url = $currentUrl;
            $dirty = true;

            if ($dirty) {
                $visitor->save();
            }
        }

        if ((int) $visitor->status === 0) {
            abort(403, '403 Forbidden');
        }

        $request->attributes->set('visitor_id', $visitorId);
        $request->attributes->set('visitor_channel', $visitor->channel);

        return $next($request);
    }

    /**
     * Absolute URL without the query string — query params are already captured
     * in the dedicated utm_* columns, so repeating them here would only bloat rows.
     */
    private function currentUrl(Request $request): string
    {
        return rtrim($request->fullUrl(), '?&');
    }

    private function clamp(?string $value, int $max = 190): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $max);
    }

    private function clampUrl(?string $value, int $max = 1000): ?string
    {
        return $this->clamp($value, $max);
    }
}