<?php
/**
 * Proof for the grid endpoints whose tables are empty in this database.
 *
 * The orders, coupons and returns screens were wired the same way as the ones
 * with data, but with nothing to draw they cannot show that the wiring works:
 * an endpoint can return a well-formed empty envelope and still be broken in
 * every way that matters.
 *
 * So this inserts a throwaway coupon, a throwaway order on a delivered status
 * and a throwaway return record, calls each endpoint exactly as the grid does,
 * prints what came back, and removes all three again. It refuses to leave
 * anything behind: the inserts and the deletes are in one try/finally, and the
 * script re-counts the tables afterwards.
 *
 *   php tools/verify_grid_endpoints_empty.php
 */
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$before = [
    'coupons'     => DB::table('coupons')->count(),
    'orders'      => DB::table('orders')->count(),
    'order_returns' => DB::table('order_returns')->count(),
];

$couponId = $orderId = $returnId = null;
$problems = 0;

try {
    // ---- fixtures -------------------------------------------------------
    $couponId = DB::table('coupons')->insertGetId([
        'code'         => 'ZZPROOF9',
        'type'         => 'fixed',
        'value'        => 75,
        'min_amount'   => 500,
        'max_discount' => 75,
        'start_at'     => now()->subDay(),
        'end_at'       => now()->addMonth(),
        'usage_limit'  => 10,
        'used_count'   => 0,
        'status'       => 1,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    // The returns screen is a list of completed orders - "delivered" is how the
    // page says it, and this database spells the status 'completed'. Picking
    // the wrong row here would make the returns grid look empty for a reason
    // that has nothing to do with the wiring.
    $delivered = DB::table('order_statuses')
        ->whereIn('slug', ['completed', 'delivered'])
        ->first();

    if ($delivered === null) {
        throw new RuntimeException('no completed/delivered order status to file the fixture under');
    }

    $orderId = DB::table('orders')->insertGetId([
        'invoice_id'       => 'ZZ-PROOF-0001',
        'amount'           => 1250,
        'discount'         => 0,
        'shipping_charge'  => 60,
        'customer_id'      => DB::table('customers')->value('id'),
        'admin_note'       => '',
        'order_status'     => $delivered->id,
        'risk_label'       => null,
        'risk_score_percent' => null,
        'note'             => '',
        'created_at'       => now(),
        'updated_at'       => now(),
    ]);

    $returnId = DB::table('order_returns')->insertGetId([
        'order_id'        => $orderId,
        'status'          => 'requested',
        'restocked'       => 0,
        'reason'          => 'grid proof',
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    echo "inserted coupon #{$couponId}, order #{$orderId}, return #{$returnId}\n\n";

    // ---- the endpoints, called the way the grid calls them ---------------
    $call = static function (string $uri, array $q = []) use ($app): array {
        $q = $q + ['draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => '']];
        $request = Illuminate\Http\Request::create(
            '/' . $uri . '?' . http_build_query($q), 'GET'
        );
        $app->instance('request', $request);

        $map = [
            'admin/coupon/manage/datatable' => [App\Http\Controllers\Admin\CouponController::class, []],
            'admin/order/all/datatable'      => [App\Http\Controllers\Admin\OrderController::class, ['all']],
            'admin/returns/datatable'        => [App\Http\Controllers\Admin\ReturnsRefundsController::class, []],
        ];

        [$class, $args] = $map[$uri];
        $payload = (new $class())->datatable($request, ...$args)->getData(true);

        return $payload;
    };

    $plain = static fn ($v) => trim(preg_replace('/\s+/', ' ', strip_tags((string) $v)));

    printf("%-34s %8s %9s %6s  %s\n", 'endpoint', 'total', 'filtered', 'rows', 'first row');
    printf("%s\n", str_repeat('-', 108));

    $coupon = $call('admin/coupon/manage/datatable', ['search' => ['value' => 'ZZPROOF9']]);
    printf("%-34s %8d %9d %6d  %s\n", 'coupon (search ZZPROOF9)',
        $coupon['recordsTotal'], $coupon['recordsFiltered'], count($coupon['data']),
        $plain($coupon['data'][0][1] ?? ''));

    $order = $call('admin/order/all/datatable', ['search' => ['value' => 'ZZ-PROOF-0001']]);
    printf("%-34s %8d %9d %6d  %s\n", 'orders (search ZZ-PROOF-0001)',
        $order['recordsTotal'], $order['recordsFiltered'], count($order['data']),
        $plain($order['data'][0][1] ?? ''));

    $returns = $call('admin/returns/datatable', ['search' => ['value' => 'ZZ-PROOF-0001']]);
    printf("%-34s %8d %9d %6d  %s\n", 'returns (search ZZ-PROOF-0001)',
        $returns['recordsTotal'], $returns['recordsFiltered'], count($returns['data']),
        $plain($returns['data'][0][5] ?? ''));

    foreach ([['coupon', $coupon], ['orders', $order], ['returns', $returns]] as [$name, $payload]) {
        $ok = $payload['recordsFiltered'] >= 1
            && count($payload['data']) >= 1
            && !str_contains(json_encode($payload['data']), '<td');
        printf("%-12s cells=%-3d %s\n",
            $name,
            count($payload['data'][0] ?? []),
            $ok ? 'OK' : 'FAIL');
        $problems += $ok ? 0 : 1;
    }
} finally {
    // ---- cleanup, whatever happened above -------------------------------
    if ($returnId)  DB::table('order_returns')->where('id', $returnId)->delete();
    if ($orderId)   DB::table('orders')->where('id', $orderId)->delete();
    if ($couponId)  DB::table('coupons')->where('id', $couponId)->delete();

    $after = [
        'coupons'     => DB::table('coupons')->count(),
        'orders'      => DB::table('orders')->count(),
        'order_returns' => DB::table('order_returns')->count(),
    ];

    echo "\ncleanup\n";
    foreach ($before as $table => $count) {
        printf("  %-14s before=%-4d after=%-4d %s\n",
            $table, $count, $after[$table],
            $before[$table] === $after[$table] ? 'unchanged' : 'CHANGED');
        $problems += $before[$table] === $after[$table] ? 0 : 1;
    }
}

echo "\n";
echo $problems === 0
    ? "the three empty screens do return rows when rows exist, and left nothing behind\n"
    : "{$problems} problem(s)\n";

exit($problems === 0 ? 0 : 1);