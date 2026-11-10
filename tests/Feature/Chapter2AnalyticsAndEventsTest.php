<?php

namespace Tests\Feature;

use App\Models\EventLog;
use App\Models\VisitorMaster;
use App\Services\Analytics\EventRecorder;
use App\Support\AnalyticsCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Dashboard freshness, bulk event ingestion and the modern storefront.
 *
 * These run against the real application database (the project has no
 * separate test database), so every row created here is removed in tearDown.
 */
class Chapter2AnalyticsAndEventsTest extends TestCase
{
    private array $visitorIds = [];

    protected function tearDown(): void
    {
        if ($this->visitorIds !== []) {
            EventLog::query()->whereIn('visitor_id', $this->visitorIds)->delete();
            VisitorMaster::query()->whereIn('visitor_id', $this->visitorIds)->delete();
        }

        parent::tearDown();
    }

    public function test_analytics_cache_is_five_minutes_and_versioned(): void
    {
        $this->assertSame(5, AnalyticsCache::TTL_MINUTES);

        $before = AnalyticsCache::version();
        $keyBefore = AnalyticsCache::key(['preset' => 'last7']);

        AnalyticsCache::invalidate();

        $after = AnalyticsCache::version();

        $this->assertGreaterThan($before, $after);
        $this->assertNotSame($keyBefore, AnalyticsCache::key(['preset' => 'last7']));
    }

    public function test_event_recorder_invalidates_the_dashboard_cache(): void
    {
        $visitorId = $this->makeVisitor();
        $before = AnalyticsCache::version();

        app(EventRecorder::class)->recordBatch($visitorId, [[
            'name' => 'PageView',
            'event_id' => 'evt_' . Str::random(16),
            'url' => 'http://127.0.0.1:8010/products',
            'ts' => time(),
        ]], EventRecorder::METHOD_BROWSER);

        $this->assertGreaterThan($before, AnalyticsCache::version());
    }

    public function test_batch_endpoint_writes_the_whole_batch_with_one_insert(): void
    {
        $visitorId = $this->makeVisitor();
        $events = [];

        for ($i = 0; $i < 25; $i++) {
            $events[] = [
                'event_name' => 'ViewContent',
                'event_id' => 'evt_' . $visitorId . '_' . $i,
                'url' => 'http://127.0.0.1:8010/products?page=' . $i,
                'event_time' => time(),
                'event_data' => ['position' => $i],
            ];
        }

        $inserts = 0;

        DB::listen(function ($query) use (&$inserts) {
            if (stripos($query->sql, 'insert into `event_logs`') !== false
                || stripos($query->sql, 'insert ignore into `event_logs`') !== false) {
                $inserts++;
            }
        });

        $response = $this->postJson('/api/event-log/batch', ['events' => $events], [
            'X-Visitor-Id' => $visitorId,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'stored' => 25,
                'duplicates' => 0,
                'total' => 25,
            ]);

        $this->assertSame(1, $inserts, 'The batch must be one INSERT, not one per event.');
        $this->assertSame(25, EventLog::query()->where('visitor_id', $visitorId)->count());
        $this->assertSame(25, EventLog::query()
            ->where('visitor_id', $visitorId)
            ->where('tracking_method', EventRecorder::METHOD_BROWSER)
            ->where('dedup_status', EventRecorder::STATUS_BROWSER_ONLY)
            ->count());
    }

    public function test_single_event_endpoint_uses_the_same_recorder(): void
    {
        $visitorId = $this->makeVisitor();

        $response = $this->postJson('/api/event-log/store', [
            'event_name' => 'AddToCart',
            'url' => 'http://127.0.0.1:8010/cart',
            'event_data' => ['value' => 10],
        ], [
            'X-Visitor-Id' => $visitorId,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $row = EventLog::query()->where('visitor_id', $visitorId)->firstOrFail();

        $this->assertSame('AddToCart', $row->event_name);
        $this->assertSame(EventRecorder::METHOD_BROWSER, $row->tracking_method);
        $this->assertSame(EventRecorder::STATUS_BROWSER_ONLY, $row->dedup_status);
    }

    public function test_home_page_renders_the_modern_theme_with_batched_rails(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get('/');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();
        $response->assertSee('fm-', false);
        $response->assertDontSee('Farmers Market Day');
        $response->assertDontSee('fm-events-section', false);

        $productQueries = array_values(array_filter(
            $queries,
            fn (array $query) => str_contains($query['query'], 'from `products`')
        ));

        $this->assertLessThanOrEqual(14, count($productQueries), 'Home page product query count grew into N+1 territory.');

        $this->assertTrue(
            collect($productQueries)->contains(fn (array $query) => str_contains($query['query'], '`category_id` in')),
            'The category rails should be one batched query.'
        );

        $this->assertFalse(
            collect($productQueries)->contains(
                fn (array $query) => preg_match('/`category_id` = \\?.*limit 12/', $query['query']) === 1
            ),
            'A per-category product query is still present.'
        );
    }

    private function makeVisitor(): string
    {
        $visitorId = (string) Str::uuid();

        VisitorMaster::create([
            'visitor_id' => $visitorId,
            'ip' => '127.0.0.1',
            'user_agent' => 'Chapter2Test',
            'status' => 1,
        ]);

        $this->visitorIds[] = $visitorId;

        return $visitorId;
    }
}
