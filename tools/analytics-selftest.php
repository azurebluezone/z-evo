<?php
// Temporary self-test harness for the analytics resolvers.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$src  = new App\Services\Analytics\TrafficSourceResolver();
$ua   = new App\Services\Analytics\UserAgentParser();
$geoC = App\Services\Analytics\BangladeshGeo::class;
$fail = 0;

echo "-- CHANNEL --\n";
$cases = [
    ['fbclid=abc123', '', 'facebook'],
    ['ttclid=xyz', '', 'tiktok'],
    ['gclid=q1', '', 'google'],
    ['', 'https://l.facebook.com/l.php?u=1', 'facebook'],
    ['', 'https://www.google.com/search?q=s', 'organic'],
    ['', 'https://www.bing.com/search?q=s', 'organic'],
    ['', '', 'direct'],
    ['utm_source=tiktok&utm_medium=cpc', '', 'tiktok'],
    ['utm_source=google&utm_medium=cpc', '', 'google'],
    ['', 'https://news.ycombinator.com/', 'referral'],
    ['', 'https://www.youtube.com/watch?v=1', 'google'],
    ['', 'https://vm.tiktok.com/abc', 'tiktok'],
    ['', 'https://instagram.com/x', 'facebook'],
];
foreach ($cases as [$q, $r, $want]) {
    parse_str($q, $qs);
    $got = $src->resolve($qs, $r);
    $ok  = $got === $want;
    if (! $ok) { $fail++; }
    printf("  %-40s => %-9s %s\n", $q ?: ($r ?: '(none)'), $got, $ok ? 'OK' : "FAIL exp=$want");
}

echo "-- UA --\n";
$uas = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36' => ['desktop', 'chrome', 'Windows 10/11'],
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605 Version/17.0 Mobile Safari' => ['mobile', 'safari', 'ios'],
    'Mozilla/5.0 (Linux; Android 14) Chrome/128 Mobile' => ['mobile', 'chrome', 'android'],
    'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605 Safari/604' => ['tablet', 'safari', 'ios'],
    // Googlebot's UA carries no browser or OS token at all, so null is the
    // honest answer here - the bot flag is what the dashboard actually needs.
    'Googlebot/2.1 (+http://www.google.com/bot.html)' => ['bot', null, null],
    'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 Chrome/128 Safari/537.36 Edg/128' => ['desktop', 'edge', 'Windows 10/11'],
];
foreach ($uas as $u => [$d, $b, $o]) {
    $p  = $ua->parse($u);
    $ok = ($p['device_type'] === $d && $p['browser'] === $b && $p['os'] === $o);
    if (! $ok) { $fail++; }
    printf("  %-8s %-8s %-14s %s\n", $p['device_type'], $p['browser'], $p['os'], $ok ? 'OK' : "FAIL exp=$d/$b/$o");
}

echo "-- DIVISION --\n";
foreach (['Sylhet', 'Dhaka', 'Barisal', 'Barishal', 'Comilla', 'bogra', 'Sylhet Division, Bangladesh', 'Kushtia', 'Jhalokati', "Cox's Bazar", null] as $c) {
    printf("  %-32s => %s\n", $c ?? '(null)', $geoC::divisionFor($c) ?? '-');
}

echo "-- ISP --\n";
foreach (['Grameenphone Ltd.', 'robi', 'GP', 'Banglalink', 'AmberIT', 'Grameenphone', null] as $i) {
    printf("  %-22s => %s\n", $i ?? '(null)', $geoC::canonicalIsp($i) ?? '-');
}

echo $fail === 0 ? "\nALL PASS\n" : "\nFAILURES: $fail\n";