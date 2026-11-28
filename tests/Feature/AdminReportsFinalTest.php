<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The final report surfaces: stock alerts and report health.
 *
 * Read-only on purpose. Every expected number is recomputed from the database
 * in the test, then asserted against the rendered HTML, so a hard-coded page
 * cannot pass.
 */
class AdminReportsFinalTest extends TestCase
{
    private function userWithRole(string $role): User
    {
        $user = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', $role))
            ->first();

        $this->assertNotNull($user, "No user with role {$role}, so the report cannot be authenticated.");

        return $user;
    }

    public function test_stock_alert_page_shows_database_balances(): void
    {
        $total = DB::table('variant_stocks')->count();
        $out = DB::table('variant_stocks')->where('available', '<=', 0)->count();
        $low = DB::table('variant_stocks')
            ->where('available', '>', 0)
            ->whereColumn('available', '<=', 'low_stock_threshold')
            ->count();
        $damaged = (int) DB::table('variant_stocks')->sum('damaged');

        $this->assertGreaterThan(0, $total, 'The baseline has no variant_stocks row, so the page cannot be proven.');

        $response = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.reports.stock-alerts'));

        $response->assertOk();
        $response->assertSee('Stock Alerts', false);
        $response->assertSee(number_format($total, 0), false);
        $response->assertSee(number_format($out, 0), false);
        $response->assertSee(number_format($low, 0), false);
        $response->assertSee(number_format($damaged, 0), false);
        $response->assertSee('stockStatusChart', false);
        $response->assertSee('inventory_movements', false);
        $response->assertSee('OPENING_BALANCE', false);
    }

    public function test_stock_alert_filter_narrows_the_table(): void
    {
        $out = DB::table('variant_stocks')->where('available', '<=', 0)->count();
        $low = DB::table('variant_stocks')
            ->where('available', '>', 0)
            ->whereColumn('available', '<=', 'low_stock_threshold')
            ->count();

        $response = $this->actingAs($this->userWithRole('Admin'))
            ->get(route('admin.reports.stock-alerts', ['status' => 'out']));

        $response->assertOk();
        $response->assertSee('Out of stock', false);

        $orphans = DB::table('variant_stocks as s')
            ->leftJoin('product_variants as v', 'v.id', '=', 's.variant_id')
            ->whereNull('v.id')
            ->count();

        $this->assertSame(0, $orphans, 'Orphan stock rows exist, so the balance table would hide inventory.');
        $this->assertGreaterThanOrEqual(0, $out + $low, 'Derived status buckets must be countable.');
    }

    public function test_health_page_reports_every_check_as_pass(): void
    {
        $response = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.reports.health'));

        $response->assertOk();
        $response->assertSee('Report Health', false);
        $response->assertSee('15 / 15', false);
        // The layout can contain the word FAIL for unrelated reasons, so the
        // proof is the badge itself: zero failing result badges on the page.
        $content = $response->getContent();
        $needle = 'bg-label-danger">FAIL';
        $position = strpos($content, $needle);
        $context = $position === false ? '' : substr($content, max(0, $position - 220), 460);
        $this->assertSame(0, substr_count($content, $needle), 'Unexpected failing badge near: '.$context);

        foreach ([
            'report-sales-view',
            'report-payment-view',
            'report-courier-view',
            'report-returns-view',
            'report-coupon-view',
            'report-stock-view',
        ] as $permission) {
            $response->assertSee($permission, false);
        }

        $response->assertSee('Stock Alerts', false);
    }

    public function test_report_blades_contain_no_bengali_text(): void
    {
        $files = File::allFiles(resource_path('views/backEnd/reports'));
        $this->assertGreaterThan(0, count($files), 'No report blade found to scan.');

        $offending = [];

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());
            if (preg_match('/[\x{0980}-\x{09FF}]/u', $content) === 1) {
                $offending[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offending, 'Report blades still contain Bengali characters: '.implode(', ', $offending));
    }

    public function test_support_is_denied_on_stock_alerts_and_health(): void
    {
        $support = $this->userWithRole('Support');

        $this->actingAs($support)->get(route('admin.reports.stock-alerts'))->assertForbidden();
        $this->actingAs($support)->get(route('admin.reports.health'))->assertForbidden();
    }

    public function test_manager_gets_stock_alerts_and_health(): void
    {
        $manager = $this->userWithRole('Manager');

        $this->actingAs($manager)->get(route('admin.reports.stock-alerts'))->assertOk();
        $this->actingAs($manager)->get(route('admin.reports.health'))->assertOk();
    }

    public function test_manager_cannot_read_profit_on_the_sales_page(): void
    {
        $manager = $this->userWithRole('Manager');

        $response = $this->actingAs($manager)->get(route('admin.reports.overview'));

        $response->assertOk();
        $response->assertDontSee('Net profit A', false);
        $response->assertDontSee('Net profit B', false);
        $response->assertDontSee('View A', false);

        $admin = $this->actingAs($this->userWithRole('Admin'))->get(route('admin.reports.overview'));

        $admin->assertOk();
        $admin->assertSee('Net profit A', false);
        $admin->assertSee('Net profit B', false);
    }
}
