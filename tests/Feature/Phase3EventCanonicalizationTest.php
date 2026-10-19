<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SocialEventDashboardController;
use App\Http\Controllers\Admin\SocialEventPlatformController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase3EventCanonicalizationTest extends TestCase
{
    public function test_collector_canonicalizes_begin_checkout_to_initiate_checkout(): void
    {
        $visitorId = (string) Str::uuid();
        $markerUrl = 'https://phase3.local/checkout?marker=' . Str::random(12);

        DB::table('visitors_master')->insert([
            'visitor_id' => $visitorId,
            'ip' => '127.0.0.1',
            'user_agent' => 'phase3-test',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/events/collect', [
            'visitor_id' => $visitorId,
            'events' => [
                [
                    'name' => 'BeginCheckout',
                    'url' => $markerUrl,
                    'ts' => now()->toIso8601String(),
                ],
            ],
        ]);

        $response->assertOk();

        $saved = DB::table('event_logs')
            ->where('visitor_id', $visitorId)
            ->where('url', $markerUrl)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($saved);
        $this->assertSame('InitiateCheckout', $saved->event_name);

        DB::table('event_logs')->where('visitor_id', $visitorId)->where('url', $markerUrl)->delete();
        DB::table('visitors_master')->where('visitor_id', $visitorId)->delete();
    }

    public function test_dashboard_and_platform_use_initiate_checkout_consistently(): void
    {
        $dashboard = new SocialEventDashboardController();
        $dashboardView = $dashboard->index(Request::create('/admin/social-events/dashboard', 'GET', ['days' => 7]));
        $dashboardData = $dashboardView->getData();

        $allowedEvents = (array) ($dashboardData['allowedEvents'] ?? []);
        $this->assertContains('InitiateCheckout', $allowedEvents);
        $this->assertNotContains('BeginCheckout', $allowedEvents);

        $visitorId = (string) Str::uuid();
        $markerUrl = 'https://phase3.local/checkout?platform=' . Str::random(12);
        $now = now();

        DB::table('visitors_master')->insert([
            'visitor_id' => $visitorId,
            'ip' => '127.0.0.1',
            'user_agent' => 'phase3-platform-test',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('event_logs')->insert([
            [
                'visitor_id' => $visitorId,
                'event_name' => 'BeginCheckout',
                'url' => $markerUrl,
                'event_time' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'visitor_id' => $visitorId,
                'event_name' => 'InitiateCheckout',
                'url' => $markerUrl,
                'event_time' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $platform = new SocialEventPlatformController();
        $platformView = $platform->facebook(Request::create('/admin/social-events/facebook', 'GET', [
            'days' => 7,
            'event' => 'BeginCheckout',
        ]));
        $platformData = $platformView->getData();

        $this->assertSame('InitiateCheckout', $platformData['event']);

        $recentLogs = $platformData['recentLogs'];
        $found = collect($recentLogs)->contains(function ($row) use ($markerUrl) {
            return (string) ($row->url ?? '') === $markerUrl
                && (string) ($row->event_name ?? '') === 'InitiateCheckout';
        });

        $this->assertTrue($found);

        DB::table('event_logs')->where('visitor_id', $visitorId)->where('url', $markerUrl)->delete();
        DB::table('visitors_master')->where('visitor_id', $visitorId)->delete();
    }
}
