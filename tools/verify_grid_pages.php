<?php
/**
 * Proof that every list screen still renders after the grid swap.
 *
 * The real controller code path is used where it is reachable without a login
 * session; where the page needs data the controller assembles (pagination,
 * helper collections), that data is supplied directly and the page is rendered
 * anyway. What is being checked is the thing the change could have broken:
 * the blade page plus the shared grid partial compiling into a table the
 * runtime can attach to.
 */
$base = '/home/sahon/Desktop/arafat20';
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Banner, Brand, Category, Coupon, Customer, Order};

// The admin layout's navbar reads auth()->user()->notifications, so a screen
// cannot render without somebody logged in. This is an in-memory login inside
// the verifier's own process - no session is written and nothing in the
// database is touched.
$admin = App\Models\User::query()->get()->first(fn ($u) => $u !== null);
if ($admin === null) {
    fwrite(STDERR, "no user row to render the admin layout with\n");
    exit(2);
}
Illuminate\Support\Facades\Auth::login($admin);

// Normally shared by ShareErrorsFromSession; rendering a view outside the web
// middleware stack is the only place it would be missing.
view()->share('errors', new Illuminate\Support\ViewErrorBag);

$pages = [
    'backEnd.product.index' => ['products' => App\Models\Product::paginate(10)],
    'backEnd.customer.index' => ['show_data' => Customer::paginate(10)],
    'backEnd.banner.index'   => ['data' => Banner::with('category')->paginate(10)],
    'backEnd.category.index' => ['data' => Category::where('parent_id', 0)->paginate(10)],
    'backEnd.brand.index'    => ['data' => Brand::paginate(10)],
    'backEnd.coupon.index'   => ['coupons' => Coupon::paginate(10)],
];

printf("%-28s %7s %7s  %s\n", 'page', 'bytes', 'grid', 'note');
printf("%s\n", str_repeat('-', 74));

$problems = 0;

foreach ($pages as $view => $data) {
    if (! view()->exists($view)) {
        printf("%-28s %7s %7s  %s\n", $view, '-', '-', 'view does not exist');
        $problems++;
        continue;
    }

    try {
        $html = view($view, $data)->render();
    } catch (\Throwable $e) {
        printf("%-28s %7s %7s  ERROR %s\n", $view, '-', '-', substr($e->getMessage(), 0, 50));
        $problems++;
        continue;
    }

    $hasGrid  = str_contains($html, 'data-fm-dt');
    $hasTable = substr_count($html, '<table') >= 1;
    $hasEmpty = str_contains($html, 'No records found') || str_contains($html, 'data-fm-dt');

    $ok = $hasGrid && $hasTable;
    $problems += $ok ? 0 : 1;

    printf(
        "%-28s %7d %7s  %s\n",
        $view,
        strlen($html),
        $hasGrid ? 'yes' : 'NO',
        $ok ? 'renders with the shared grid attached' : 'grid/table missing'
    );
}

echo "\n";
echo $problems === 0
    ? "every list screen renders and carries the grid hook\n"
    : "{$problems} page(s) did not come out right\n";

exit($problems === 0 ? 0 : 1);