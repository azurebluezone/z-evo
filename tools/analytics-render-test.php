<?php
// Renders the real analytics route through the full HTTP kernel as a logged-in
// admin, so route + middleware + controller + Blade are all exercised together.
require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Boot the framework (config, DB, facades) before touching any model.
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = App\Models\User::where('id', 1)->first()
      ?? App\Models\User::orderBy('id')->first();

if (! $admin) {
    echo "NO ADMIN USER FOUND\n";
    exit(1);
}

Illuminate\Support\Facades\Auth::guard('web')->login($admin);
app('auth')->shouldUse('web');
Illuminate\Support\Facades\Session::start();

$urls = [
    '/admin/analytics?preset=last7',
    '/admin/analytics?preset=today',
    '/admin/analytics?preset=yesterday',
    '/admin/analytics?preset=last90&channel=facebook',
    '/admin/analytics?preset=custom&from=2026-10-01&to=2026-10-13&method=browser',
    '/admin/analytics?preset=last30&division=Dhaka',
    '/admin/analytics?search=103.10',
    '/admin/analytics/live?preset=last7',
];

$fail = 0;

foreach ($urls as $url) {
    $request = Illuminate\Http\Request::create($url, 'GET');
    $request->setLaravelSession(app('session.store'));

    try {
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $body = $response->getContent();
        $len = strlen($body);

        $note = '';
        if ($status >= 500) {
            $note = ' <-- ERROR';
            $fail++;
            // Surface the first exception message for diagnosis.
            if (preg_match('/<title>(.*?)<\/title>/s', $body, $m)) {
                $note .= ' :: ' . strip_tags(html_entity_decode($m[1]));
            }
        }
        printf("  [%d] %-62s %7d bytes%s\n", $status, $url, $len, $note);
    } catch (\Throwable $e) {
        $fail++;
        printf("  [EX] %-62s %s\n", $url, $e->getMessage());
    }
}

// Visitor drill-down needs a real visitor id.
$visitorId = App\Models\VisitorMaster::orderByDesc('id')->value('visitor_id');
if ($visitorId) {
    $request = Illuminate\Http\Request::create('/admin/analytics/visitor/' . $visitorId, 'GET');
    $request->setLaravelSession(app('session.store'));
    try {
        $response = $kernel->handle($request);
        printf("  [%d] %-62s %7d bytes\n", $response->getStatusCode(), '/admin/analytics/visitor/{id}', strlen($response->getContent()));
        if ($response->getStatusCode() >= 400) { $fail++; }
    } catch (\Throwable $e) {
        $fail++;
        printf("  [EX] visitor drill-down: %s\n", $e->getMessage());
    }
}

echo $fail === 0 ? "\nRENDER OK\n" : "\nRENDER FAILURES: $fail\n";