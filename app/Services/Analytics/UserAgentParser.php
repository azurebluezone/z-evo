<?php

namespace App\Services\Analytics;

/**
 * Lightweight user-agent fingerprinting.
 *
 * No external dependency is used here on purpose: the dashboard only needs four
 * coarse buckets (device type, browser, OS, is-bot) and pulling in a full UA
 * parser for that would be heavier than the matching itself.
 *
 * Bot detection matters because without it crawler traffic pollutes every KPI
 * card and the live feed fills up with junk rows.
 */
class UserAgentParser
{
    /**
     * @param  string|null  $userAgent
     * @return array{device_type:?string, browser:?string, os:?string, is_bot:bool}
     */
    public function parse(?string $userAgent): array
    {
        $empty = ['device_type' => null, 'browser' => null, 'os' => null, 'is_bot' => false];

        $ua = trim((string) $userAgent);

        if ($ua === '') {
            return $empty;
        }

        $isBot = $this->isBot($ua);

        return [
            'device_type' => $isBot ? 'bot' : $this->deviceType($ua),
            'browser' => $this->browser($ua),
            'os' => $this->os($ua),
            'is_bot' => $isBot,
        ];
    }

    /**
     * Crawler / monitoring agents. Kept explicit rather than heuristic because a
     * false positive would silently drop real users from the dashboard.
     */
    public function isBot(string $ua): bool
    {
        $needles = [
            'bot', 'crawler', 'spider', 'slurp', 'curl', 'wget', 'python-requests',
            'headlesschrome', 'phantomjs', 'scrapy', 'httpclient', 'postmanruntime',
            'facebookexternalhit', 'ia_archiver', 'semrushbot', 'ahrefsbot', 'mj12',
            'dataprovider', 'lighthouse', 'pingdom', 'uptimerobot', 'gtmetrix',
        ];

        $needle = strtolower($ua);

        foreach ($needles as $n) {
            if (str_contains($needle, $n)) {
                return true;
            }
        }

        return false;
    }

    private function deviceType(string $ua): string
    {
        $needle = strtolower($ua);

        // Tablet before mobile — most tablet UAs also contain "Mobile"-like hints.
        if (str_contains($needle, 'ipad')
            || str_contains($needle, 'tablet')
            || str_contains($needle, 'playbook')
            || str_contains($needle, 'kindle')
            || str_contains($needle, 'silk')) {
            return 'tablet';
        }

        if (str_contains($needle, 'mobi')
            || str_contains($needle, 'iphone')
            || str_contains($needle, 'ipod')
            || str_contains($needle, 'android')
            || str_contains($needle, 'blackberry')
            || str_contains($needle, 'windows phone')
            || str_contains($needle, 'opera mini')
            || str_contains($needle, 'iemobile')) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function browser(string $ua): ?string
    {
        $needle = strtolower($ua);

        // Order matters: most Chromium forks also advertise "Safari" and
        // "Chrome", so the specific token has to be tested first.
        $rules = [
            'edge' => ['edg/', 'edge/', 'edga/', 'edgios/'],
            'opera' => ['opr/', 'opera'],
            'samsung internet' => ['samsungbrowser'],
            'brave' => ['brave/'],
            'vivaldi' => ['vivaldi/'],
            'yandex' => ['yandex', 'yabrowser'],
            'chrome' => ['chrome/', 'crios/', 'chromium'],
            'firefox' => ['firefox/', 'fxios/'],
            // iOS Safari ends in "Mobile/15E148 Safari/604.1" but some webviews
            // only expose "Mobile Safari", so both forms are accepted.
            'safari' => ['safari/', 'mobile safari'],
            'internet explorer' => ['msie', 'trident/'],
        ];

        foreach ($rules as $label => $needles) {
            foreach ($needles as $n) {
                if (str_contains($needle, $n)) {
                    return $label === 'safari' ? 'safari' : $label;
                }
            }
        }

        return null;
    }

    private function os(string $ua): ?string
    {
        $needle = strtolower($ua);

        // Windows version is genuinely useful in this dashboard (the admin log
        // shows "Chrome 128 / Windows"), so it is read out rather than lumped.
        if (preg_match('/windows nt (\d+\.\d+)/', $needle, $m)) {
            $map = [
                '10.0' => 'Windows 10/11',
                '6.3' => 'Windows 8.1',
                '6.2' => 'Windows 8',
                '6.1' => 'Windows 7',
            ];

            return $map[$m[1]] ?? 'Windows';
        }

        $rules = [
            'android' => ['android'],
            'ios' => ['iphone', 'ipad', 'ipod', 'crios/', 'fxios/'],
            'macos' => ['mac os x', 'macintosh'],
            'chromeos' => ['cros'],
            'linux' => ['linux', 'x11'],
            'freebsd' => ['freebsd'],
            'openbsd' => ['openbsd'],
        ];

        foreach ($rules as $label => $needles) {
            foreach ($needles as $n) {
                if (str_contains($needle, $n)) {
                    return $label;
                }
            }
        }

        return null;
    }
}