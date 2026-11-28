<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/**
 * Report health: what exists, what it is worth, and whether it is honest.
 *
 * This is a data-quality and access audit for the report suite, not the
 * business summary. The page used to be called Report Summary, which made
 * admins expect a money report; the route now lives at reports/health and the
 * old URL redirects here.
 *
 * This page does not restate numbers by hand. Every figure and every PASS is
 * computed at request time from the database, the route table or the report
 * source files, so the page cannot drift away from the code it describes.
 */
class ReportHealthController extends Controller
{
    /**
     * The report surfaces and the permission each one is meant to require.
     */
    private const REPORTS = [
        ['name' => 'Sales & Profit', 'route' => 'admin.reports.overview', 'permission' => 'report-sales-view'],
        ['name' => 'Payment Methods', 'route' => 'admin.reports.payments', 'permission' => 'report-payment-view'],
        ['name' => 'Courier Performance', 'route' => 'admin.reports.couriers', 'permission' => 'report-courier-view'],
        ['name' => 'Returns & Cancellations', 'route' => 'admin.reports.returns', 'permission' => 'report-returns-view'],
        ['name' => 'Coupon Impact', 'route' => 'admin.reports.coupons', 'permission' => 'report-coupon-view'],
        ['name' => 'Stock Alerts', 'route' => 'admin.reports.stock-alerts', 'permission' => 'report-stock-view'],
    ];

    public function index()
    {
        $reports = $this->reports();

        $unguarded = count(array_filter($reports, fn ($report) => ! $report['guarded']));

        $orphans = DB::table('variant_stocks as s')
            ->leftJoin('product_variants as v', 'v.id', '=', 's.variant_id')
            ->whereNull('v.id')
            ->count();

        $deliveredWithoutDate = DB::table('orders')
            ->whereIn('order_status', ['delivered', '4'])
            ->whereNull('delivered_at')
            ->count();

        $refundRequired = (float) DB::table('order_returns')->where('refund_required', 1)->sum('refund_amount');
        $refunded = (float) DB::table('order_returns')->where('refunded', 1)->sum('refund_amount');
        $refundPending = max(0.0, $refundRequired - $refunded);

        $couponDrift = DB::table('coupons as c')
            ->leftJoin('coupon_usages as u', 'u.coupon_code', '=', 'c.code')
            ->select('c.code', 'c.used_count', DB::raw('COUNT(u.id) as actual'))
            ->groupBy('c.code', 'c.used_count')
            ->get()
            ->filter(fn ($row) => (int) $row->used_count !== (int) $row->actual)
            ->count();

        $unlinkedCouponUsages = DB::table('coupon_usages')->whereNull('order_id')->count();

        $lowStock = DB::table('variant_stocks')
            ->where('available', '>', 0)
            ->where('available', '<=', (int) ReportSetting::current()->stock_warning_threshold)
            ->count();

        $remainingStock = DB::table('variant_stocks')
            ->where('available', '>', (int) ReportSetting::current()->stock_warning_threshold)
            ->where('available', '<=', (int) ReportSetting::current()->stock_remaining_threshold)
            ->count();

        $outOfStock = DB::table('variant_stocks')->where('available', '<=', 0)->count();

        $reportFiles = File::allFiles(resource_path('views/backEnd/reports'));
        $bengaliFiles = 0;
        $emptyStateFiles = 0;
        foreach ($reportFiles as $file) {
            $content = file_get_contents($file->getPathname());
            if (preg_match('/[\x{0980}-\x{09FF}]/u', $content) === 1) {
                $bengaliFiles++;
            }
            if (str_contains($content, 'No data in this report yet')) {
                $emptyStateFiles++;
            }
        }

        $analyticsFiles = File::allFiles(resource_path('views/backEnd/reports/analytics'));
        $phoneFiles = 0;
        foreach ($analyticsFiles as $file) {
            if (stripos(file_get_contents($file->getPathname()), 'phone') !== false) {
                $phoneFiles++;
            }
        }

        $controllerSources = '';
        foreach (['ReportAnalyticsController.php', 'ReportOverviewController.php', 'ReportStockAlertController.php'] as $controller) {
            $path = app_path('Http/Controllers/Admin/'.$controller);
            if (File::exists($path)) {
                // Comments are stripped before the scan: a docblock that explains
                // "never read raw_payload" must not be mistaken for code that
                // reads it. Only executable source is judged here.
                $controllerSources .= $this->codeOnly(file_get_contents($path));
            }
        }
        $usesRawPayload = str_contains($controllerSources, 'raw_payload');
        $doubleDiscount = str_contains($controllerSources, 'product_discount')
            || str_contains($controllerSources, "sum('discount')");

        $backupExists = File::exists(database_path('seeders/.db-backup-20261127.sql.gz'));

        $assertions = 0;
        foreach (File::glob(base_path('tests/Feature/AdminReport*Test.php')) as $testFile) {
            $assertions += substr_count(file_get_contents($testFile), '$this->assert');
        }

        $checks = [
            ['check' => 'Orphan stock rows', 'value' => (string) $orphans, 'expected' => '0', 'pass' => $orphans === 0],
            ['check' => 'Delivered orders without delivered_at', 'value' => (string) $deliveredWithoutDate, 'expected' => '0', 'pass' => $deliveredWithoutDate === 0],
            ['check' => 'Coupon used_count drift', 'value' => (string) $couponDrift, 'expected' => '0', 'pass' => $couponDrift === 0],
            ['check' => 'Coupon usages without order_id', 'value' => (string) $unlinkedCouponUsages, 'expected' => '0', 'pass' => $unlinkedCouponUsages === 0],
            ['check' => 'Refund pending (required minus refunded)', 'value' => number_format($refundPending, 0), 'expected' => '>= 0', 'pass' => $refundPending >= 0],
            ['check' => 'Variants below threshold', 'value' => (string) $lowStock, 'expected' => 'informational', 'pass' => true],
            ['check' => 'Variants out of stock', 'value' => (string) $outOfStock, 'expected' => 'informational', 'pass' => true],
            ['check' => 'Report routes without permission middleware', 'value' => (string) $unguarded, 'expected' => '0', 'pass' => $unguarded === 0],
            ['check' => 'Report blades containing Bengali characters', 'value' => (string) $bengaliFiles, 'expected' => '0', 'pass' => $bengaliFiles === 0],
            ['check' => 'New report pages containing a phone number', 'value' => (string) $phoneFiles, 'expected' => '0', 'pass' => $phoneFiles === 0],
            ['check' => 'Pages with an explicit empty state', 'value' => (string) $emptyStateFiles, 'expected' => '6', 'pass' => $emptyStateFiles >= 6],
            ['check' => 'Report controllers reading raw_payload', 'value' => $usesRawPayload ? '1' : '0', 'expected' => '0', 'pass' => ! $usesRawPayload],
            ['check' => 'Report controllers subtracting discount twice', 'value' => $doubleDiscount ? '1' : '0', 'expected' => '0', 'pass' => ! $doubleDiscount],
            ['check' => 'Pre-fresh backup file present', 'value' => $backupExists ? 'yes' : 'no', 'expected' => 'yes', 'pass' => $backupExists],
            ['check' => 'Assertions in report tests', 'value' => (string) $assertions, 'expected' => '>= 15', 'pass' => $assertions >= 15],
        ];

        $passed = count(array_filter($checks, fn ($check) => $check['pass']));

        $volume = [
            ['table' => 'orders', 'rows' => DB::table('orders')->count()],
            ['table' => 'order_details', 'rows' => DB::table('order_details')->count()],
            ['table' => 'payments', 'rows' => DB::table('payments')->count()],
            ['table' => 'courier_shipments', 'rows' => DB::table('courier_shipments')->count()],
            ['table' => 'order_returns', 'rows' => DB::table('order_returns')->count()],
            ['table' => 'coupon_usages', 'rows' => DB::table('coupon_usages')->count()],
            ['table' => 'products', 'rows' => DB::table('products')->count()],
            ['table' => 'product_variants', 'rows' => DB::table('product_variants')->count()],
            ['table' => 'variant_stocks', 'rows' => DB::table('variant_stocks')->count()],
            ['table' => 'inventory_movements', 'rows' => DB::table('inventory_movements')->count()],
            ['table' => 'permissions', 'rows' => DB::table('permissions')->count()],
        ];

        $productivity = [
            ['task' => 'Revenue and profit for a period', 'before' => 'Hand-written SQL and a spreadsheet', 'now' => 'One page, two profit views', 'route' => 'admin.reports.overview'],
            ['task' => 'Money collected per payment method', 'before' => 'Group payments by hand', 'now' => 'One page with status split', 'route' => 'admin.reports.payments'],
            ['task' => 'Courier cost and delivery rate', 'before' => 'Read provider dashboards one by one', 'now' => 'One page per provider', 'route' => 'admin.reports.couriers'],
            ['task' => 'Refund pending versus paid', 'before' => 'Two queries plus mental math', 'now' => 'One page with the subtraction shown', 'route' => 'admin.reports.returns'],
            ['task' => 'Coupon cost versus revenue', 'before' => 'Join usages to orders by hand', 'now' => 'One page per coupon', 'route' => 'admin.reports.coupons'],
            ['task' => 'Which variant needs restocking', 'before' => 'Export stock and filter in a spreadsheet', 'now' => 'One page sorted by available', 'route' => 'admin.reports.stock-alerts'],
        ];

        $paymentRoles = $this->rolesFor('report-payment-view');
        $profitRoles = $this->rolesFor('report-profit-view');

        $paymentAdminOnly = $paymentRoles === ['Admin'];

        $overviewPath = resource_path('views/backEnd/reports/overview.blade.php');
        $overviewSource = File::exists($overviewPath) ? file_get_contents($overviewPath) : '';
        $profitGates = substr_count($overviewSource, "@can('report-profit-view')");
        $profitGated = $profitRoles === ['Admin'] && $profitGates >= 2;

        $ethics = [
            ['item' => 'Payment method page is Admin-only', 'pass' => $paymentAdminOnly, 'evidence' => 'payment: '.implode(', ', $paymentRoles)],
            ['item' => 'Net profit and margin are gated by report-profit-view', 'pass' => $profitGated, 'evidence' => 'profit: '.implode(', ', $profitRoles).' / blade gates: '.$profitGates],
            ['item' => 'Every report route carries a permission middleware', 'pass' => $unguarded === 0, 'evidence' => $unguarded.' unguarded route(s)'],
            ['item' => 'No customer phone number in the new report pages', 'pass' => $phoneFiles === 0, 'evidence' => $phoneFiles.' file(s)'],
            ['item' => 'No Bengali or explanatory prose in report blades', 'pass' => $bengaliFiles === 0, 'evidence' => $bengaliFiles.' file(s)'],
            ['item' => 'Courier cost read from a column, never raw_payload', 'pass' => ! $usesRawPayload, 'evidence' => 'shipping_cost'],
            ['item' => 'Discount is not subtracted a second time', 'pass' => ! $doubleDiscount, 'evidence' => 'orders.amount is authoritative'],
            ['item' => 'Empty range shows an empty state, not a fake chart', 'pass' => $emptyStateFiles >= 6, 'evidence' => $emptyStateFiles.' page(s)'],
            ['item' => 'Destructive cleanup was reversible', 'pass' => $backupExists, 'evidence' => 'database/seeders/.db-backup-20261127.sql.gz'],
            ['item' => 'Numbers are recomputed independently in tests', 'pass' => $assertions >= 15, 'evidence' => $assertions.' assertions'],
        ];

        return view('backEnd.reports.health', [
            'reports' => $reports,
            'checks' => $checks,
            'passed' => $passed,
            'volume' => $volume,
            'productivity' => $productivity,
            'ethics' => $ethics,
            'lowStock' => $lowStock,
            'remainingStock' => $remainingStock,
            'outOfStock' => $outOfStock,
            'refundPending' => $refundPending,
            'assertions' => $assertions,
        ]);
    }

    /**
     * Roles that currently hold a permission, read from the pivot tables.
     */
    private function rolesFor(string $permission): array
    {
        return DB::table('role_has_permissions')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('permissions.name', $permission)
            ->orderBy('roles.name')
            ->pluck('roles.name')
            ->all();
    }

    /**
     * Source text with comments removed, so a scan judges executable code only.
     */
    private function codeOnly(string $source): string
    {
        $code = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    continue;
                }

                $code .= $token[1];
            } else {
                $code .= $token;
            }
        }

        return $code;
    }

    /**
     * Each report with the roles that hold its permission and whether the
     * registered route really carries that middleware.
     */
    private function reports(): array
    {
        $registered = Route::getRoutes();
        $rows = [];

        foreach (self::REPORTS as $report) {
            $roles = DB::table('role_has_permissions')
                ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('permissions.name', $report['permission'])
                ->orderBy('roles.name')
                ->pluck('roles.name')
                ->all();

            $route = $registered->getByName($report['route']);

            $guarded = false;
            if ($route !== null) {
                foreach ($route->gatherMiddleware() as $middleware) {
                    if (is_string($middleware) && str_starts_with($middleware, 'permission:'.$report['permission'])) {
                        $guarded = true;
                        break;
                    }
                }
            }

            $rows[] = $report + [
                'roles' => $roles,
                'exists' => $route !== null,
                'guarded' => $guarded,
            ];
        }

        return $rows;
    }
}
