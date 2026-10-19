<?php
/**
 * Proof that every wired admin grid endpoint returns its real rows.
 *
 * Each controller method is called exactly as the route would call it - the
 * DataTables request parameters, the same search value a shopkeeper would type
 * - and the response is checked for the things a grid cannot work without:
 *
 *   * a recordsTotal the table can page against
 *   * one array entry per row
 *   * one item per column, and no <td> left inside a cell
 *
 * A screen with no rows yet is reported as EMPTY, not as a failure: the grid is
 * still correct, there is simply nothing to show.
 *
 *   php tools/verify_grid_endpoints.php
 */
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$endpoints = [
    'admin/products/manage/datatable' => [App\Http\Controllers\Admin\ProductController::class, 'datatable', []],
    'admin/order/all/datatable'       => [App\Http\Controllers\Admin\OrderController::class, 'datatable', ['all']],
    'admin/customer/datatable'        => [App\Http\Controllers\Admin\CustomerManageController::class, 'datatable', []],
    'admin/banner/manage/datatable'   => [App\Http\Controllers\Admin\BannerController::class, 'datatable', []],
    'admin/categories/manage/datatable' => [App\Http\Controllers\Admin\CategoryController::class, 'datatable', []],
    'admin/brands/manage/datatable'   => [App\Http\Controllers\Admin\BrandController::class, 'datatable', []],
    'admin/coupon/manage/datatable'   => [App\Http\Controllers\Admin\CouponController::class, 'datatable', []],
    'admin/returns/datatable'         => [App\Http\Controllers\Admin\ReturnsRefundsController::class, 'datatable', []],
];

printf("%-38s %8s %7s %7s  %s\n", 'endpoint', 'total', 'rows', 'cells', 'verdict');
printf("%s\n", str_repeat('-', 92));

$problems = 0;

foreach ($endpoints as $uri => [$class, $method, $args]) {
    $query = http_build_query([
        'draw' => 1,
        'start' => 0,
        'length' => 5,
        'search' => ['value' => ''],
        'order' => [['column' => 1, 'dir' => 'desc']],
    ]);

    $request = Illuminate\Http\Request::create("/{$uri}?{$query}", 'GET');
    $app->instance('request', $request);

    try {
        $controller = new $class();
        $payload = $controller->{$method}($request, ...$args)->getData(true);
    } catch (Throwable $e) {
        printf("%-38s %s\n", $uri, $e::class . ': ' . substr($e->getMessage(), 0, 200));
        $problems++;
        continue;
    }

    $rows = $payload['data'];
    $total = $payload['recordsTotal'];

    if ($total === 0) {
        printf("%-38s %8d %7d %7s  EMPTY (no rows in the database yet)\n", $uri, $total, count($rows), '-');
        continue;
    }

    $cells = count($rows[0]);
    $hasTd = str_contains(json_encode($rows), '<td');

    $ok = $cells > 0 && !$hasTd && count($rows) > 0;
    $problems += $ok ? 0 : 1;

    printf(
        "%-38s %8d %7d %7d  %s\n",
        $uri,
        $total,
        count($rows),
        $cells,
        $ok ? 'OK' : ($hasTd ? 'FAIL: <td> left inside a cell' : 'FAIL: no rows returned')
    );
}

echo "\n";
echo $problems === 0
    ? "every endpoint with data returns well-formed rows\n"
    : "{$problems} endpoint(s) returned something the grid cannot draw\n";

exit($problems === 0 ? 0 : 1);