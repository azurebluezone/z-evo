<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Admin\ReportMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Coupon revenue follows the report metric dictionary:
 * revenue = SUM(orders.amount) over DISTINCT coupon_usages.order_id.
 *
 * The fixture inserts a second usage row for an order that already has one,
 * which is the shape that used to multiply the order amount by its usage
 * count. DatabaseTransactions keeps the development database untouched.
 */
class AdminReportCouponRevenueTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Admin'))
            ->first();

        $this->assertNotNull($user, 'No Admin user, so the coupon report cannot be authenticated.');

        return $user;
    }

    private function distinctCouponRevenue(?string $couponCode = null): float
    {
        $orderIds = DB::table('coupon_usages')->whereNotNull('order_id');

        if ($couponCode !== null) {
            $orderIds->where('coupon_code', $couponCode);
        }

        return (float) DB::table('orders')
            ->whereIn('id', $orderIds->select('order_id')->distinct())
            ->sum('amount');
    }

    public function test_coupon_revenue_counts_an_order_once_when_the_same_coupon_is_used_twice(): void
    {
        $usage = DB::table('coupon_usages')
            ->whereNotNull('order_id')
            ->orderBy('id')
            ->first();

        $this->assertNotNull($usage, 'The baseline has no linked coupon usage.');

        DB::table('coupon_usages')->insert([
            'coupon_id' => $usage->coupon_id,
            'order_id' => $usage->order_id,
            'coupon_code' => $usage->coupon_code,
            'source' => $usage->source,
            'discount_amount' => 10.00,
            'created_at' => $usage->created_at,
            'updated_at' => $usage->updated_at,
        ]);

        $expectedRevenue = $this->distinctCouponRevenue();
        $expectedRowRevenue = $this->distinctCouponRevenue($usage->coupon_code);

        $naiveRevenue = (float) DB::table('coupon_usages as u')
            ->join('orders as o', 'o.id', '=', 'u.order_id')
            ->sum('o.amount');

        $this->assertGreaterThan(
            $expectedRevenue,
            $naiveRevenue,
            'The fixture must reproduce the duplicate revenue before the fix.'
        );

        $response = $this->actingAs($this->admin())->get(route('admin.reports.coupons'));

        $response->assertOk();
        $response->assertViewHas('metrics', function (array $metrics) use ($expectedRevenue): bool {
            return abs((float) $metrics['revenue'] - $expectedRevenue) < 0.01;
        });
        $response->assertViewHas('rows', function (array $rows) use ($usage, $expectedRowRevenue): bool {
            foreach ($rows as $row) {
                if ($row['code'] === $usage->coupon_code) {
                    return abs((float) $row['revenue'] - $expectedRowRevenue) < 0.01;
                }
            }

            return false;
        });

        $firstUsage = DB::table('coupon_usages')->min('created_at');
        $lastUsage = DB::table('coupon_usages')->max('created_at');

        $summary = app(ReportMetricsService::class)->summary(
            Carbon::parse($firstUsage)->startOfDay(),
            Carbon::parse($lastUsage)->endOfDay(),
        );

        $this->assertSame(
            round($expectedRevenue, 2),
            round((float) $summary['coupon_revenue'], 2),
            'The dashboard/email coupon revenue must match the distinct-order sum.'
        );
    }
}
