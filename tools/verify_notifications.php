<?php

declare(strict_types=1);

use App\Events\CustomerRegistered;
use App\Events\PaymentFailed;
use App\Events\PaymentStatusChanged;
use App\Events\RefundCompleted;
use App\Jobs\SendNotificationJob;
use App\Models\Customer;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\User;
use App\Notifications\GenericDbNotification;
use App\Services\NotificationDispatcher;
use App\Services\NotificationVariables;
use App\Services\OrderLifecycle;
use App\Services\OrderPlacementService;
use App\Support\NotificationTemplateCatalog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Prove the e-commerce notification system on the scratch database.
 *
 * Run through tools/verify_notification_bundle.sh; this script refuses to run
 * when DB_DATABASE is the .env database, because it deletes notifications and
 * places a real order.
 *
 * Every check below must pass before the final NOTIFICATION_BUNDLE_OK.
 */

$root = dirname(__DIR__);

/** @return array<string, string> */
function env_values(string $path): array
{
    $values = [];

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $values[trim($key)] = $value;
    }

    return $values;
}

function ensure(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function check(string $label, callable $fn): void
{
    try {
        $fn();
        echo "  [ok] {$label}\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "  [FAIL] {$label}: {$e->getMessage()}\n");
        exit(1);
    }
}

/** @return list<array{event_key:?string,title:string,body:string}> */
function notifications_for(object $notifiable): array
{
    return DB::table('notifications')
        ->where('notifiable_type', $notifiable::class)
        ->where('notifiable_id', $notifiable->getKey())
        ->orderBy('created_at')
        ->get()
        ->map(static function ($row): array {
            $data = json_decode((string) $row->data, true) ?: [];

            return [
                'event_key' => $data['meta']['event_key'] ?? null,
                'title' => (string) ($data['title'] ?? ''),
                'body' => (string) ($data['body'] ?? ''),
            ];
        })
        ->all();
}

/** @return array{event_key:?string,title:string,body:string} */
function ensure_notified(object $notifiable, string $eventKey): array
{
    $matches = array_values(array_filter(
        notifications_for($notifiable),
        static fn (array $notification): bool => $notification['event_key'] === $eventKey
    ));

    ensure($matches !== [], sprintf(
        'expected a %s notification for %s#%s; got [%s]',
        $eventKey,
        $notifiable::class,
        $notifiable->getKey(),
        implode(', ', array_filter(array_column(notifications_for($notifiable), 'event_key')))
    ));

    return end($matches);
}

function make_customer(string $suffix): Customer
{
    $customer = new Customer();
    $customer->name = 'Notification Check ' . $suffix;
    $customer->slug = 'notification-check-' . $suffix;
    $customer->phone = '0179' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    $customer->email = null;
    $customer->password = bcrypt('secret');
    $customer->verify = 1;
    $customer->status = 'active';
    $customer->save();

    return $customer;
}

function place_check_order(Customer $customer, object $line, string $prefix = 'NCHK'): Order
{
    $invoice = $prefix . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    return app(OrderPlacementService::class)->place(
        [[
            'product_id' => (int) $line->product_id,
            'name' => (string) $line->product_name,
            'qty' => 1,
            'price' => (float) $line->sale_price,
            'regular_price' => (float) ($line->regular_price ?? $line->sale_price),
            'purchase_price' => (float) ($line->purchase_price ?? 0),
            'product_color' => $line->product_color,
            'product_size' => $line->product_size,
            'campaign_id' => null,
            'variant_id' => (int) $line->variant_id,
        ]],
        [
            'customer_id' => (int) $customer->id,
            'name' => (string) $customer->name,
            'phone' => (string) $customer->phone,
            'address' => 'Notification check address',
            'area' => 'Dhaka',
            'payment_method' => 'Cash On Delivery',
        ],
        [
            'invoice_id' => $invoice,
            'fraud_check' => false,
            'source' => 'system',
        ]
    );
}

$env = env_values($root . '/.env');
$scratch = getenv('DB_DATABASE');

if ($scratch === false || $scratch === '') {
    fwrite(STDERR, "DB_DATABASE is not set. Run bash tools/verify_notification_bundle.sh\n");
    exit(10);
}

if ($scratch === (string) ($env['DB_DATABASE'] ?? '')) {
    fwrite(STDERR, "Refusing to run against the .env database ({$scratch}).\n");
    exit(11);
}

require $root . '/vendor/autoload.php';

$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$active = (string) config('database.connections.mysql.database');

if ($active !== $scratch) {
    fwrite(STDERR, "Laravel resolved '{$active}', expected scratch '{$scratch}'. Aborting.\n");
    exit(12);
}

echo "Scratch database: {$active}\n";

$suffix = date('ymdHis') . random_int(100, 999);
$admin = User::query()->orderBy('id')->firstOrFail();

if (getenv('NOTIFY_CHECK_FRESH') === '1') {
    check('seeding created no notification rows (notify=false works)', function (): void {
        ensure(DB::table('notifications')->count() === 0, 'seeders created notification rows');
    });
}

// Make the script safe to run twice: the first pass leaves real notifications,
// orders and one queued job behind. The runner proves the fresh-seed state on
// pass 1 with NOTIFY_CHECK_FRESH=1 before this cleanup runs.
DB::table('notifications')->delete();
DB::table('jobs')->delete();
DB::table('failed_jobs')->delete();

check('schema: event metadata + queue tables', function (): void {
    foreach (['event_key', 'channels', 'variables', 'description'] as $column) {
        ensure(Schema::hasColumn('notification_templates', $column), "missing column {$column}");
    }

    foreach (['jobs', 'job_batches', 'failed_jobs'] as $table) {
        ensure(Schema::hasTable($table), "missing table {$table}");
    }
});

check('catalog: 18 active, documented templates', function (): void {
    $catalog = NotificationTemplateCatalog::all();
    ensure(count($catalog) === 18, 'expected 18 catalog entries, got ' . count($catalog));

    $rows = NotificationTemplate::query()
        ->whereIn('event_key', array_keys($catalog))
        ->get();

    ensure($rows->count() === count($catalog), 'expected one row per catalog key, got ' . $rows->count());

    foreach ($rows as $row) {
        ensure($row->is_active, "template {$row->event_key} is inactive");
        ensure(trim((string) $row->title) !== '', "template {$row->event_key} has no title");
        ensure(trim((string) $row->body) !== '', "template {$row->event_key} has no body");
        ensure($row->channelList() !== [], "template {$row->event_key} has no channel");
        ensure($row->variableList() !== [], "template {$row->event_key} documents no variables");
    }
});

check('legacy row adoption keeps the admin title/body', function (): void {
    DB::table('notification_templates')->where('event_key', 'general.offer')->delete();
    DB::table('notification_templates')->insert([
        'name' => 'General: Offer',
        'event_key' => null,
        'title' => 'Legacy offer title',
        'body' => 'Legacy offer body',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stats = NotificationTemplateCatalog::install();

    $adopted = DB::table('notification_templates')
        ->where('name', 'General: Offer')
        ->where('event_key', 'general.offer')
        ->get();

    ensure($adopted->count() === 1, 'legacy row was not adopted exactly once');
    ensure($adopted->first()->title === 'Legacy offer title', 'adoption overwrote the title');
    ensure($stats['adopted'] === 1, 'install() did not report the adoption');
    ensure(DB::table('notification_templates')->where('event_key', 'general.offer')->count() === 1, 'duplicate event key');

    $second = NotificationTemplateCatalog::install();
    ensure($second['inserted'] === 0 && $second['adopted'] === 0, 'install() is not idempotent');
});

check('renderer: variables replaced, HTML stripped, tokens never leak', function (): void {
    $template = NotificationTemplate::query()->where('event_key', 'order.placed')->firstOrFail();

    $variables = [
        'customer_name' => 'Test Customer',
        'order_number' => 'AD-ORD-777',
        'order_total' => '1,234.50',
        'payment_method' => 'bKash',
        'payment_status' => 'paid',
        'site_name' => 'Demo Shop',
    ];

    $title = $template->renderTitle($variables);
    $body = $template->renderBody($variables);

    ensure(str_contains($title, 'AD-ORD-777'), 'title did not substitute {order_number}');
    ensure(str_contains($body, 'Test Customer'), 'body did not substitute {customer_name}');
    ensure(str_contains($body, 'AD-ORD-777'), 'body did not substitute {order_number}');
    ensure(! str_contains($body, '{customer_name}'), 'raw placeholder leaked to the body');

    $html = new NotificationTemplate([
        'title' => 'Hi {customer_name}',
        'body' => '<p>Hi <strong>{customer_name}</strong>,</p><p>Order {order_number}<br>Thanks</p>',
    ]);

    $plain = $html->renderBody(['customer_name' => 'Rahim', 'order_number' => 'AD-1']);

    ensure(! str_contains($plain, '<'), 'HTML tags survived the plain-text conversion');
    ensure(str_contains($plain, 'Hi Rahim'), 'strong text lost the variable');
    ensure(str_contains($plain, "Order AD-1\nThanks"), 'block/br newlines were not preserved');
});

$customer = make_customer($suffix);

check('template channel fallback is database', function (): void {
    $template = new NotificationTemplate(['title' => 'x', 'body' => 'y']);
    ensure($template->channelList() === ['database'], 'a null channels value must fall back to database');
});

check('OrderPlaced -> customer receipt + admin copy', function () use ($customer, $admin): void {
    $line = DB::table('order_details as d')
        ->join('variant_stocks as s', 's.variant_id', '=', 'd.variant_id')
        ->where('s.available', '>=', 5)
        ->orderByDesc('s.available')
        ->select([
            'd.product_id',
            'd.product_name',
            'd.sale_price',
            'd.regular_price',
            'd.purchase_price',
            'd.product_color',
            'd.product_size',
            'd.variant_id',
        ])
        ->first();

    ensure($line !== null, 'no seed order line has a variant with available stock');

    $GLOBALS['line'] = $line;
    $order = place_check_order($customer, $line);

    $customerCopy = ensure_notified($customer, 'order.placed');
    ensure(str_contains($customerCopy['body'], (string) $order->invoice_id), 'receipt body does not carry the invoice number');

    ensure_notified($admin, 'admin.order.placed');

    $GLOBALS['order'] = $order;
});

check('OrderStatusChanged -> processing, shipped, delivered + one review request', function () use ($customer): void {
    $order = $GLOBALS['order'] ?? null;
    ensure($order instanceof Order, 'no order fixture');

    $lifecycle = app(OrderLifecycle::class);

    $lifecycle->transition($order, 'processing');
    ensure_notified($customer, 'order.processing');

    $lifecycle->transition($order->fresh(), 'shipped');
    ensure_notified($customer, 'order.shipped');

    $lifecycle->transition($order->fresh(), 'delivered');
    ensure_notified($customer, 'order.delivered');
    ensure_notified($customer, 'review.request');

    // A repeated courier webhook must not send a second review request.
    $lifecycle->transition($order->fresh(), 'delivered');
    $reviews = array_filter(
        notifications_for($customer),
        static fn (array $notification): bool => $notification['event_key'] === 'review.request'
    );
    ensure(count($reviews) === 1, 'repeated delivered transition sent the review request twice');

    $lifecycle->transition($order->fresh(), 'returned');
    ensure_notified($customer, 'order.returned');

    // Cancellation is the other terminal transition; prove the real path too.
    $line = $GLOBALS['line'] ?? null;
    ensure(is_object($line), 'no line fixture for the cancelled order');

    $cancelled = place_check_order($customer, $line, 'NCHKC');
    $lifecycle->transition($cancelled, 'cancelled');
    ensure_notified($customer, 'order.cancelled');
});

check('PaymentStatusChanged / PaymentFailed -> payment templates', function () use ($customer): void {
    $order = $GLOBALS['order'] ?? null;
    ensure($order instanceof Order, 'no order fixture');

    $payment = $order->payment()->firstOrFail();
    $previous = (string) $payment->payment_status;

    $payment->payment_status = 'paid';
    $payment->trx_id = 'NCHK-TRX';
    $payment->save();

    event(new PaymentStatusChanged($order, $payment, $previous, 'paid'));
    $paid = ensure_notified($customer, 'payment.paid');
    ensure(str_contains($paid['body'], (string) $order->invoice_id), 'payment body has no invoice');

    event(new PaymentFailed($order, $payment, 'verification failure'));
    ensure_notified($customer, 'payment.failed');
});

check('RefundCompleted -> refund template with amount', function () use ($customer): void {
    $order = $GLOBALS['order'] ?? null;
    ensure($order instanceof Order, 'no order fixture');

    event(new RefundCompleted($order, 123.45, 'verification refund'));

    $refund = ensure_notified($customer, 'refund.completed');
    ensure(str_contains($refund['body'], '123.45'), 'refund amount missing from the body');
});

check('CustomerRegistered -> welcome + admin copy', function () use ($admin, $suffix): void {
    $newCustomer = make_customer($suffix . 'w');

    event(new CustomerRegistered($newCustomer));

    $welcome = ensure_notified($newCustomer, 'customer.welcome');
    ensure(str_contains($welcome['body'], (string) $newCustomer->name), 'welcome body has no customer name');

    ensure_notified($admin, 'admin.customer.registered');
});

check('support + manual broadcast templates deliver through the dispatcher', function () use ($customer, $admin): void {
    $dispatcher = app(NotificationDispatcher::class);

    $dispatcher->send('support.request.received', [
        'customer_name' => 'Support Customer',
        'service_type' => 'Product question',
        'site_name' => 'Demo Shop',
    ], [$customer]);

    $received = ensure_notified($customer, 'support.request.received');
    ensure(str_contains($received['body'], 'Product question'), 'support body has no service type');

    $dispatcher->send('support.request.approved', [
        'customer_name' => 'Support Customer',
        'support_note' => 'Approved by the team.',
        'site_name' => 'Demo Shop',
    ], [$customer]);
    ensure_notified($customer, 'support.request.approved');

    $dispatcher->send('admin.service.request.received', [
        'customer_name' => 'Support Customer',
        'customer_phone' => '01790000000',
        'service_type' => 'Product question',
    ], [$admin]);
    ensure_notified($admin, 'admin.service.request.received');

    $offer = NotificationTemplate::query()->where('event_key', 'general.offer')->firstOrFail();
    $dispatcher->sendTemplate($offer, [$customer], [
        'customer_name' => 'Offer Customer',
        'site_name' => 'Demo Shop',
    ]);
    ensure_notified($customer, 'general.offer');
});

check('mail is opt-in and dropped without an email address', function (): void {
    $notification = new GenericDbNotification(
        'Mail subject',
        "Line one\nLine two",
        [],
        ['database', 'mail']
    );

    $withEmail = new Customer([
        'name' => 'With Email',
        'email' => 'notify-check@example.test',
    ]);

    $channels = $notification->via($withEmail);
    ensure(in_array('database', $channels, true), 'database channel missing');
    ensure(in_array('mail', $channels, true), 'mail channel was not honoured');

    ensure($notification->toMail($withEmail)->subject === 'Mail subject', 'mail subject mismatch');

    $noEmail = new Customer(['name' => 'No Email Customer']);
    ensure(! in_array('mail', $notification->via($noEmail), true), 'mail was kept for a customer with no email');
});

check('database queue: job row is stored and queue:work delivers it', function () use ($customer): void {
    DB::table('jobs')->delete();

    config(['queue.default' => 'database']);

    SendNotificationJob::dispatch(
        $customer,
        'Queued notification',
        'Delivered by the database queue',
        ['database'],
        ['event_key' => 'queue.check']
    );

    ensure(DB::table('jobs')->count() === 1, 'the job was not stored in the jobs table');

    Artisan::call('queue:work', ['--once' => true, '--tries' => 1]);

    ensure(DB::table('jobs')->count() === 0, 'queue:work did not process the job');
    ensure_notified($customer, 'queue.check');

    config(['queue.default' => 'sync']);
});

check('scheduler drains the database queue on shared hosting', function (): void {
    Artisan::call('schedule:list');
    $output = Artisan::output();

    ensure(str_contains($output, 'queue:work'), 'queue:work is not on the schedule: ' . trim($output));
});

check('admin template form renders event_key / channels / variables', function (): void {
    $create = view('backEnd.notifications.templates._form', ['template' => null])->render();

    ensure(str_contains($create, 'name="event_key"'), 'event key input missing');
    ensure(str_contains($create, 'name="channels[]"'), 'channel checkboxes missing');
    ensure(str_contains($create, 'name="variables"'), 'variables input missing');
    ensure(str_contains($create, 'order.shipped'), 'event key datalist missing catalog keys');

    $template = NotificationTemplate::query()->where('event_key', 'order.shipped')->firstOrFail();
    $edit = view('backEnd.notifications.templates._form', ['template' => $template])->render();

    ensure(str_contains($edit, 'value="order.shipped"'), 'edit form does not prefill the event key');
    ensure(str_contains($edit, 'value="customer_name, order_number, courier, tracking_number, site_name"'), 'edit form does not prefill variables');
});

check('admin routes, sidebar link and blade syntax', function (): void {
    foreach ([
        'admin.notification-templates.index',
        'admin.notification-templates.create',
        'admin.notification-templates.edit',
    ] as $route) {
        ensure(Route::has($route), "missing route {$route}");
    }

    $sidebar = file_get_contents(resource_path('views/backEnd/layouts/partials/sidebar.blade.php'));
    ensure(str_contains($sidebar, 'admin.notification-templates.index'), 'sidebar link missing');

    foreach (['index', 'create', 'edit'] as $view) {
        $source = file_get_contents(resource_path('views/backEnd/notifications/templates/' . $view . '.blade.php'));
        Blade::compileString($source);
    }

    $index = file_get_contents(resource_path('views/backEnd/notifications/templates/index.blade.php'));
    ensure(str_contains($index, 'Event key'), 'index has no event key column');
    ensure(str_contains($index, 'channelList()'), 'index does not show channels');
});

echo "NOTIFICATION_CHECKS_OK\n";
