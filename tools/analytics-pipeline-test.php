<?php
// End-to-end pipeline check for the analytics event recorder:
// browser event -> server-side duplicate -> dedup accounting -> visitor counters.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\EventLog;
use App\Models\VisitorMaster;
use App\Services\Analytics\EventRecorder;

$visitorId = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';
VisitorMaster::where('visitor_id', $visitorId)->delete();
EventLog::where('visitor_id', $visitorId)->delete();

VisitorMaster::create([
    'visitor_id' => $visitorId,
    'ip' => '8.8.8.8',
    'user_agent' => 'test-agent',
    'status' => 1,
    'channel' => 'facebook',
    'device_type' => 'mobile',
    'event_count' => 0,
    'pageview_count' => 0,
    'browser_event_count' => 0,
    'server_event_count' => 0,
]);

$recorder = app(EventRecorder::class);
$sharedId = 'shared-event-' . substr(md5((string) microtime(true)), 0, 12);

echo "1) BROWSER BATCH (3 events, one carrying the shared event_id)\n";
$r1 = $recorder->recordBatch($visitorId, [
    ['name' => 'PageView', 'event_id' => 'browser-pv-1', 'url' => 'https://shop.test/', 'ts' => time()],
    ['name' => 'Scroll_50', 'event_id' => 'browser-scroll-1', 'url' => 'https://shop.test/', 'ts' => time(), 'scroll_depth' => 50],
    ['name' => 'AddToCart', 'event_id' => $sharedId, 'url' => 'https://shop.test/p/1', 'ts' => time(), 'click_target' => 'add_to_cart#1'],
], EventRecorder::METHOD_BROWSER);
printf("   stored=%d duplicates=%d rejected=%d\n", $r1['stored'], $r1['duplicates'], count($r1['rejected']));

echo "2) SERVER BATCH (same event_id -> must NOT create a second row)\n";
$r2 = $recorder->recordBatch($visitorId, [
    ['name' => 'AddToCart', 'event_id' => $sharedId, 'url' => 'https://shop.test/p/1', 'ts' => time()],
    ['name' => 'Purchase', 'event_id' => 'server-purchase-1', 'url' => 'https://shop.test/p/1', 'ts' => time()],
], EventRecorder::METHOD_SERVER);
printf("   stored=%d duplicates=%d rejected=%d\n", $r2['stored'], $r2['duplicates'], count($r2['rejected']));

echo "3) MALFORMED / HOSTILE PAYLOAD (public endpoint hardening)\n";
$r3 = $recorder->recordBatch($visitorId, [
    ['name' => 'DROP TABLE event_logs'],
    ['name' => ''],
    ['name' => 'PageView', 'event_id' => str_repeat('x', 200)],
    ['name' => 'PageView', 'event_id' => "bad';--"],
    ['not-an-array'],
], EventRecorder::METHOD_BROWSER);
printf("   stored=%d rejected=%d\n", $r3['stored'], count($r3['rejected']));

echo "\n-- RESULTING ROWS --\n";
$rows = EventLog::where('visitor_id', $visitorId)->orderBy('id')->get(['event_name','event_id','tracking_method','dedup_status','scroll_depth','click_target']);
foreach ($rows as $r) {
    printf("   %-14s id=%-26s %-8s %-12s scroll=%-4s target=%s\n",
        $r->event_name, substr((string)$r->event_id,0,26), (string)$r->tracking_method, (string)$r->dedup_status,
        var_export($r->scroll_depth, true), (string)$r->click_target);
}

$v = VisitorMaster::where('visitor_id', $visitorId)->first();
printf("\n-- VISITOR COUNTERS --\n   events=%d pageviews=%d browser=%d server=%d last_event=%s\n",
    $v->event_count, $v->pageview_count, $v->browser_event_count, $v->server_event_count, $v->last_event_name);

$dupRows = EventLog::where('visitor_id', $visitorId)->where('event_id', $sharedId)->count();
$addToCart = EventLog::where('visitor_id', $visitorId)->where('event_name', 'AddToCart')->count();
$storedRows = EventLog::where('visitor_id', $visitorId)->count();
$shared = EventLog::where('visitor_id', $visitorId)->where('event_id', $sharedId)->first();
$sharedStatus = $shared ? $shared->dedup_status : 'MISSING';

echo "\n-- ASSERTIONS --\n";
$checks = [
    'shared event_id stored exactly once'      => $dupRows === 1,
    'AddToCart counted once (not twice)'       => $addToCart === 1,
    'exactly 4 rows survived hostile input' => $storedRows === 4,
    'shared row promoted to primary (dedup)'   => $sharedStatus === 'primary',
    'browser counter == 3'                    => $v->browser_event_count === 3,
    'server counter == 1 (dup not counted)'   => $v->server_event_count === 1,
    'pageview counter == 1'                   => $v->pageview_count === 1,
    'total event counter == 4'                 => $v->event_count === 4,
    'scroll_depth persisted'                  => (int) $rows->firstWhere('event_name','Scroll_50')->scroll_depth === 50,
];
$fail = 0;
foreach ($checks as $label => $ok) {
    if (!$ok) { $fail++; }
    printf("   [%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
}

// cleanup
EventLog::where('visitor_id', $visitorId)->delete();
VisitorMaster::where('visitor_id', $visitorId)->delete();

echo $fail === 0 ? "\nPIPELINE OK\n" : "\nPIPELINE FAILURES: $fail\n";