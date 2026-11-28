<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 2 item 3 — district and division breakdowns on the three money reports.
 *
 * Read-only on purpose: the suite runs against the development database, so
 * nothing here creates, resets or deletes a row. The proof is an invariant the
 * plan asks for (docs/report-completion-plan.md Phase 2 item 5): the district
 * rows must add up to the national total for the same range, which only holds
 * when the NULL geography lands in an explicit Unassigned bucket instead of
 * being dropped by an inner join.
 *
 * Marker: GEO_BREAKDOWN_OK
 */
class AdminReportGeoBreakdownTest extends TestCase
{
    private const DELIVERED = ['delivered', '4'];

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so the geography breakdown cannot be authenticated.');

        return $admin;
    }

    private function geoTotal(float $value): string
    {
        return 'data-geo-total="'.number_format($value, 2, '.', '').'"';
    }

    public function test_sales_breakdown_adds_up_to_the_delivered_total(): void
    {
        $national = (float) DB::table('orders')
            ->whereIn('order_status', self::DELIVERED)
            ->sum('amount');

        $response = $this->actingAs($this->admin())->get(route('admin.reports.overview'));

        $response->assertOk();
        $response->assertSee('Delivered orders by division and district', false);
        $response->assertSee($this->geoTotal($national), false);
    }

    public function test_returns_breakdown_adds_up_to_the_refund_required_total(): void
    {
        $national = (float) DB::table('order_returns')
            ->where('refund_required', 1)
            ->sum('refund_amount');

        $response = $this->actingAs($this->admin())->get(route('admin.reports.returns'));

        $response->assertOk();
        $response->assertSee('Returns by division and district', false);
        $response->assertSee($this->geoTotal($national), false);
    }

    public function test_courier_breakdown_adds_up_to_the_cost_total(): void
    {
        $national = (float) DB::table('courier_shipments')->sum('shipping_cost');

        $response = $this->actingAs($this->admin())->get(route('admin.reports.couriers'));

        $response->assertOk();
        $response->assertSee('Shipments by division and district', false);
        $response->assertSee($this->geoTotal($national), false);
    }

    public function test_breakdown_follows_the_date_filter(): void
    {
        $query = '?start_date=1990-01-01&end_date=1990-01-02';

        foreach ([
            route('admin.reports.overview'),
            route('admin.reports.returns'),
            route('admin.reports.couriers'),
        ] as $url) {
            $response = $this->actingAs($this->admin())->get($url.$query);

            $response->assertOk();
            $response->assertSee($this->geoTotal(0), false);
        }
    }
}
