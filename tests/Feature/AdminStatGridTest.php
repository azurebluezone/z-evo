<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The admin header is one template whose numbers change with the page.
 *
 * These tests pin that contract from the outside:
 *   - /admin/coupon counts coupons, /admin/courier counts courier accounts,
 *     /admin/products counts products - not the customer trio copied around;
 *   - the customer page still counts customers, from the whole table;
 *   - the fourth card is "Showing This Page" and never a fabricated number
 *     when the page has no list;
 *   - the dashboard is the one documented exception.
 *
 * Read-only against the development database: expected values are read with
 * the same queries the service uses, so the assertions survive new seed data.
 */
class AdminStatGridTest extends TestCase
{
    private function admin(): User
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first()
            ?? User::query()->first();

        $this->assertNotNull($admin, 'No admin user, so no admin page can be authenticated.');

        return $admin;
    }

    private function html(string $url): string
    {
        $response = $this->actingAs($this->admin())->get($url);
        $response->assertOk();

        return $response->getContent();
    }

    /**
     * The rendered number next to data-stat="<slug>".
     */
    private function value(string $html, string $slug): string
    {
        $this->assertMatchesRegularExpression(
            '/data-stat="' . preg_quote($slug, '/') . '"[^>]*>\s*([^<]*)</',
            $html,
            "Card [{$slug}] is missing from the header."
        );

        preg_match('/data-stat="' . preg_quote($slug, '/') . '"[^>]*>\s*([^<]*)</', $html, $m);

        return trim($m[1]);
    }

    private function activeCount(string $table): int
    {
        return (int) DB::table($table)->whereIn('status', config('admin_stats.active_values'))->count();
    }

    public function test_header_appears_exactly_once(): void
    {
        $html = $this->html(route('customers.index'));

        $this->assertSame(1, substr_count($html, 'data-admin-stat-grid'));
    }

    public function test_customer_page_counts_customers_from_the_whole_table(): void
    {
        $html = $this->html(route('customers.index'));
        $total = (int) DB::table('customers')->count();
        $active = $this->activeCount('customers');

        $this->assertSame(number_format($total), $this->value($html, 'total-customers'));
        $this->assertSame(number_format($active), $this->value($html, 'active-customers'));
        $this->assertSame(number_format($total - $active), $this->value($html, 'inactive-customers'));
        $this->assertNotSame('—', $this->value($html, 'inactive-customers'), 'Inactive must be a number, not a dash.');
    }

    public function test_coupon_page_counts_coupons_not_customers(): void
    {
        $html = $this->html(route('coupons.index'));

        $this->assertStringContainsString('Total Coupons', $html);
        $this->assertStringContainsString('Active Coupons', $html);
        $this->assertStringContainsString('Inactive Coupons', $html);
        $this->assertSame(number_format(DB::table('coupons')->count()), $this->value($html, 'total-coupons'));
        $this->assertSame(number_format($this->activeCount('coupons')), $this->value($html, 'active-coupons'));
    }

    public function test_courier_page_counts_courier_accounts(): void
    {
        $html = $this->html(route('admin.courier.index'));

        $this->assertStringContainsString('Total Couriers', $html);
        $this->assertSame(number_format(DB::table('courier_apis')->count()), $this->value($html, 'total-couriers'));
        $this->assertSame(number_format($this->activeCount('courier_apis')), $this->value($html, 'active-couriers'));
    }

    public function test_product_page_counts_products(): void
    {
        $html = $this->html(route('products.index'));

        $this->assertStringContainsString('Total Products', $html);
        $this->assertSame(number_format(DB::table('products')->count()), $this->value($html, 'total-products'));
        $this->assertSame(number_format($this->activeCount('products')), $this->value($html, 'active-products'));
    }

    public function test_order_page_uses_order_language_not_active_inactive(): void
    {
        $html = $this->html(route('admin.orders', ['slug' => 'all']));

        $this->assertStringContainsString('Total Orders', $html);
        $this->assertStringContainsString('Pending Orders', $html);
        $this->assertStringContainsString('Delivered Orders', $html);
        $this->assertSame(
            number_format(DB::table('orders')->where('order_status', 'pending')->count()),
            $this->value($html, 'pending-orders')
        );
    }

    /**
     * The duplicate KPI band regression: order/all and order-report rendered the
     * shared four cards plus their own four KPI cards, so the page opened with
     * two identical-looking stat bands. Pages that hand-wrote a band now rely on
     * the shared one, and the only icons left in the content area are its four.
     */
    public function test_pages_render_exactly_one_stat_band(): void
    {
        $urls = [
            route('admin.orders', ['slug' => 'all']),
            route('admin.order_report'),
            route('admin.recommendations.index'),
            route('reviews.pending'),
            route('admin.courier.index'),
            route('admin.courier.settings'),
        ];

        foreach ($urls as $url) {
            $html = $this->html($url);

            $this->assertSame(1, substr_count($html, 'data-admin-stat-grid'), "The shared stat grid is not rendered exactly once on {$url}.");
            $this->assertSame(4, substr_count($html, 'avatar-initial'), "A second KPI band is still rendered on {$url}.");
        }
    }

    public function test_module_pages_show_a_page_count(): void
    {
        $html = $this->html(route('coupons.index'));
        $total = (int) DB::table('coupons')->count();

        $this->assertSame(number_format(min(20, $total)), $this->value($html, 'showing-this-page'));
    }

    public function test_settings_page_renders_no_stat_band_at_all(): void
    {
        // Configuration screens (website setup, general settings, tracking
        // setup, account pages) used to fall back to the shop overview and
        // print "Total Products" on a page with nothing to do with products.
        // Reported 2026-10-28: the band is now OFF there, not re-labelled.
        foreach ([
            route('admin.website-setup.header'),
            route('admin.website-setup.footer'),
            route('admin.homepage.settings.edit'),
            route('settings.index'),
        ] as $url) {
            $this->assertStringNotContainsString(
                'data-admin-stat-grid',
                $this->html($url),
                "A settings page must not render the shared stat band: {$url}"
            );
        }
    }

    public function test_website_setup_hub_opens_the_real_header_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.website-setup.index'))
            ->assertRedirect(route('admin.website-setup.header'));
    }

    public function test_dashboard_is_the_documented_exception(): void
    {
        $html = $this->html(route('dashboard'));

        $this->assertStringNotContainsString('data-admin-stat-grid', $html);
        $this->assertStringContainsString('Total Customers', $html, 'The dashboard keeps its own KPI band.');
    }

    public function test_route_overrides_with_dotted_names_are_honoured(): void
    {
        $html = $this->html(route('reviews.pending'));

        $this->assertStringContainsString('Total Pending Reviews', $html);
        $this->assertSame(
            number_format(DB::table('reviews')->where('status', 'pending')->count()),
            $this->value($html, 'total-pending-reviews')
        );
    }

    public function test_scoped_module_counts_only_the_rows_the_page_shows(): void
    {
        $html = $this->html(route('admin.recommendations.index'));

        $this->assertStringContainsString('Total Recommended Products', $html);
        $this->assertSame(
            number_format(DB::table('products')->where('is_recommended', 1)->count()),
            $this->value($html, 'total-recommended-products')
        );
    }

    public function test_refunds_count_only_returns_that_need_money_back(): void
    {
        $html = $this->html(route('admin.refunds.index'));
        $total = DB::table('order_returns')->where('restocked', 1)->where('refund_required', 1)->count();

        $this->assertSame(number_format($total), $this->value($html, 'total-refunds'));
    }

    public function test_a_form_page_has_no_page_count(): void
    {
        $html = $this->html(route('products.create'));

        $this->assertStringContainsString('data-admin-stat-grid', $html);
        $this->assertSame('—', $this->value($html, 'showing-this-page'));
    }

    public function test_orders_are_counted_with_order_language_on_the_report(): void
    {
        $html = $this->html(route('admin.order_report'));

        $this->assertStringContainsString('Pending Orders', $html);
        $this->assertSame(
            number_format(DB::table('orders')->where('order_status', 'pending')->count()),
            $this->value($html, 'pending-orders')
        );
    }

    public function test_every_configured_table_exists(): void
    {
        $definitions = array_merge(
            array_values(config('admin_stats.modules')),
            array_values(config('admin_stats.routes')),
            config('admin_stats.overview', [])
        );

        foreach ($definitions as $definition) {
            $table = $definition['table'] ?? $definition['metrics'][0]['table'] ?? null;

            if ($table === null) {
                continue;
            }

            $this->assertTrue(
                Schema::hasTable($table),
                'config/admin_stats.php points at a missing table: ' . $table
            );
        }
    }
}
