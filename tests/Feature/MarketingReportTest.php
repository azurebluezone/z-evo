<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3 item 3 — ROAS / CPA / CAC on the dashboard and the Marketing report.
 *
 * The definition under test (docs/report-completion-plan.md item 6):
 *   ROAS = attributed delivered revenue / marketing spend, same range.
 *
 * Every expected value is recomputed here with plain SQL over the fixture rows
 * this test inserts into an isolated 2099-03 range, so a hard-coded or stale
 * number cannot pass. The fixture set also contains one delivered order with no
 * attribution row, which must be reported as unattributed and kept out of ROAS.
 *
 * Writes are wrapped in a transaction, so live rows are untouched.
 *
 * Marker: MARKETING_REPORT_OK
 */
class MarketingReportTest extends TestCase
{
    use DatabaseTransactions;

    private const FROM = '2037-03-01';
    private const TO = '2037-03-31';

    private function admin(): User
    {
        $admin = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first();

        $this->assertNotNull($admin, 'No Admin user, so the Marketing report cannot be authenticated.');

        return $admin;
    }

    private function insertOrder(int $customerId, float $amount, string $deliveredAt, ?string $source): int
    {
        $orderId = (int) DB::table('orders')->insertGetId([
            'invoice_id' => 'MR-'.strtoupper(Str::random(10)),
            'amount' => $amount,
            'discount' => 0,
            'shipping_charge' => 0,
            'customer_id' => $customerId,
            'order_status' => 'delivered',
            'delivered_at' => $deliveredAt.' 12:00:00',
            'created_at' => $deliveredAt.' 10:00:00',
            'updated_at' => $deliveredAt.' 12:00:00',
        ]);

        if ($source !== null) {
            DB::table('order_attributions')->insert([
                'order_id' => $orderId,
                'source' => $source,
                'model' => 'first_touch',
                'attributed_at' => $deliveredAt.' 10:00:00',
                'created_at' => $deliveredAt.' 10:00:00',
                'updated_at' => $deliveredAt.' 10:00:00',
            ]);
        }

        return $orderId;
    }

    private function insertCustomer(): int
    {
        return (int) DB::table('customers')->insertGetId([
            'name' => 'Marketing Report Customer '.Str::random(6),
            'slug' => 'marketing-report-customer-'.Str::random(8),
            'phone' => '018'.random_int(10000000, 99999999),
            'email' => 'marketing-report-'.Str::random(8).'@example.test',
            'balance' => 0,
            'password' => bcrypt('secret'),
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSpend(string $channel, float $spent, string $day): void
    {
        DB::table('marketing_spends')->insert([
            'channel' => $channel,
            'campaign_name' => 'TEST '.strtoupper(Str::random(5)),
            'spent' => $spent,
            'impressions' => 0,
            'clicks' => 0,
            'spent_at' => $day,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedFixtures(): void
    {
        $customerId = $this->insertCustomer();

        // Two attributed delivered orders for the same customer: 2 orders,
        // 1 new customer, 3000 revenue.
        $this->insertOrder($customerId, 1000.00, '2037-03-05', 'facebook');
        $this->insertOrder($customerId, 2000.00, '2037-03-20', 'facebook');

        // Delivered revenue with no attribution row: reported, never in ROAS.
        $this->insertOrder($customerId, 500.00, '2037-03-10', null);

        $this->insertSpend('facebook', 900.00, '2037-03-05');
        $this->insertSpend('google', 100.00, '2037-03-06');
    }

    /**
     * @return array<string, float|int|null>
     */
    private function expected(): array
    {
        $spend = (float) DB::table('marketing_spends')
            ->whereBetween('spent_at', [self::FROM, self::TO])
            ->sum('spent');

        $attributed = DB::table('orders as o')
            ->join('order_attributions as a', 'a.order_id', '=', 'o.id')
            ->where('o.order_status', 'delivered')
            ->whereBetween('o.delivered_at', [self::FROM.' 00:00:00', self::TO.' 23:59:59'])
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(o.amount), 0) as revenue')
            ->first();

        $unattributed = (float) DB::table('orders as o')
            ->leftJoin('order_attributions as a', 'a.order_id', '=', 'o.id')
            ->where('o.order_status', 'delivered')
            ->whereBetween('o.delivered_at', [self::FROM.' 00:00:00', self::TO.' 23:59:59'])
            ->whereNull('a.id')
            ->sum('o.amount');

        $newCustomers = (int) DB::table('orders as o')
            ->joinSub(
                DB::table('orders')
                    ->selectRaw('customer_id, MIN(delivered_at) as first_delivered_at')
                    ->where('order_status', 'delivered')
                    ->whereNotNull('customer_id')
                    ->whereNotNull('delivered_at')
                    ->groupBy('customer_id'),
                'first',
                function ($join) {
                    $join->on('first.customer_id', '=', 'o.customer_id')
                        ->on('first.first_delivered_at', '=', 'o.delivered_at');
                }
            )
            ->where('o.order_status', 'delivered')
            ->whereBetween('o.delivered_at', [self::FROM.' 00:00:00', self::TO.' 23:59:59'])
            ->distinct()
            ->count('o.customer_id');

        $revenue = (float) $attributed->revenue;
        $orders = (int) $attributed->orders;

        return [
            'spend' => $spend,
            'revenue' => $revenue,
            'orders' => $orders,
            'new_customers' => $newCustomers,
            'roas' => $spend > 0 ? round($revenue / $spend, 2) : null,
            'cpa' => $orders > 0 ? round($spend / $orders, 2) : null,
            'cac' => $newCustomers > 0 ? round($spend / $newCustomers, 2) : null,
            'unattributed' => $unattributed,
        ];
    }

    private function marker(string $key, float|int $value): string
    {
        return 'data-marketing-report-'.$key.'="'.number_format((float) $value, 2, '.', '').'"';
    }

    public function test_report_totals_match_an_independent_sql_recompute(): void
    {
        $this->seedFixtures();
        $expected = $this->expected();

        $this->assertSame(2, $expected['orders'], 'Fixture guard: the attributed delivered order count.');
        $this->assertSame(1, $expected['new_customers'], 'Fixture guard: one new customer.');

        $page = $this->actingAs($this->admin())->get(route('admin.reports.marketing', [
            'from' => self::FROM,
            'to' => self::TO,
        ]));

        $page->assertOk();
        $page->assertSee($this->marker('spend', $expected['spend']), false);
        $page->assertSee($this->marker('revenue', $expected['revenue']), false);
        $page->assertSee($this->marker('roas', $expected['roas']), false);
        $page->assertSee($this->marker('cpa', $expected['cpa']), false);
        $page->assertSee($this->marker('cac', $expected['cac']), false);
        $page->assertSee($this->marker('unattributed', $expected['unattributed']), false);

        // The channel row must carry its own ROAS, not the range total: 3000
        // earned on 900 spent is 3.33x for facebook even though the whole range
        // (which includes google's spend) is 3.00x. Both are recomputed here.
        $facebookSpend = (float) DB::table('marketing_spends')
            ->where('channel', 'facebook')
            ->whereBetween('spent_at', [self::FROM, self::TO])
            ->sum('spent');
        $facebookRevenue = (float) DB::table('orders as o')
            ->join('order_attributions as a', 'a.order_id', '=', 'o.id')
            ->where('o.order_status', 'delivered')
            ->where('a.source', 'facebook')
            ->whereBetween('o.delivered_at', [self::FROM.' 00:00:00', self::TO.' 23:59:59'])
            ->sum('o.amount');
        $channelRoas = round($facebookRevenue / $facebookSpend, 2);

        $page->assertSee('data-channel-row="facebook"', false);
        $page->assertSee('data-channel-roas="'.number_format($channelRoas, 2, '.', '').'"', false);
    }

    public function test_dashboard_shows_the_same_roas_as_the_report(): void
    {
        $this->seedFixtures();
        $expected = $this->expected();

        $dashboard = $this->actingAs($this->admin())->get(route('admin.reports.dashboard', [
            'from' => self::FROM,
            'to' => self::TO,
        ]));

        $dashboard->assertOk();
        $dashboard->assertSee('data-roas="'.number_format((float) $expected['roas'], 2, '.', '').'"', false);
        $dashboard->assertSee(
            'data-marketing-spend-total="'.number_format((float) $expected['spend'], 2, '.', '').'"',
            false
        );
    }

    public function test_a_range_without_spend_or_orders_reports_zero_and_no_roas(): void
    {
        $page = $this->actingAs($this->admin())->get(route('admin.reports.marketing', [
            'from' => '1990-01-01',
            'to' => '1990-01-31',
        ]));

        $page->assertOk();
        $page->assertSee('data-marketing-report-spend="0.00"', false);
        $page->assertSee('data-marketing-report-revenue="0.00"', false);
        $page->assertSee('data-marketing-report-roas=""', false);
    }
}
