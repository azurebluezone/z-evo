<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PhoneBlock;
use App\Models\User;
use App\Models\VisitorMaster;
use App\Services\Analytics\VisitorFilter;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * The visitor analytics slice, tested against the development database the way
 * the admin actually uses it:
 *
 *   - both placeholders are gone: the chart has a data payload and the map has
 *     Leaflet plus a points payload;
 *   - one date input means that exact day, not "from that day onwards";
 *   - the orders grid carries an order phone and a customer phone cell;
 *   - a blocked phone is refused by the global middleware on a frontend write.
 *
 * No RefreshDatabase: the recorded 2026-11-02 count is the acceptance number,
 * and refreshing would erase it. Rows created here are deleted in finally.
 */
class VisitorAnalyticsPhoneBlockTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so this test cannot authenticate.');

        return $admin;
    }

    public function test_analytics_page_renders_chart_and_map_containers_with_payloads(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/visitors/manage');

        $response->assertOk();

        // Chart: a real ApexCharts container plus the server-built series.
        $response->assertSee('id="trafficChart"', false);
        $response->assertSee('visitorAnalyticsChartData', false);
        $response->assertSee('admin-page-visitors-analytics.js', false);

        // Map: a real Leaflet container, its assets and its points payload.
        $response->assertSee('id="visitorMap"', false);
        $response->assertSee('leaflet.js', false);
        $response->assertSee('leaflet.css', false);
        $response->assertSee('visitorAnalyticsMapData', false);

        // The old gradient placeholders must not come back.
        $response->assertDontSee('linear-gradient(135deg, #667eea', false);
        $response->assertDontSee('Visitor Traffic Chart', false);
        $response->assertDontSee('Visitor Location Map', false);
    }

    public function test_one_date_filter_means_that_exact_day(): void
    {
        $date = '2026-11-02';

        $expected = VisitorMaster::query()->whereDate('created_at', $date)->count();
        $otherDay = VisitorMaster::query()->whereDate('created_at', '2026-11-06')->count();

        $this->assertSame(348, $expected, 'The recorded acceptance count for 2026-11-02 changed.');
        $this->assertGreaterThan(0, $otherDay, 'The "other day" control is empty, so the range bug cannot be detected.');

        $request = Request::create('/admin/visitors/manage', 'GET', ['from' => $date]);
        $filtered = (new VisitorFilter())->apply(VisitorMaster::query(), $request)->count();

        $this->assertSame($expected, $filtered, 'A single from= must select only that day.');

        $response = $this->actingAs($this->admin())->get('/admin/visitors/manage?from=' . $date);
        $response->assertOk();
        $response->assertSee('Total: ' . number_format($expected), false);
    }

    public function test_impossible_date_is_ignored_instead_of_crashing(): void
    {
        $request = Request::create('/admin/visitors/list', 'GET', ['from' => '2026-02-31']);

        $count = (new VisitorFilter())->apply(VisitorMaster::query(), $request)->count();

        $this->assertSame(
            VisitorMaster::query()->count(),
            $count,
            'An impossible date must not filter anything and must not throw.'
        );

        $this->actingAs($this->admin())
            ->get('/admin/visitors/list?from=2026-02-31')
            ->assertOk();
    }

    public function test_agent_is_an_alias_for_user_agent(): void
    {
        $byAgent = Request::create('/admin/visitors/list', 'GET', ['agent' => 'chrome']);
        $byColumn = Request::create('/admin/visitors/list', 'GET', ['user_agent' => 'chrome']);

        $agentCount = (new VisitorFilter())->apply(VisitorMaster::query(), $byAgent)->count();
        $columnCount = (new VisitorFilter())->apply(VisitorMaster::query(), $byColumn)->count();

        $this->assertGreaterThan(0, $agentCount, 'No Chrome visitor exists, so the alias test is meaningless.');
        $this->assertSame($columnCount, $agentCount);
    }

    public function test_orders_datatable_has_order_phone_and_customer_phone_cells(): void
    {
        $order = Order::query()
            ->with(['shipping', 'customer'])
            ->whereHas('customer', fn ($q) => $q->whereNotNull('phone')->where('phone', '<>', ''))
            ->whereHas('shipping', fn ($q) => $q->whereNotNull('phone')->where('phone', '<>', ''))
            ->first();

        if ($order === null) {
            $this->markTestSkipped('No order with both a customer phone and a delivery phone.');
        }

        $response = $this->actingAs($this->admin())->get(
            '/admin/order/all/datatable?draw=1&start=0&length=20&search%5Bvalue%5D=' . urlencode((string) $order->invoice_id)
        );

        $response->assertOk();

        $rows = $response->json('data');
        $row = collect($rows)->first(
            fn (array $cells) => isset($cells[1]) && str_contains($cells[1], (string) $order->invoice_id)
        );

        $this->assertNotNull($row, 'The searched order was not returned by the datatable.');
        $this->assertCount(9, $row, 'The orders table header and the rendered row cells disagree.');

        $this->assertStringContainsString((string) $order->shipping->phone, $row[4], 'Order phone cell is missing.');
        $this->assertStringContainsString((string) $order->customer->phone, $row[5], 'Customer phone cell is missing.');
    }

    public function test_blocked_phone_is_refused_by_global_middleware(): void
    {
        $phone = '01999999991';
        $normalized = PhoneBlock::normalize($phone);

        $this->assertSame($phone, $normalized);

        PhoneBlock::where('phone', $normalized)->delete();
        $block = PhoneBlock::create([
            'phone' => $normalized,
            'original_phone' => $phone,
            'reason' => 'test',
            'is_active' => true,
        ]);

        try {
            $response = $this->withHeader('Accept', 'application/json')
                ->post('/customer/signin', ['phone' => $phone, 'password' => 'irrelevant']);

            $response->assertStatus(403);
            $response->assertJson(['status' => 'blocked']);

            $this->assertSame(1, (int) $block->fresh()->hits);

            // The admin must not be locked out of the page that manages blocks.
            $this->actingAs($this->admin())->get('/admin/visitors/phone-blocks?phone=' . $phone)
                ->assertOk();

            // A non-blocked number must not be refused; it reaches the
            // controller, which answers with its own redirect.
            $allowed = $this->withHeader('Accept', 'application/json')
                ->post('/customer/signin', ['phone' => '01711111111', 'password' => 'irrelevant']);

            $this->assertNotSame(403, $allowed->getStatusCode(), 'The guard blocked a phone that is not on the list.');
        } finally {
            PhoneBlock::where('phone', $normalized)->delete();
        }
    }
}
