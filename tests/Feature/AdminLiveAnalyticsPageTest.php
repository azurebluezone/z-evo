<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * T4.1 — the "Live now" page.
 *
 * The same route serves two surfaces:
 *   - a browser gets the Blade page;
 *   - the dashboard poller sends `Accept: application/json` and gets the payload.
 *
 * These assertions are read-only on purpose: the test suite runs against the
 * development database, so nothing here creates or deletes analytics rows.
 */
class AdminLiveAnalyticsPageTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the live page cannot be authenticated.');

        return $admin;
    }

    public function test_live_page_renders_for_a_browser(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.analytics.live'));

        $response->assertOk();
        $response->assertSee('Live now', false);
        $response->assertSee('id="live-map"', false);
        $response->assertSee('admin/assets/vendor/libs/leaflet/leaflet.js', false);
        $response->assertSee('id="stat-visitors"', false);
        $response->assertSee('id="stat-events-per-min"', false);
        $response->assertSee('id="stat-page"', false);
        $response->assertSee('id="stat-channel"', false);
    }

    /**
     * The page's <script> must land inside the document: a block placed after
     * @endsection is compiled and then silently dropped, which is exactly how
     * the courier buttons died before.
     */
    public function test_live_page_script_is_inside_the_document(): void
    {
        $html = (string) $this->actingAs($this->admin())
            ->get(route('admin.analytics.live'))
            ->getContent();

        $this->assertStringContainsString('live-auto', $html);
        $this->assertLessThan(
            strpos($html, '</html>'),
            strpos($html, 'function refresh()'),
            'The live page script was rendered outside the document.'
        );
    }

    public function test_live_json_keeps_the_poller_contract_and_adds_stats_and_map(): void
    {
        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.analytics.live'));

        $response->assertOk();
        $response->assertJsonStructure([
            'rows',
            'live_visitors',
            'server_time',
            'stats' => [
                'window_minutes',
                'visitors_now',
                'visitors_prev',
                'visitors_delta',
                'events_now',
                'events_per_min',
                'events_delta',
                'top_page',
                'top_channel',
            ],
            'map' => ['scope', 'points', 'total', 'max'],
        ]);

        $stats = $response->json('stats');
        $this->assertSame(5, $stats['window_minutes']);
        $this->assertIsNumeric($stats['events_per_min']);

        // Deltas compare equal-length windows, so they can never be null.
        $this->assertIsNumeric($stats['visitors_delta']);
        $this->assertIsNumeric($stats['events_delta']);
    }

    public function test_map_points_carry_coordinates_and_a_count(): void
    {
        $map = $this->actingAs($this->admin())
            ->getJson(route('admin.analytics.live'))
            ->json('map');

        $this->assertContains($map['scope'], ['live', 'today']);

        foreach ($map['points'] as $point) {
            $this->assertArrayHasKey('division', $point);
            $this->assertArrayHasKey('lat', $point);
            $this->assertArrayHasKey('lng', $point);
            $this->assertArrayHasKey('visitors', $point);
            $this->assertGreaterThan(0, $point['visitors']);
        }
    }

    public function test_rows_expose_division_and_city_for_the_map_filter(): void
    {
        $rows = $this->actingAs($this->admin())
            ->getJson(route('admin.analytics.live'))
            ->json('rows');

        foreach ($rows as $row) {
            $this->assertArrayHasKey('division', $row);
            $this->assertArrayHasKey('city', $row);
        }

        // The live table contract is 25 rows, newest first.
        $this->assertLessThanOrEqual(25, count($rows));
    }
}
