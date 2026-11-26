<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Shipping;
use App\Models\ShippingCharge;
use App\Models\Category;
use App\Models\User;
use App\Models\Courierapi;
use App\Services\PathaoClient;
use App\Models\CourierFraudCheck;
use App\Models\CourierShipment;
use App\Services\CourierOrderSender;
use App\Services\PathaoOrderSender;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Brian2694\Toastr\Facades\Toastr;
use Session;
use Cart;
use Gloudemans\Shoppingcart\Exceptions\InvalidRowIDException;
use Mail;
use App\Models\InventoryMovement;

class OrderController extends Controller
{
    use InteractsWithDataTable;
    private function posExtraDiscountTotal(): float
    {
        $manual = (float) (Session::get('pos_manual_discount') ?? 0);
        $coupon = (float) (Session::get('pos_coupon_discount') ?? 0);
        return max(0.0, $manual + $coupon);
    }

    private function posTotalDiscount(): float
    {
        $product = (float) (Session::get('product_discount') ?? 0);
        return max(0.0, $this->posExtraDiscountTotal() + $product);
    }

    private function resolveOrderStatusBySlug(string $slug): ?OrderStatus
    {
        $slug = trim($slug);

        $aliases = [
            'delivered' => ['delivered', 'completed', 'complete'],
            'completed' => ['completed', 'delivered', 'complete'],
            'cancel' => ['cancel', 'cancelled', 'canceled'],
            'cancelled' => ['cancelled', 'cancel', 'canceled'],
        ];

        $candidates = $aliases[$slug] ?? [$slug];

        return OrderStatus::whereIn('slug', $candidates)
            ->select('id', 'name', 'slug')
            ->first();
    }

    private function joinUrl(string $base, string $path): string
    {
        $base = rtrim($base, '/');
        $path = '/' . ltrim($path, '/');
        return $base . $path;
    }

    /*
     * There is no Pathao URL builder here any more.
     *
     * This class used to carry its own copy of the base-path rule, and that copy
     * said the sandbox host serves the API at the root. Measured live:
     * POST {sandbox}/aladdin/api/v1/issue-token answers 200, POST
     * {sandbox}/api/v1/issue-token answers 404. PathaoClient::buildUrl() now
     * holds the verified rule, so a fix cannot land in one caller and miss
     * another. Parcel data and location detection moved to
     * PathaoOrderSender for the same reason.
     */

    /*
     * The orders screen used to fetch Pathao's city list and store list on
     * every page load. Both are ~20 second calls on the real API, and the guard
     * here was a 2 second timeout - so the fetch ALWAYS failed, opened the
     * circuit breaker for five minutes, and left $pathaostore empty. The Pathao
     * button is rendered only when that list is non-empty, so the button was
     * permanently hidden and the page still paid 4 seconds for the attempt.
     *
     * Nothing on the list screen actually needs the store list: the send action
     * resolves the store itself (PathaoOrderSender). The button is now gated on
     * "Pathao is configured and enabled", which is a local DB read.
     */

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = preg_replace('/[^0-9\+]/', '', $phone);
        if (str_starts_with($phone, '88') && strlen($phone) > 11) {
            $phone = ltrim($phone, '88');
        }
        if (str_starts_with($phone, '+88')) {
            $phone = substr($phone, 3);
        }
        return $phone;
    }

    /**
     * Add the high-risk highlight (and its percentage) to each order.
     *
     * Accepts either a paginator, as the blade page passes, or a plain
     * Collection, as the DataTables endpoint passes - both arrive at the same
     * fraud-check query and the same per-order mutation.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection  $orders
     */
    private function attachRiskFallbackToOrders($orders): void
    {
        $orders = method_exists($orders, 'getCollection')
            ? $orders->getCollection()
            : $orders;

        $phones = $orders
            ->map(fn ($o) => $o->shipping ? $this->normalizePhone((string) $o->shipping->phone) : null)
            ->filter(fn ($p) => $p)
            ->unique()
            ->values();

        if ($phones->isEmpty()) {
            return;
        }

        $latestChecks = CourierFraudCheck::query()
            ->select('phone', 'risk_label', 'success_rate_percent', 'checked_at')
            ->whereIn('phone', $phones)
            ->orderByDesc('checked_at')
            ->get()
            ->groupBy('phone')
            ->map(fn ($rows) => $rows->first());

        foreach ($orders as $order) {
            if (!empty($order->risk_label)) {
                continue;
            }
            $phone = $order->shipping ? $this->normalizePhone((string) $order->shipping->phone) : null;
            if (!$phone) {
                continue;
            }
            $check = $latestChecks->get($phone);
            if (!$check) {
                continue;
            }
            $order->risk_label = $check->risk_label;
            $order->risk_score_percent = $check->success_rate_percent;
        }
    }

    /**
     * DataTables server-side endpoint for the orders list.
     *
     * WHY THIS SHAPE
     * --------------
     * The orders table is not a grid of plain values: each row carries an
     * invoice link, an avatar, a status badge, a bulk-action form and a modal
     * trigger. Re-rendering that in JavaScript would mean two implementations
     * of one row, and they would drift.
     *
     * So the server sends HTML. Each entry in `data` is one already-rendered
     * <tr> from backEnd/order/_row.blade.php - the very partial the ordinary
     * blade table uses. One row implementation, two callers.
     *
     * Searching and sorting are done in SQL, not on the page: the search box
     * covers invoice id, customer name and phone, which is the same three
     * things the old ?keyword= form searched, plus the customer name it never
     * did.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /** Columns of the orders table, in <thead> order. */
    private const ORDER_TABLE_COLUMNS = 9;

    /**
     * Split a rendered <tr> into its cells.
     *
     * @throws \RuntimeException when the cell count does not match the table,
     *                           which is the only way this heuristic can fail
     */
    private function rowHtmlToCells(string $html, int $expected): array
    {
        // Strip the wrapper first so the first cell does not carry it.
        $inner = preg_replace('#^<tr[^>]*>|</tr>$#i', '', trim($html)) ?? trim($html);
        $inner = preg_replace('#^</?tbody>#i', '', $inner) ?? $inner;

        $cells = preg_split('#</td>#i', $inner) ?: [];

        // The text after the last </td> is empty by construction.
        if (end($cells) === false || trim((string) end($cells)) === '') {
            array_pop($cells);
        }

        $cells = array_values(array_filter(array_map('trim', $cells), static fn ($c) => $c !== ''));

        if (count($cells) !== $expected) {
            throw new \RuntimeException(sprintf(
                'order/_row.blade.php rendered %d cells but the orders table has %d columns. '
                . 'Either update self::ORDER_TABLE_COLUMNS or the row partial.',
                count($cells),
                $expected
            ));
        }

        return $cells;
    }

    public function datatable(Request $request, $slug)
    {
        $draw    = (int) $request->input('draw', 1);
        $start   = max(0, (int) $request->input('start', 0));
        $length  = (int) $request->input('length', 20);
        $length  = $length > 0 ? min($length, 200) : 20;

        $search  = $this->dtSearch($request);
        $status  = $request->input('status') ?: $request->input('keyword');

        // courierShipments is eager loaded because backEnd/order/_row.blade.php
        // checks it to decide whether the "Send to courier" button belongs on
        // this row (an order that already has a consignment must not offer a
        // second, billable one). Without the eager load that check would fire
        // one extra query per row - an N+1 on every page of the orders table.
        $query = Order::query()->with('shipping', 'status', 'courierShipments', 'customer');

        // Taken before the filters below, so recordsTotal stays the size of the
        // tab and recordsFiltered is the size of what the search left.
        $total = (clone $query)->count();

        // The status tab in the path still decides the set; ?status= is the
        // "advanced filter" select and narrows it further.
        if ($slug !== 'all') {
            $resolved = $this->resolveOrderStatusBySlug($slug);
            $query->when($resolved, fn ($q) => $q->where('order_status', $resolved->id),
                          fn ($q) => $q->whereRaw('1 = 0'));
        }

        if ($status !== null && $status !== '') {
            $query->where('order_status', (int) $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_id', 'LIKE', "%{$search}%")
                  ->orWhereHas('shipping', function ($sub) use ($search) {
                      $sub->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('phone', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('customer', function ($sub) use ($search) {
                      $sub->where('name', 'LIKE', "%{$search}%")
                         ->orWhere('phone', 'LIKE', "%{$search}%");
                  });
            });
        }

        // How many survive the search; the pager counts these.
        $filtered = (clone $query)->count();

        // Ordering is by COLUMN INDEX, so the whitelist has to match the
        // <thead> exactly. An unknown index falls back to newest first rather
        // than interpolating anything into the query.
        $sortable = [
            1 => 'invoice_id',
            2 => 'created_at',
            6 => 'total',
        ];
        [$orderColumn, $direction] = $this->dtOrdering($request, $sortable, 'created_at');
        $query->orderBy($orderColumn, $direction);

        $orders = $query->skip($start)->take($length)->get();

        // The high-risk row highlight is computed per order by the fraud
        // check; a paginator helper would need a paginator, so the collection
        // is handed to the same routine.
        $this->attachRiskFallbackToOrders($orders);

        $orderstatus = OrderStatus::select('id', 'name', 'slug')->get();

        /*
         * Each row is rendered by the same partial the blade table uses, then
         * split into its cells.
         *
         * DataTables needs one array entry PER COLUMN; handing it a whole
         * <tr> as a single string leaves it with nothing to draw (the table
         * comes up with class .dt-empty-footer and no controls, which is a
         * silent failure - the page looks like it simply has no orders).
         *
         * The split is checked against the column count on purpose. If someone
         * adds a cell to the row partial without telling this method, the
         * request fails loudly here instead of quietly shifting every column
         * one place to the left on every row.
         */
        $rows = [];
        foreach ($orders as $order) {
            $html = trim(view('backEnd.order._row', [
                'value'       => $order,
                'orderstatus' => $orderstatus,
            ])->render());

            $cells = $this->rowHtmlToCells($html, self::ORDER_TABLE_COLUMNS);

            /*
             * Unwrap the <td> itself.
             *
             * DataTables writes each value into a cell it creates itself, so a
             * value that still carries its own <td> becomes <td><td>…</td></td>.
             * The browser's parser repairs that nesting by hoisting the content
             * out, which prints every cell once per column - a two-row table
             * came out with sixteen checkboxes in it. The value has to be the
             * cell's CONTENTS.
             */
            $rows[] = array_map(static function (string $cell): string {
                $inner = preg_replace('#^<td[^>]*>|</td>$#i', '', trim($cell));
                return trim((string) $inner);
            }, $cells);
        }

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows,
        ]);
    }

    public function index($slug,Request $request){
        if($slug == 'all'){
            $order_status = (object) [
                'name' => 'All',
                'orders_count'=> Order::count(),
            ];
            $show_data = Order::latest()->with('shipping','status','customer');
            if($request->status){
                $show_data = $show_data->where('order_status', (int) $request->status);
            }
            if($request->keyword){
                $show_data = $show_data->where(function ($query) use ($request) {
                    $query->orWhere('invoice_id', 'LIKE', '%' . $request->keyword . '%')
                          ->orWhereHas('shipping', function ($subQuery) use ($request) {
                              $subQuery->where('phone', 'LIKE', '%' . $request->keyword . '%')
                                       ->orWhere('name', 'LIKE', '%' . $request->keyword . '%');
                          })
                          ->orWhereHas('customer', function ($subQuery) use ($request) {
                              $subQuery->where('phone', 'LIKE', '%' . $request->keyword . '%')
                                       ->orWhere('name', 'LIKE', '%' . $request->keyword . '%');
                          });
                });
            }
           $show_data = $show_data->paginate(20);
           $this->attachRiskFallbackToOrders($show_data);
        }else{
            $resolved = $this->resolveOrderStatusBySlug($slug);
            if ($resolved) {
                $order_status = OrderStatus::where('id', $resolved->id)->withCount('orders')->first();
                $show_data = Order::where(['order_status'=>$order_status->id])->latest()->with('shipping','status','customer');
                if($request->status){
                    $show_data = $show_data->where('order_status', (int) $request->status);
                }
                if($request->keyword){
                    $show_data = $show_data->where(function ($query) use ($request) {
                        $query->orWhere('invoice_id', 'LIKE', '%' . $request->keyword . '%')
                              ->orWhereHas('shipping', function ($subQuery) use ($request) {
                                  $subQuery->where('phone', 'LIKE', '%' . $request->keyword . '%')
                                           ->orWhere('name', 'LIKE', '%' . $request->keyword . '%');
                              })
                              ->orWhereHas('customer', function ($subQuery) use ($request) {
                                  $subQuery->where('phone', 'LIKE', '%' . $request->keyword . '%')
                                           ->orWhere('name', 'LIKE', '%' . $request->keyword . '%');
                              });
                    });
                }
                $show_data = $show_data->paginate(20);
                $this->attachRiskFallbackToOrders($show_data);
            } else {
                $order_status = (object) [
                    'name' => Str::headline($slug),
                    'orders_count' => 0,
                ];
                $show_data = Order::whereRaw('1 = 0')->latest()->with('shipping','status')->paginate(20);
            }
        }

        $orderstatus = OrderStatus::select('id', 'name', 'slug')->get();

        $pendingStatus = $this->resolveOrderStatusBySlug('pending');
        $deliveredStatus = $this->resolveOrderStatusBySlug('delivered');
        $cancelledStatus = $this->resolveOrderStatusBySlug('cancel');

        $order_kpis = (object) [
            'total' => Order::count(),
            'today' => Order::whereDate('created_at', now()->toDateString())->count(),
            'pending' => $pendingStatus ? Order::where('order_status', $pendingStatus->id)->count() : 0,
            'delivered' => $deliveredStatus ? Order::where('order_status', $deliveredStatus->id)->count() : 0,
            'cancelled' => $cancelledStatus ? Order::where('order_status', $cancelledStatus->id)->count() : 0,
        ];
        $users = User::get();

        $courierEnabledColumnExists = Schema::hasColumn('courier_apis', 'status');

        $steadfastQuery = Courierapi::query()->where('type', 'steadfast');
        if ($courierEnabledColumnExists) {
            $steadfastQuery->where('status', 1);
        }
        $steadfast = $steadfastQuery->first();

        // The Pathao button is shown when the account is usable, which is a
        // local DB check. The old code asked Pathao for its store list here and
        // hid the button whenever that (always failing) call came back empty.
        $pathaoReady = PathaoClient::enabled() !== null;

        return view('backEnd.order.index',compact('show_data','order_status','orderstatus','order_kpis','users', 'steadfast','pathaoReady'));
    } 
    
    /**
     * Zones of a Pathao city, for the cascading city -> zone -> area pickers.
     *
     * Answers with an explicit state instead of a bare empty list:
     *
     *   422 - the caller forgot city_id
     *   424 - Pathao is not usable here (missing row, disabled, or incomplete
     *         credentials), and say which
     *   502 - Pathao was reached but refused or was unreachable, carrying its
     *         own response body
     *   200 - the courier's payload, passed through untouched
     *
     * The reason for the codes is that an empty 200 is indistinguishable from
     * "this city genuinely has no zones". That ambiguity is what made a
     * switched-off courier look like a broken API, with nothing on screen and
     * no request in the network tab.
     */
    public function pathaocity(Request $request)
    {
        $cityId = trim((string) $request->query('city_id', ''));

        if ($cityId === '') {
            return response()->json([
                'configured' => true,
                'message'    => 'Missing city_id.',
            ], 422);
        }

        return $this->pathaoLookup('api/v1/cities/' . rawurlencode($cityId) . '/zone-list');
    }

    /**
     * Areas of a Pathao zone. See pathaocity() for the response contract.
     */
    public function pathaozone(Request $request)
    {
        $zoneId = trim((string) $request->query('zone_id', ''));

        if ($zoneId === '') {
            return response()->json([
                'configured' => true,
                'message'    => 'Missing zone_id.',
            ], 422);
        }

        return $this->pathaoLookup('api/v1/zones/' . rawurlencode($zoneId) . '/area-list');
    }

    /**
     * Shared body for the two Pathao reference lookups.
     *
     * Kept in one place so both endpoints cannot drift apart again: they were
     * two copies of the same four blocks, which is how both ended up without an
     * Authorization header and without a timeout.
     */
    private function pathaoLookup(string $path): \Illuminate\Http\JsonResponse
    {
        $client = PathaoClient::enabled();

        if ($client === null) {
            $config = Courierapi::query()->where('type', 'pathao')->first();

            // Say exactly which of the three states we are in. "Disabled" and
            // "you forgot the secret" need different actions from the admin.
            $reason = ! $config
                ? 'No Pathao account is configured.'
                : (empty($config->status)
                    ? 'Pathao is disabled.'
                    : 'Pathao credentials are incomplete.');

            Log::info('Pathao lookup refused locally', ['path' => $path, 'reason' => $reason]);

            return response()->json([
                'configured' => false,
                'message'    => $reason . ' Enable it in Admin > Courier Settings before choosing zones and areas.',
            ], 424);
        }

        $result = $client->get($path);

        if (! $result['ok']) {
            Log::warning('Pathao lookup failed', [
                'path'   => $path,
                'status' => $result['status'],
            ]);

            return response()->json([
                'configured'       => true,
                'message'          => $result['message'] ?? 'Pathao lookup failed.',
                'courier_response' => $result['body'],
            ], 502);
        }

        // Pass Pathao's payload through unchanged: the calling script reads the
        // courier's own nesting (data.data), and rebuilding it here would be one
        // more shape to keep in step with a third party.
        return response()->json($result['body']);
    }

    public function order_pathao(Request $request)
    {
        $orders_id = (array) $request->order_ids;

        if (empty($orders_id)) {
            return response()->json(['status' => 'failed', 'message' => 'No orders selected'], 422);
        }

        // The store/city/zone/area the admin picked in the popup. Anything left
        // empty is resolved by the sender, so this path and the bulk one cannot
        // disagree about what a valid Pathao payload is.
        $shop = [
            'store_id' => $request->pathaostore,
            'city_id'  => $request->pathaocity,
            'zone_id'  => $request->pathaozone,
            'area_id'  => $request->pathaoarea,
        ];

        foreach ($orders_id as $order_id) {
            $order = Order::with(['shipping', 'orderdetails'])->find($order_id);

            if (! $order) {
                return response()->json(['status' => 'failed', 'message' => 'Order not found']);
            }

            $result = app(PathaoOrderSender::class)->send($order, $shop);

            if (! $result['ok']) {
                Toastr::error($result['message'], 'Courier Order Failed');

                return response()->json(['status' => 'failed', 'message' => $result['message']]);
            }

            Toastr::success($result['consignment_id'], 'Courier Tracking ID');

            return response()->json([
                'status'         => 'success',
                'message'        => $result['consignment_id'],
                'consignment_id' => $result['consignment_id'],
            ]);
        }

        return response()->json(['status' => 'failed', 'message' => 'No orders selected'], 422);
    }
    
    public function invoice($invoice_id){
        $order = Order::where(['invoice_id'=>$invoice_id])->with('orderdetails','payment','shipping','customer')->firstOrFail();
        return view('backEnd.order.invoice',compact('order'));
    }

    public function invoicesIndex(Request $request)
    {
        $query = Order::query()->with(['shipping', 'payment', 'status']);

        if ($request->keyword) {
            $kw = trim((string) $request->keyword);
            $query->where(function ($q) use ($kw) {
                $q->where('invoice_id', 'LIKE', '%' . $kw . '%')
                  ->orWhereHas('shipping', function ($sq) use ($kw) {
                      $sq->where('phone', 'LIKE', '%' . $kw . '%')
                         ->orWhere('name', 'LIKE', '%' . $kw . '%');
                  });
            });
        }

        $invoices = $query->latest()->paginate(20);

        $clients = Shipping::query()->distinct('phone')->count('phone');
        $invoiceCount = Order::count();
        $paidAmount = (float) Payment::query()->where('payment_status', 'paid')->sum('amount');
        $unpaidCount = (int) Payment::query()->where('payment_status', 'pending')->count();

        $invoice_kpis = (object) [
            'clients' => $clients,
            'invoices' => $invoiceCount,
            'paid_amount' => $paidAmount,
            'unpaid_count' => $unpaidCount,
        ];

        return view('backEnd.order.invoices', compact('invoices', 'invoice_kpis'));
    }
    
    public function process($invoice_id){
        $order = Order::query()
            ->where(['invoice_id' => $invoice_id])
            ->with([
                'orderdetails.image',
                'payment',
                'shipping',
                'customer',
                'status',
            ])
            ->firstOrFail();

        $data = $order;
        $orderstatus = OrderStatus::select('id', 'name', 'slug')->get();
        $shippingcharge = ShippingCharge::where('status', 1)->get();

        $previousCourierShipments = collect();
        if (!empty($order->customer_id) && Schema::hasTable('courier_shipments')) {
            $previousCourierShipments = CourierShipment::query()
                ->with(['order:id,invoice_id,customer_id,created_at'])
                ->whereHas('order', function ($q) use ($order) {
                    $q->where('customer_id', $order->customer_id)
                      ->where('id', '!=', $order->id);
                })
                ->orderByDesc('id')
                ->limit(10)
                ->get();
        }

        $hasCourierHistory = $previousCourierShipments->isNotEmpty();

        /*
         * B5: THIS order's own courier event timeline (append-only log), so the
         * admin sees the parcel's real history - pending -> in_transit ->
         * delivered -> returned - instead of only the last status or the
         * customer's unrelated orders.
         */
        $courierEvents = collect();
        if (Schema::hasTable('courier_events')) {
            $courierEvents = \App\Models\CourierEvent::query()
                ->where('order_id', $order->id)
                ->orderBy('id')
                ->get();
        }
        $hasCourierEvents = $courierEvents->isNotEmpty();

        return view('backEnd.order.process', compact('order', 'data', 'shippingcharge', 'orderstatus', 'previousCourierShipments', 'hasCourierHistory', 'courierEvents', 'hasCourierEvents'));
    }
    
    public function order_process(Request $request)
    {

        $statusRow = OrderStatus::findOrFail($request->status);
        $link = $statusRow->slug;
        $order = Order::findOrFail($request->id);
        $courier = (int) $order->order_status;
        $noteBefore = (string) ($order->admin_note ?? '');

        /*
         * Only touch the note when the form actually carried the field.
         *
         * This assignment used to be unconditional, but the process/status
         * form ("Update Order") has no note input, so every status save wrote
         * NULL over the note typed on the order edit screen. That is why the
         * admin note kept disappearing.
         */
        $audit = app(\App\Services\AuditLogger::class);
        if ($request->has('admin_note')) {
            $order->admin_note = $request->admin_note;
            $order->save();
            $audit->orderNote($order, $noteBefore, (string) $order->admin_note);
        }

        /*
         * Every status move goes through OrderLifecycle.
         *
         * This form used to write order_status directly and stop there. The
         * list-view dropdown and the Kanban board both call OrderLifecycle, so
         * an order shipped from this screen left no SALE movement and never
         * reduced stock - the exact order path an admin uses most. Lifecycle
         * also writes the audit row, so it is not duplicated here.
         */
        if ((string) $courier !== (string) $statusRow->id) {
            try {
                app(\App\Services\OrderLifecycle::class)->transition(
                    $order,
                    (string) $statusRow->slug,
                    null,
                    auth()->id()
                );
            } catch (\Throwable $e) {
                Log::warning('Admin order status change failed', [
                    'order_id' => $order->id,
                    'status_id' => $statusRow->id,
                    'slug' => $statusRow->slug,
                    'error' => $e->getMessage(),
                ]);

                Toastr::error('Status was not changed: ' . $e->getMessage(), 'Stock');

                return redirect('admin/order/' . $link);
            }
        }

        $shipping_update = Shipping::where('order_id', $order->id)->first();
        $shippingfee = ShippingCharge::find($request->area);

        /*
         * A seeded/legacy order can exist WITHOUT a shipping row. This block
         * used to write $shipping_update->name unconditionally, so editing such
         * an order's status threw "Attempt to assign property on null" (HTTP 500)
         * even though the status itself had already been saved. Guard the whole
         * shipping update - the courier hook below only needs the order row.
         */
        if ($shipping_update && $shippingfee && $shippingfee->name != $request->area) {
            if ($order->shipping_charge > $shippingfee->amount) {
                $total = $order->amount + ($shippingfee->amount - $order->shipping_charge);
                $order->shipping_charge = $shippingfee->amount;
                $order->amount = $total;
                $order->save();
            } else {
                $total = $order->amount + ($shippingfee->amount - $order->shipping_charge);
                $order->shipping_charge = $shippingfee->amount;
                $order->amount = $total;
                $order->save();
            }
        }

        if ($shipping_update && $shippingfee) {
            $shipping_update->name = $request->name;
            $shipping_update->phone = $request->phone;
            $shipping_update->address = $request->address;
            $shipping_update->area = $shippingfee->name;
            $shipping_update->save();
        }

        // Admin Approve Trigger: Pending -> On The Way
        if ($statusRow && (string) $statusRow->slug === 'on-the-way' && (int) $courier !== (int) $order->order_status) {
            DB::transaction(function () use ($order) {
                $orders_details = OrderDetails::select('id','order_id','product_id','variant_id','qty','product_size')
                    ->where('order_id', $order->id)
                    ->get();

                foreach ($orders_details as $order_details) {
                    $qty = (int) ($order_details->qty ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    $product = Product::select('id','product_code','name')->find($order_details->product_id);
                    if (!$product) {
                        continue;
                    }

                    $variantId = null;

                    if (!empty($order_details->variant_id)) {
                        $candidateId = (int) $order_details->variant_id;
                        $exists = DB::table('product_variants')
                            ->where('id', $candidateId)
                            ->where('product_id', $product->id)
                            ->exists();
                        if ($exists) {
                            $variantId = $candidateId;
                        }
                    }

                    $sizeMarker = (string) ($order_details->product_size ?? '');
                    if (!$variantId && $sizeMarker !== '' && preg_match('/\bvariant_id\s*:\s*(\d+)\b/i', $sizeMarker, $m)) {
                        $candidateId = (int) $m[1];
                        $exists = DB::table('product_variants')
                            ->where('id', $candidateId)
                            ->where('product_id', $product->id)
                            ->exists();
                        if ($exists) {
                            $variantId = $candidateId;
                        }
                    }

                    // Fallback: Ensure a default variant exists for ledger tracking
                    if (!$variantId) {
                        $defaultSku = ($product->product_code ? $product->product_code : ('P'.$product->id)) . '-DEFAULT';
                        $variantId = DB::table('product_variants')
                            ->where('sku', $defaultSku)
                            ->value('id');

                        if (!$variantId) {
                            $variantId = DB::table('product_variants')->insertGetId([
                                'product_id' => $product->id,
                                'sku' => $defaultSku,
                                'title' => 'Default',
                                'price_override' => null,
                                'weight_gram' => null,
                                'status' => 1,
                                'attributes_json' => null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    // Ensure cache row exists (sync with legacy product stock)
                    $stockRowExists = DB::table('variant_stocks')
                        ->where('variant_id', $variantId)
                        ->exists();
                    if (!$stockRowExists) {
                            $initial = 0;
                        DB::table('variant_stocks')->insert([
                            'variant_id' => $variantId,
                            'on_hand' => $initial,
                            'reserved' => 0,
                            'available' => $initial,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Idempotency: never deduct twice for the same order
                    $alreadyApplied = DB::table('inventory_movements')
                        ->where('variant_id', $variantId)
                        ->whereIn('type', ['ORDER_PLACED_DEDUCT', 'DELIVERED_DEDUCT', 'ON_THE_WAY_DEDUCT'])
                        ->where('ref_type', 'order')
                        ->where('ref_id', $order->id)
                        ->exists();
                    if ($alreadyApplied) {
                        continue;
                    }

                    $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->lockForUpdate()->first();
                    $available = (int) ($stock->available ?? 0);
                    if ($available < $qty) {
                        continue;
                    }

                    InventoryMovement::record([
                        'variant_id' => $variantId,
                        'type' => 'ON_THE_WAY_DEDUCT',
                        'qty_delta' => -1 * $qty,
                        'ref_type' => 'order',
                        'ref_id' => $order->id,
                        'created_by' => auth()->id(),
                        'note' => 'On the way approve stock deduct',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('variant_stocks')
                        ->where('variant_id', $variantId)
                        ->update([
                            'on_hand' => DB::raw('on_hand - ' . (int) $qty),
                            'available' => DB::raw('available - ' . (int) $qty),
                            'updated_at' => now(),
                        ]);

                }

                // Clear any active reservations once approved
                if (Schema::hasTable('inventory_reservations')) {
                    DB::table('inventory_reservations')->where('order_id', (int) $order->id)->delete();
                }
            });
        }

        /*
         * Auto-send to SteadFast when an order becomes SHIPPED (id 3).
         *
         * This used to fire on status id 5, but 5 = Cancelled in the seeded
         * order_statuses (see database/migrations/2026_10_18_000006). So
         * cancelling an order booked a parcel and marking it shipped sent
         * nothing. Shipped (3) is the status that means "the parcel goes out".
         *
         * Pathao is deliberately NOT auto-sent here: CourierOrderSender speaks
         * only the SteadFast protocol. Pathao goes through its own bulk/per-order
         * flow (OrderController::order_pathao_auto / the per-order courier screen).
         */
        if ((int) $request->status === 3 && (int) $courier !== 3) {
            $courierEnabledColumnExists = Schema::hasColumn('courier_apis', 'status');
            $steadfastQuery = Courierapi::query()->where('type', 'steadfast');
            if ($courierEnabledColumnExists) {
                $steadfastQuery->where('status', 1);
            }
            $courier_info = $steadfastQuery->first();
            if ($courier_info) {
                /*
                 * Routed through CourierOrderSender instead of an inline POST.
                 *
                 * This was the third copy of the payload builder, and the one
                 * with the worst fallback: a missing shipping row produced
                 *
                 *     'recipient_phone' => ... : '01750578495'
                 *
                 * i.e. an order with no customer was booked to a hardcoded
                 * number and billed. It also had no timeout and no try/catch,
                 * so a hung courier API hung the admin's status change, and it
                 * saved the consignment without checking the courier had
                 * actually returned one.
                 *
                 * CourierOrderSender refuses incomplete orders (naming the
                 * missing field), normalises the phone, times out, and records
                 * the consignment only when the courier really returned an id.
                 */
                $processResult = app(CourierOrderSender::class)->send($order, 'steadfast');

                if (! $processResult['ok']) {
                    Log::warning('SteadFast send failed on status change', [
                        'order_id' => $order->id,
                        'reason'   => $processResult['message'],
                    ]);

                    Toastr::error($processResult['message'], 'Courier');
                } else {
                    Toastr::success($processResult['message'], 'Courier');
                }
            } else {
                // B13: used to `return "ok"` here - the status was saved but the
                // admin saw a bare "ok" body and no parcel was booked. Say so.
                Toastr::warning(
                    'Order marked Shipped, but SteadFast is not switched on. '
                    . 'Enable it in Courier API Settings, or send this order from its courier screen.',
                    'Courier not configured'
                );
            }
            Toastr::success('Success', 'Order status change successfully');
            return redirect('admin/order/' . $link);
        }

        /*
         * Every other status (pending, processing, delivered, cancelled,
         * returned) has no courier hook, but still must send the admin back
         * with a confirmation. This method used to fall off the end and return
         * null for those statuses, so the status WAS saved (line 747) yet the
         * admin got an HTTP 500 error page instead of a redirect.
         */
        Toastr::success('Success', 'Order status change successfully');
        return redirect('admin/order/' . $link);
    }
    
    public function destroy(Request $request){
        $order = Order::where('id',$request->id)->delete();
        $order_details = OrderDetails::where('order_id',$request->id)->delete();
        $shipping = Shipping::where('order_id',$request->id)->delete();
        $payment = Payment::where('order_id',$request->id)->delete();
        Toastr::success('Success','Order delete success successfully');
        return redirect()->back();
    }
    
    public function order_assign(Request $request){
        $products = Order::whereIn('id', $request->input('order_ids'))->update(['user_id' => $request->user_id]);
        return response()->json(['status'=>'success','message'=>'Order user id assign']);
    }
    
    /**
 * Move orders to a status, applying the stock side-effects that status implies.
 *
 * Both stock blocks below - the On-The-Way deduct and the Delivered deduct -
 * used to live inline in order_status(), 250 lines of it. The Kanban board
 * moves an order with a drag and must run exactly the same stock arithmetic
 * as the list view's status dropdown, so the arithmetic is now a named
 * method and both callers go through it. One implementation of the stock
 * rule is the point: two copies is how a product ends up with stock
 * decremented twice, or not at all.
 *
 * @deprecated 2026-11-16 — no caller remains. changeOrderStatus() now delegates
 *             every transition to App\Services\OrderLifecycle, whose
 *             InventoryLedger writes RESERVE/SALE/RELEASE. This method is kept
 *             only until the DROP-LEGACY-STOCK step removes the old
 *             ON_THE_WAY_DEDUCT movement types with it; do not call it.
 *
 * @param  array<int,int>  $orderIds
 */
private function applyOrderStatusStock(array $orderIds, $statusId): void
{
    $newStatus = OrderStatus::find((int) $statusId);

    if ($newStatus && (string) $newStatus->slug === 'on-the-way') {
        $orders = Order::whereIn('id', $orderIds)->get();
            foreach ($orders as $order) {
                DB::transaction(function () use ($order) {
                    $orders_details = OrderDetails::select('id','order_id','product_id','variant_id','qty','product_size')
                        ->where('order_id', $order->id)
                        ->get();

                    foreach ($orders_details as $order_details) {
                        $qty = (int) ($order_details->qty ?? 0);
                        if ($qty <= 0) {
                            continue;
                        }

                        $product = Product::select('id','product_code','name')->find($order_details->product_id);
                        if (!$product) {
                            continue;
                        }

                        $variantId = null;

                        if (!empty($order_details->variant_id)) {
                            $candidateId = (int) $order_details->variant_id;
                            $exists = DB::table('product_variants')
                                ->where('id', $candidateId)
                                ->where('product_id', $product->id)
                                ->exists();
                            if ($exists) {
                                $variantId = $candidateId;
                            }
                        }

                        $sizeMarker = (string) ($order_details->product_size ?? '');
                        if (!$variantId && $sizeMarker !== '' && preg_match('/\bvariant_id\s*:\s*(\d+)\b/i', $sizeMarker, $m)) {
                            $candidateId = (int) $m[1];
                            $exists = DB::table('product_variants')
                                ->where('id', $candidateId)
                                ->where('product_id', $product->id)
                                ->exists();
                            if ($exists) {
                                $variantId = $candidateId;
                            }
                        }

                        if (!$variantId) {
                            $defaultSku = ($product->product_code ? $product->product_code : ('P'.$product->id)) . '-DEFAULT';
                            $variantId = DB::table('product_variants')
                                ->where('sku', $defaultSku)
                                ->value('id');

                            if (!$variantId) {
                                $variantId = DB::table('product_variants')->insertGetId([
                                    'product_id' => $product->id,
                                    'sku' => $defaultSku,
                                    'title' => 'Default',
                                    'price_override' => null,
                                    'weight_gram' => null,
                                    'status' => 1,
                                    'attributes_json' => null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }

                        $stockRowExists = DB::table('variant_stocks')
                            ->where('variant_id', $variantId)
                            ->exists();
                        if (!$stockRowExists) {
                            $initial = 0;
                            DB::table('variant_stocks')->insert([
                                'variant_id' => $variantId,
                                'on_hand' => $initial,
                                'reserved' => 0,
                                'available' => $initial,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        $alreadyApplied = DB::table('inventory_movements')
                            ->where('variant_id', $variantId)
                            ->whereIn('type', ['ORDER_PLACED_DEDUCT', 'DELIVERED_DEDUCT', 'ON_THE_WAY_DEDUCT'])
                            ->where('ref_type', 'order')
                            ->where('ref_id', $order->id)
                            ->exists();
                        if ($alreadyApplied) {
                            continue;
                        }

                        $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->lockForUpdate()->first();
                        $available = (int) ($stock->available ?? 0);
                        if ($available < $qty) {
                            continue;
                        }

                        InventoryMovement::record([
                            'variant_id' => $variantId,
                            'type' => 'ON_THE_WAY_DEDUCT',
                            'qty_delta' => -1 * $qty,
                            'ref_type' => 'order',
                            'ref_id' => $order->id,
                            'created_by' => auth()->id(),
                            'note' => 'On the way approve stock deduct',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('variant_stocks')
                            ->where('variant_id', $variantId)
                            ->update([
                                'on_hand' => DB::raw('on_hand - ' . (int) $qty),
                                'available' => DB::raw('available - ' . (int) $qty),
                                'updated_at' => now(),
                            ]);

                    }

                    if (Schema::hasTable('inventory_reservations')) {
                        DB::table('inventory_reservations')->where('order_id', (int) $order->id)->delete();
                    }
                });
            }
        }

    if ((int) $statusId === 5) {
            $orders = Order::whereIn('id', $orderIds)->get();
            foreach($orders as $order){
                DB::transaction(function () use ($order) {
                    $orders_details = OrderDetails::select('id','order_id','product_id','variant_id','qty','product_size')
                        ->where('order_id', $order->id)
                        ->get();

                    foreach($orders_details as $order_details){
                        $qty = (int) ($order_details->qty ?? 0);
                        if ($qty <= 0) {
                            continue;
                        }

                        $product = Product::select('id','product_code','name')->find($order_details->product_id);
                        if (!$product) {
                            continue;
                        }

                        $variantId = null;

                        if (!empty($order_details->variant_id)) {
                            $candidateId = (int) $order_details->variant_id;
                            $exists = DB::table('product_variants')
                                ->where('id', $candidateId)
                                ->where('product_id', $product->id)
                                ->exists();
                            if ($exists) {
                                $variantId = $candidateId;
                            }
                        }

                        $sizeMarker = (string) ($order_details->product_size ?? '');
                        if (!$variantId && $sizeMarker !== '' && preg_match('/\bvariant_id\s*:\s*(\d+)\b/i', $sizeMarker, $m)) {
                            $candidateId = (int) $m[1];
                            $exists = DB::table('product_variants')
                                ->where('id', $candidateId)
                                ->where('product_id', $product->id)
                                ->exists();
                            if ($exists) {
                                $variantId = $candidateId;
                            }
                        }

                        // Fallback: Ensure a default variant exists for ledger tracking
                        if (!$variantId) {
                            $defaultSku = ($product->product_code ? $product->product_code : ('P'.$product->id)) . '-DEFAULT';
                            $variantId = DB::table('product_variants')
                                ->where('sku', $defaultSku)
                                ->value('id');

                            if (!$variantId) {
                                $variantId = DB::table('product_variants')->insertGetId([
                                    'product_id' => $product->id,
                                    'sku' => $defaultSku,
                                    'title' => 'Default',
                                    'price_override' => null,
                                    'weight_gram' => null,
                                    'status' => 1,
                                    'attributes_json' => null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }

                        // Initialize cache row if missing (sync with current product stock before deduct)
                        $stockRowExists = DB::table('variant_stocks')
                            ->where('variant_id', $variantId)
                            ->exists();
                        if (!$stockRowExists) {
                            $initial = 0;
                            DB::table('variant_stocks')->insert([
                                'variant_id' => $variantId,
                                'on_hand' => $initial,
                                'reserved' => 0,
                                'available' => $initial,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        // Idempotency: if stock was already deducted at order placement OR approved (on-the-way) OR delivered, skip
                        $alreadyApplied = DB::table('inventory_movements')
                            ->where('variant_id', $variantId)
                            ->whereIn('type', ['ORDER_PLACED_DEDUCT', 'ON_THE_WAY_DEDUCT', 'DELIVERED_DEDUCT'])
                            ->where('ref_type', 'order')
                            ->where('ref_id', $order->id)
                            ->exists();
                        if ($alreadyApplied) {
                            continue;
                        }

                        // Ledger write (truth)
                        InventoryMovement::record([
                            'variant_id' => $variantId,
                            'type' => 'DELIVERED_DEDUCT',
                            'qty_delta' => -1 * $qty,
                            'ref_type' => 'order',
                            'ref_id' => $order->id,
                            'created_by' => auth()->id(),
                            'note' => 'Delivered order stock deduct',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Cache update
                        DB::table('variant_stocks')
                            ->where('variant_id', $variantId)
                            ->update([
                                'on_hand' => DB::raw('on_hand - ' . (int) $qty),
                                'available' => DB::raw('available - ' . (int) $qty),
                                'updated_at' => now(),
                            ]);
                    }
                });
            }
        }
}

/**
 * Change the status of the selected orders from the list view.
 *
 * A thin caller: it validates and hands every order to changeOrderStatus(),
 * which delegates the stock side-effects to App\Services\OrderLifecycle. The
 * Kanban board calls the same routine, so both paths share one implementation.
 */
public function order_status(Request $request)
    {
        $data = $request->validate([
            'order_ids'    => ['required', 'array', 'min:1'],
            'order_ids.*'  => ['integer', 'exists:orders,id'],
            'order_status' => ['required', 'integer', 'exists:order_statuses,id'],
        ]);

        $ids = array_map('intval', $data['order_ids']);

        $failures = $this->changeOrderStatus($ids, (int) $data['order_status']);

        if ($failures !== []) {
            $message = count($failures) . ' order(s) could not change status: ' . $failures[0]['message'];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'failed', 'message' => $message, 'failures' => $failures]);
            }

            Toastr::error($message, 'Failed');

            return redirect()->back(302, [], route('admin.orders', ['slug' => 'all']));
        }

        $message = 'Order status change successfully';

        /*
         * Only AJAX callers want JSON.
         *
         * The per-row dropdown is a plain <form> submit, so answering JSON
         * printed {"status":"success","message":"..."} on a blank page instead
         * of returning the admin to the order list. The bulk modal and the
         * Kanban board call this endpoint over AJAX and still get JSON.
         */
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => $message]);
        }

        Toastr::success($message, 'Success');

        // Fallback keeps a direct POST (no referer) on the order list instead
        // of dumping the admin on the dashboard.
        return redirect()->back(302, [], route('admin.orders', ['slug' => 'all']));
    }

    /**
     * The one status-change routine. The list view's dropdown and the Kanban
     * board's drag both land here.
     *
     * Every order moves through App\Services\OrderLifecycle: one transaction
     * that writes the status, the stock side-effect in the ledger and the audit
     * row. Nothing is duplicated here any more, so the board and the dropdown
     * cannot disagree about stock.
     *
     * Public because OrderBoardController reuses it, not to be called freely:
     * it assumes the ids and the status have already been validated. The two
     * callers validate first, and each of them goes through its own
     * permission-checked route.
     *
     * @param  array<int,int>  $ids
     * @return array<int, array{order_id:int, message:string}> orders that failed
     */
    public function changeOrderStatus(array $ids, int $statusId): array
    {
        if ($ids === []) {
            return [];
        }

        $status = OrderStatus::find($statusId);

        if (! $status || trim((string) $status->slug) === '') {
            return [['order_id' => 0, 'message' => 'Unknown order status #' . $statusId]];
        }

        $failures = [];

        foreach (Order::whereIn('id', $ids)->orderBy('id')->get() as $order) {
            if ((string) $order->order_status === (string) $statusId) {
                continue;   // it did not move
            }

            try {
                app(\App\Services\OrderLifecycle::class)->transition(
                    $order,
                    (string) $status->slug
                );
            } catch (\Throwable $e) {
                // One order with a stock problem must not stop the batch, but
                // the caller is told which one failed and why.
                Log::warning('Order status change failed', [
                    'order_id' => $order->id,
                    'status_id' => $statusId,
                    'slug' => $status->slug,
                    'error' => $e->getMessage(),
                ]);

                $failures[] = ['order_id' => (int) $order->id, 'message' => $e->getMessage()];
            }
        }

        return $failures;
    }

    public function bulk_destroy(Request $request){
        $orders_id = $request->order_ids;
        foreach($orders_id as $order_id){
            $order = Order::where('id',$order_id)->delete();
            $order_details = OrderDetails::where('order_id',$order_id)->delete();
            $shipping = Shipping::where('order_id',$order_id)->delete();
            $payment = Payment::where('order_id',$order_id)->delete();
        }
        return response()->json(['status'=>'success','message'=>'Order delete successfully']);
    }
    public function order_print(Request $request){
        // whereIn() with a null list reaches count(null) and throws a TypeError,
        // so a bare GET on this URL was a 500 instead of a message. The order
        // grid's JS already refuses to call it with nothing ticked (order/
        // index.blade.php, .multi_order_print); this is the server-side half of
        // that guard, and it answers in the shape that JS expects.
        $orderIds = (array) $request->input('order_ids', []);

        if (empty($orderIds)) {
            return response()->json(['status' => 'failed', 'message' => 'No orders selected']);
        }

        $orders = Order::whereIn('id', $orderIds)->with('orderdetails','payment','shipping','customer')->get();
        $view = view('backEnd.order.print', ['orders' => $orders])->render();
        return response()->json(['status' => 'success', 'view' => $view]);
    }
    public function bulk_courier($slug, Request $request)
    {
        // Delegate to pathao auto method for pathao courier
        if ((string) $slug === 'pathao') {
            return $this->order_pathao_auto($request);
        }

        $courierEnabledColumnExists = Schema::hasColumn('courier_apis', 'status');
        $courierQuery = Courierapi::query()->where('type', $slug);
        if ($courierEnabledColumnExists) {
            $courierQuery->where('status', 1);
        }
        $courier_info = $courierQuery->first();
        if (!$courier_info) {
            return response()->json(['status' => 'failed', 'message' => 'Courier is not configured']);
        }

        $orders_id = (array) $request->order_ids;
        $results = [];

        foreach ($orders_id as $order_id) {
            $order = Order::with('shipping')->find($order_id);
            if (!$order) {
                $results[] = ['order_id' => $order_id, 'status' => 'failed', 'message' => 'Order not found'];
                continue;
            }

            try {
                $statusId = (int) $order->order_status;
                // B2: skip the TERMINAL states - an order that is already
                // delivered (4), cancelled (5) or returned (6) must never be
                // booked to a courier. This guard used to check only id 5 and
                // label it "delivered", but 5 = cancelled in the seeded
                // order_statuses, so a cancelled order was mislabelled and a
                // delivered one was not skipped at all.
                if (in_array($statusId, [4, 5, 6], true)) {
                    $label = [4 => 'delivered', 5 => 'cancelled', 6 => 'returned'][$statusId];
                    $results[] = ['order_id' => $order_id, 'status' => 'skipped', 'message' => 'Order is already ' . $label];
                    continue;
                }

                /*
                 * SteadFast is routed through CourierOrderSender rather than
                 * building the payload inline here.
                 *
                 * What this replaces, and why it mattered:
                 *
                 *     'recipient_name'    => $order->shipping ? $order->shipping->name    : 'InboxHat',
                 *     'recipient_phone'   => $order->shipping ? $order->shipping->phone   : '01750578495',
                 *     'recipient_address' => $order->shipping ? $order->shipping->address : '01750578495',
                 *
                 * An order with a missing shipping row was NOT rejected - it was
                 * sent to a hardcoded number and billed for. The service refuses,
                 * names the missing field, validates the phone, and refuses to
                 * double-book an order that already has a consignment.
                 *
                 * Pathao keeps its original flow further down, unchanged.
                 */
                if ((string) $slug === 'steadfast') {
                    $steadiResult = app(CourierOrderSender::class)->send($order, 'steadfast');

                    if (! $steadiResult['ok']) {
                        $results[] = [
                            'order_id' => $order_id,
                            'status'   => 'failed',
                            'message'  => $steadiResult['message'],
                        ];
                        continue;
                    }

                    /*
                     * NOTE the status is deliberately NOT advanced here.
                     *
                     * This block used to do `$order->order_status = 4`. That
                     * assumed id 4 meant "sent to courier", but 4 is not a
                     * courier concept at all - it is whatever row happens to sit
                     * at that id in order_statuses. Before those rows existed the
                     * value dangled and the admin column showed blank; once they
                     * were seeded id 4 is "delivered", so every order would have
                     * jumped straight to DELIVERED the moment it was booked.
                     *
                     * Handing the parcel to the courier is not a delivery
                     * outcome. The status moves when the courier webhook (or an
                     * admin) says so, which is the only place that knows.
                     */
                    $results[] = [
                        'order_id' => $order_id,
                        'status'   => 'success',
                        'message'  => $steadiResult['message'],
                    ];

                    continue;
                }

                /*
                 * Pathao keeps its original flow below, but WITHOUT the invented
                 * recipient.
                 *
                 * It carried the same fallbacks the SteadFast copies did:
                 *
                 *     'recipient_phone' => ... : '01750578495'
                 *
                 * so an order with no shipping row would have been booked to a
                 * hardcoded number. Pathao is a different API and is not routed
                 * through CourierOrderSender yet - that service speaks the
                 * SteadFast create_order shape - so the guard is written out
                 * here instead: same rule, no invented address.
                 */
                $recipientName    = trim((string) ($order->shipping->name ?? ''));
                $recipientPhone   = trim((string) ($order->shipping->phone ?? ''));
                $recipientAddress = trim((string) ($order->shipping->address ?? ''));

                if ($recipientName === '' || $recipientPhone === '' || $recipientAddress === '') {
                    $missing = [];
                    if ($recipientName === '')    { $missing[] = 'name'; }
                    if ($recipientPhone === '')   { $missing[] = 'phone'; }
                    if ($recipientAddress === '') { $missing[] = 'address'; }

                    $results[] = [
                        'order_id' => $order_id,
                        'status'   => 'failed',
                        'message'  => 'Not sent - missing recipient ' . implode(', ', $missing)
                            . '. Fill it in on the order first.',
                    ];
                    continue;
                }

                $consignmentData = [
                    'invoice' => $order->invoice_id,
                    'recipient_name' => $recipientName,
                    'recipient_phone' => $recipientPhone,
                    'recipient_address' => $recipientAddress,
                    'cod_amount' => $order->amount
                ];

                $client = new Client();

                $courierUrl = (string) $courier_info->url;
                if ((string) $slug === 'steadfast' && $courierUrl !== '' && !str_contains($courierUrl, '/create_order')) {
                    $courierUrl = $this->joinUrl($courierUrl, 'create_order');
                }

                $response = $client->post($courierUrl, [
                    'json' => $consignmentData,
                    'headers' => [
                        'Api-Key' => $courier_info->api_key,
                        'Secret-Key' => $courier_info->secret_key,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'http_errors' => false,
                ]);

                $httpStatus = (int) $response->getStatusCode();
                $body = (string) $response->getBody();
                $responseData = null;
                try {
                    $responseData = json_decode($body, true);
                } catch (\Throwable $e) {
                    $responseData = null;
                }
                if (!is_array($responseData)) {
                    $responseData = ['raw_body' => $body, 'http_status' => $httpStatus];
                }

                $apiStatus = (int) data_get($responseData, 'status', 0);

                if ($apiStatus === 200) {
                    /*
                     * The order status is deliberately NOT advanced on a
                     * successful send.
                     *
                     * This was `$order->order_status = 4`, which assumed id 4
                     * meant "handed to the courier". It does not: 4 is just
                     * whatever row sits at that position in order_statuses.
                     * Those rows did not exist, so the value dangled and the
                     * admin status column rendered blank; once they were seeded
                     * with the obvious lifecycle, id 4 is "delivered", and every
                     * order would jump straight to DELIVERED the instant it was
                     * booked - a customer with a parcel still sitting in the
                     * warehouse would show as delivered.
                     *
                     * Booking is not a delivery outcome. The status moves when
                     * the courier webhook says so, or when an admin sets it.
                     */
                    try {
                        $deliveryFee = data_get($responseData, 'consignment.delivery_fee')
                            ?? data_get($responseData, 'delivery_fee');

                        CourierShipment::query()->updateOrCreate(
                            [
                                'order_id' => $order->id,
                                'provider' => (string) $slug,
                            ],
                            [
                                'merchant_order_id' => (string) $order->invoice_id,
                                'consignment_id' => isset($responseData['consignment']['consignment_id']) ? (string) $responseData['consignment']['consignment_id'] : (isset($responseData['consignment_id']) ? (string) $responseData['consignment_id'] : null),
                                'tracking_code' => isset($responseData['consignment']['tracking_code']) ? (string) $responseData['consignment']['tracking_code'] : (isset($responseData['tracking_code']) ? (string) $responseData['tracking_code'] : null),
                                'current_status' => isset($responseData['consignment']['status']) ? (string) $responseData['consignment']['status'] : null,
                                'shipping_cost' => is_numeric($deliveryFee) ? (float) $deliveryFee : null,
                                'raw_payload' => $responseData,
                                'last_synced_at' => now(),
                            ]
                        );
                    } catch (\Throwable $e) {
                    }

                    $results[] = ['order_id' => $order_id, 'status' => 'success', 'message' => 'Your order place to courier successfully'];
                } else {
                    $message = (string) data_get($responseData, 'message', 'Your order place to courier failed');
                    if ($httpStatus >= 400 && $message === 'Your order place to courier failed') {
                        $message = 'Courier API HTTP ' . $httpStatus;
                    }
                    $results[] = ['order_id' => $order_id, 'status' => 'failed', 'message' => $message, 'raw' => $responseData, 'http_status' => $httpStatus];
                }
            } catch (\Throwable $e) {
                $results[] = ['order_id' => $order_id, 'status' => 'failed', 'message' => $e->getMessage()];
            }
        }

        return response()->json([
            'status' => 'completed',
            'results' => $results,
        ]);
    }
    /*
     * Inventory is a projection: product_variants + variant_stocks hold the
     * SSOT and inventory_movements is the append-only ledger. The legacy
     * product-level stock column was dropped, so this report reads the
     * projection only.
     *
     * Units are on_hand - physically present stock. A damaged unit is still
     * in the warehouse, so it counts here although it is not sellable. Money
     * uses the variant's own price/cost when it sets one, falling back to the
     * product, because a variant override is what the shopper actually pays.
     */
    public function stock_report(Request $request){
        $products = Product::query()->where('products.status', 1);
        if ($request->keyword) {
            $products->where('products.name', 'LIKE', '%' . $request->keyword . '%');
        }
        if ($request->category_id) {
            $products->where('products.category_id', $request->category_id);
        }
        if ($request->start_date && $request->end_date) {
            // The filter inputs are dates, so end_date must mean the end of
            // that day; comparing against the raw string cut it at midnight.
            $products->whereBetween('products.updated_at', [
                \Carbon\Carbon::parse($request->start_date)->startOfDay(),
                \Carbon\Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        // Footer totals cover the whole filter, not the ten rows on this page.
        $totals = (clone $products)
            ->join('product_variants as v', function ($join) {
                $join->on('v.product_id', '=', 'products.id')->where('v.status', 1);
            })
            ->join('variant_stocks as s', 's.variant_id', '=', 'v.id')
            ->selectRaw('COALESCE(SUM(s.on_hand), 0) as units')
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(v.price_override, 0), products.new_price) * s.on_hand), 0) as stock_value')
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(v.purchase_price, 0), products.purchase_price) * s.on_hand), 0) as stock_cost')
            ->first();

        $total_stock = (int) ($totals->units ?? 0);
        $total_price = (float) ($totals->stock_value ?? 0);
        $total_purchase = (float) ($totals->stock_cost ?? 0);

        $products = $products
            ->select('products.id', 'products.name', 'products.new_price')
            ->selectSub(function ($query) {
                $query->from('product_variants as v')
                    ->join('variant_stocks as s', 's.variant_id', '=', 'v.id')
                    ->whereColumn('v.product_id', 'products.id')
                    ->where('v.status', 1)
                    ->selectRaw('COALESCE(SUM(s.on_hand), 0)');
            }, 'variant_stock')
            ->selectSub(function ($query) {
                $query->from('product_variants as v')
                    ->join('variant_stocks as s', 's.variant_id', '=', 'v.id')
                    ->whereColumn('v.product_id', 'products.id')
                    ->where('v.status', 1)
                    ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(v.price_override, 0), products.new_price) * s.on_hand), 0)');
            }, 'variant_stock_value')
            ->paginate(10)
            ->withQueryString();

        $products->getCollection()->transform(function ($product) {
            $units = (int) ($product->variant_stock ?? 0);
            $value = (float) ($product->variant_stock_value ?? 0);
            $product->setAttribute('stock_on_hand', $units);
            $product->setAttribute('stock_value', $value);
            $product->setAttribute('unit_price', $units > 0 ? $value / $units : (float) ($product->new_price ?? 0));
            return $product;
        });

        $categories = Category::where('status',1)->get();
        return view('backEnd.reports.stock',compact('products','categories','total_purchase','total_stock','total_price'));
    }
    public function order_report(Request $request){
        $users = User::where('status',1)->get();

        /*
         * The report counts DELIVERED sales.

         * Two things made the old query return nothing:
         *
         * 1. It filtered `order_status = 6`. orders.order_status is a varchar
         *    that holds a SLUG ('pending', 'delivered', ...), and 6 is the id
         *    of 'returned' - not a sale. The status is now resolved by slug
         *    (the same way OrderController::index() does it) and the query
         *    accepts either spelling, because the column has held a numeric
         *    id as well since order_save() writes `order_status = 1`.
         *
         * 2. The keyword filter searched `order_details.name`, a column that
         *    does not exist, so every keyword search was a 500. The report
         *    searches the three things a user can actually see on a row:
         *    the product name, the invoice number and the customer name.
         */
        $delivered = $this->resolveOrderStatusBySlug('delivered');

        $orders = OrderDetails::with('shipping','order')->whereHas('order', function ($query) use ($delivered) {
                $query->where(function ($q) use ($delivered) {
                    if ($delivered) {
                        $q->where('order_status', $delivered->slug)
                          ->orWhere('order_status', (string) $delivered->id)
                          ->orWhere('order_status', $delivered->id);
                    } else {
                        $q->where('order_status', 'delivered');
                    }
                });
            });

        if ($request->keyword) {
            $keyword = '%' . $request->keyword . '%';
            $orders = $orders->where(function ($q) use ($keyword) {
                $q->where('product_name', 'LIKE', $keyword)
                  ->orWhereHas('order', function ($oq) use ($keyword) {
                      $oq->where('invoice_id', 'LIKE', $keyword);
                  })
                  ->orWhereHas('shipping', function ($sq) use ($keyword) {
                      $sq->where('name', 'LIKE', $keyword)
                        ->orWhere('phone', 'LIKE', $keyword);
                  });
            });
        }
        if ($request->user_id) {
            $orders = $orders->whereHas('order', function ($query) use ($request) {
                $query->where('user_id', $request->user_id);
            });
        }
        if ($request->start_date && $request->end_date) {
            $orders =$orders->whereBetween('updated_at', [$request->start_date,$request->end_date]);
        }
        $total_purchase = $orders->sum(\DB::raw('purchase_price * qty'));
        $total_item = $orders->sum('qty');
        $total_sales = $orders->sum(\DB::raw('sale_price * qty'));
        $orders = $orders->paginate(10);
        return view('backEnd.reports.order',compact('orders','users','total_purchase','total_item','total_sales'));
    }

    public function order_create(){
        $products = Product::select('id','name','new_price','product_code')->where(['status'=>1])->get();
        $cartinfo  = Cart::instance('pos_shopping')->content();
        $shippingcharge = ShippingCharge::where('status',1)->get();
        return view('backEnd.order.create',compact('products','cartinfo','shippingcharge'));
    }
    
    public function order_store(Request $request){
        // Validate request with custom messages
        $validated = $this->validate($request,[
            'name'=>'required',
            'phone'=>'required',
            'address'=>'required',
            'area'=>'required',
            'admin_note' => 'nullable|string|max:5000',
        ], [
            'name.required' => 'Customer name is required',
            'phone.required' => 'Phone number is required',
            'address.required' => 'Address is required',
            'area.required' => 'Shipping area is required',
        ]);

        // Check if cart has items
        if(Cart::instance('pos_shopping')->count() <= 0) {
            Toastr::error('Please add products to cart first', 'Failed!');
            return redirect()->back();
        }

        try {
            // Calculate totals
            $subtotal = Cart::instance('pos_shopping')->subtotal();
            $subtotal = str_replace(',','',$subtotal);
            $subtotal = str_replace('.00', '',$subtotal);
            $discount = $this->posTotalDiscount();
            $shippingfee  = ShippingCharge::find($request->area);
            
            if (!$shippingfee) {
                Toastr::error('Invalid shipping area selected', 'Error!');
                return redirect()->back();
            }
            
            // Find or create customer
            $exits_customer = Customer::where('phone',$request->phone)->select('phone','id')->first();
            if($exits_customer){
                $customer_id = $exits_customer->id;
            }else{
                $password = rand(111111,999999);
                $store              = new Customer();
                $store->name        = $request->name;
                $store->slug        = $request->name;
                $store->phone       = $request->phone;
                $store->password    = bcrypt($password);
                $store->verify      = 1;
                $store->status      = 'active';
                $store->save();
                $customer_id = $store->id;
            }
     
            // Save order data
            $order                   = new Order();
            $order->invoice_id       = rand(11111,99999);
            $order->amount           = ($subtotal + $shippingfee->amount) - $discount;
            $order->discount         = $discount ? $discount : 0;
            $order->shipping_charge  = $shippingfee->amount;
            $order->customer_id      =  $customer_id;
            $order->order_status     = 1;
            // The POS form always had a visible "Admin Note" after this fix;
            // the column has existed since 2026_03_03_124223.
            $order->admin_note       = $request->admin_note;

            /*
             * No `note` here.
             *
             * This used to be `$order->note = $request->note;`, but the orders
             * table has never had a `note` column - the admin note lives in
             * `admin_note` (added by 2026_03_03_124223) and the POS form has no
             * note field at all, so `$request->note` was always null. Setting
             * the attribute anyway put `note` into the INSERT statement, and
             * EVERY order created from the POS screen died with
             * "SQLSTATE[42S22]: Unknown column 'note' in 'field list'".
             * The admin could not create an order here at all.
             */
            $order->save();

            // The audit trail: a new order is the first thing that happened.
            app(\App\Services\AuditLogger::class)->orderCreated($order);

            // POS coupon usage log: the till session carries pos_coupon_code
            // (set by cart_apply_coupon). Record it against this order so the
            // admin coupon details page counts till sales too.
            if ($posCouponCode = trim((string) Session::get('pos_coupon_code'))) {
                if ($posCoupon = \App\Models\Coupon::where('code', $posCouponCode)->first()) {
                    \App\Models\CouponUsage::record($posCoupon, $order->id, (float) (Session::get('pos_coupon_discount') ?: 0), 'pos');
                }
                Session::forget('pos_coupon_code');
                Session::forget('pos_coupon_discount');
            }

            // Save shipping data
            $shipping              =   new Shipping();
            $shipping->order_id    =   $order->id;
            $shipping->customer_id =   $customer_id;
            $shipping->name        =   $request->name;
            $shipping->phone       =   $request->phone;
            $shipping->address     =   $request->address;
            $shipping->area        =   $shippingfee->name;
            $shipping->save();

            // Save payment data
            $payment                 = new Payment();
            $payment->order_id       = $order->id;
            $payment->customer_id    = $customer_id;
            $payment->payment_method = 'Cash On Delivery';
            $payment->amount         = $order->amount;
            $payment->payment_status = 'pending';
            $payment->save();

            // Save order details
            foreach(Cart::instance('pos_shopping')->content() as $cart){
                $order_details                   =   new OrderDetails();
                $order_details->order_id         =   $order->id;
                $order_details->product_id       =   $cart->id;
                $order_details->product_name     =   $cart->name;
                $order_details->purchase_price   =   $cart->options->purchase_price;
                $order_details->product_discount =   $cart->options->product_discount;
                $order_details->sale_price       =   $cart->price;
                $order_details->qty              =   $cart->qty;
                $order_details->save();
            }
            
            // Clear cart and sessions
            Cart::instance('pos_shopping')->destroy();
            Session::forget('pos_shipping');
            Session::forget('pos_manual_discount');
            Session::forget('pos_coupon_discount');
            Session::forget('pos_coupon_code');
            Session::forget('product_discount');
            
            // Show success message
            Toastr::success('Thanks, Your order place successfully. Invoice ID: ' . $order->invoice_id, 'Success!');
            return redirect()->route('admin.order.edit', ['invoice_id' => $order->invoice_id]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Order creation failed: ' . $e->getMessage());
            
            // Show error message
            Toastr::error('Order creation failed: ' . $e->getMessage(), 'Error!');
            return redirect()->back()->withInput();
        }
    }

    public function cart_extra_discount(Request $request)
    {
        $discount = (float) $request->input('discount', 0);
        $discount = max(0.0, $discount);
        Session::put('pos_manual_discount', $discount);
        return response()->json([
            'manual_discount' => $discount,
            'total_extra_discount' => $this->posExtraDiscountTotal(),
        ]);
    }

    public function cart_apply_coupon(Request $request)
    {
        $code = strtoupper(trim((string) $request->input('coupon_code')));

        if ($code === '') {
            Session::forget('pos_coupon_discount');
            Session::forget('pos_coupon_code');
            return response()->json([
                'status' => 'success',
                'message' => 'Coupon cleared',
                'amount' => 0,
                'coupon_code' => null,
            ]);
        }

        $coupon = Coupon::where('code', $code)->running()->first();
        if (!$coupon || !$coupon->isUsageAvailable()) {
            Session::forget('pos_coupon_discount');
            Session::forget('pos_coupon_code');
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired coupon',
            ]);
        }

        $eligibleSubtotal = (float) str_replace([',', '.00'], ['', ''], (string) Cart::instance('pos_shopping')->subtotal());
        if ($coupon->isRestrictedToProducts()) {
            $allowedIds = $coupon->products()->pluck('products.id')->all();
            $eligibleSubtotal = 0.0;

            foreach (Cart::instance('pos_shopping')->content() as $item) {
                if (in_array((int) $item->id, $allowedIds, true)) {
                    $eligibleSubtotal += ((float) $item->price) * ((float) $item->qty);
                }
            }
        }

        $discount = (float) $coupon->calculateDiscount((float) $eligibleSubtotal);
        if ($discount <= 0) {
            Session::forget('pos_coupon_discount');
            Session::forget('pos_coupon_code');
            return response()->json([
                'status' => 'error',
                'message' => 'Coupon is not applicable for this cart',
            ]);
        }

        Session::put('pos_coupon_discount', $discount);
        Session::put('pos_coupon_code', $coupon->code);

        return response()->json([
            'status' => 'success',
            'message' => 'Coupon applied successfully',
            'amount' => $discount,
            'coupon_code' => $coupon->code,
        ]);
    }

    public function cart_remove_coupon(Request $request)
    {
        Session::forget('pos_coupon_discount');
        Session::forget('pos_coupon_code');

        return response()->json([
            'status' => 'success',
            'message' => 'Coupon removed',
        ]);
    }
    public function cart_add(Request $request){
        $product = Product::with('image')
            ->select('id','name','new_price','old_price','purchase_price','slug')
            ->where(['id' => $request->id])
            ->first();
        
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }
        
        $qty = 1;
        Cart::instance('pos_shopping')->add([
            'id' => $product->id,
            'name' => $product->name,
            'qty' => $qty,
            'price' => $product->new_price,
            'options' => [
                'slug' => $product->slug,
                'image' => $product->image ? ($product->image->image ?? '') : '',
                'old_price' => $product->old_price,
                'purchase_price' => $product->purchase_price,
                'product_discount' => 0,
            ],
        ]);
        
        $cartinfo = Cart::instance('pos_shopping')->content();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product added to cart successfully',
            'cartinfo' => $cartinfo,
            'count' => Cart::instance('pos_shopping')->count()
        ]);
    }
    public function cart_content(){
        $cartinfo = Cart::instance('pos_shopping')->content();
        return view('backEnd.order.cart_content',compact('cartinfo'));
    }
    public function cart_details(){
        $cartinfo = Cart::instance('pos_shopping')->content();
        $discount = 0;
        foreach($cartinfo as $cart){
            $discount += ($cart->options->product_discount ?? 0) * $cart->qty;
        }
        Session::put('product_discount',$discount);
        return view('backEnd.order.cart_details',compact('cartinfo'));
    }
    public function cart_increment(Request $request){
        $data = $request->validate([
            'id' => 'required|string',
            'qty' => 'required|integer|min:1',
        ]);

        try {
            $cartinfo = Cart::instance('pos_shopping')->update($data['id'], $data['qty'] + 1);
        } catch (InvalidRowIDException $e) {
            return response()->json(['message' => 'That cart row is no longer in the cart.'], 404);
        }

        return response()->json($cartinfo);
    }

    public function cart_decrement(Request $request){
        $data = $request->validate([
            'id' => 'required|string',
            'qty' => 'required|integer|min:1',
        ]);

        try {
            // At one unit the minus button means "remove", not "quantity 0":
            // a zero-quantity row would still be printed on the cart page.
            if ($data['qty'] <= 1) {
                Cart::instance('pos_shopping')->remove($data['id']);

                return response()->json(Cart::instance('pos_shopping')->content());
            }

            $cartinfo = Cart::instance('pos_shopping')->update($data['id'], $data['qty'] - 1);
        } catch (InvalidRowIDException $e) {
            return response()->json(['message' => 'That cart row is no longer in the cart.'], 404);
        }

        return response()->json($cartinfo);
    }

    public function cart_remove(Request $request){
        $data = $request->validate(['id' => 'required|string']);

        try {
            Cart::instance('pos_shopping')->remove($data['id']);
        } catch (InvalidRowIDException $e) {
            return response()->json(['message' => 'That cart row is no longer in the cart.'], 404);
        }

        return response()->json(Cart::instance('pos_shopping')->content());
    }

    public function product_discount(Request $request){
        $data = $request->validate([
            'id' => 'required|string',
            'discount' => 'required|numeric|min:0',
        ]);

        $cart = Cart::instance('pos_shopping')->content()->where('rowId', $data['id'])->first();

        if (! $cart) {
            return response()->json(['message' => 'That cart row is no longer in the cart.'], 404);
        }

        $cartinfo = Cart::instance('pos_shopping')->update($data['id'], [
            'options' => [
                'slug' => $cart->options->slug,
                'image' => $cart->options->image,
                'old_price' => $cart->options->old_price,
                'purchase_price' => $cart->options->purchase_price,
                'product_discount' => $data['discount'],
            ],
        ]);

        return response()->json($cartinfo);
    }

    public function cart_shipping(Request $request){
        $data = $request->validate(['id' => 'required|integer']);

        $shipping = ShippingCharge::where(['status' => 1, 'id' => $data['id']])->value('amount');

        if ($shipping === null) {
            return response()->json(['message' => 'That shipping charge is not available.'], 404);
        }

        Session::put('pos_shipping', $shipping);

        return response()->json($shipping);
    }

    public function cart_clear(Request $request){
        Cart::instance('pos_shopping')->destroy();
        Session::forget('pos_shipping');
        Session::forget('pos_manual_discount');
        Session::forget('pos_coupon_discount');
        Session::forget('pos_coupon_code');
        Session::forget('product_discount');
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cart cleared successfully',
            'count' => 0
        ]);
    }
    public function order_edit($invoice_id){
        $products = Product::select('id','name','new_price','product_code','slug')->where(['status'=>1])->get();
        $shippingcharge = ShippingCharge::where('status',1)->get();
        $orderstatus = OrderStatus::select('id', 'name', 'slug')->get();
        $order = Order::where('invoice_id',$invoice_id)->first();
        $cartinfo  = Cart::instance('pos_shopping')->destroy();
        $shippinginfo  = Shipping::where('order_id',$order->id)->first();
        Session::put('product_discount',$order->discount);
        Session::put('pos_shipping',$order->shipping_charge);
        $orderdetails = OrderDetails::where('order_id',$order->id)->get();
        
  
        foreach($orderdetails as $ordetails){
             $product = $products->where('id', $ordetails->product_id)->first();
         if ($product) {

        $cartinfo = Cart::instance('pos_shopping')->add([
            'id' => $product->id, 
            'name' => $product->name, 
            'qty' => $ordetails->qty,
            'price' => $ordetails->sale_price,
            
            'options' => [
                'image' => $ordetails->image->image,
                'purchase_price' => $ordetails->purchase_price, 
                'product_discount' => $ordetails->product_discount, 
                'details_id' => $ordetails->id, 
                'product_code' => $product->product_code,
                 'slug' => $product->slug,
            ],
        ]);
    }
        }
        $cartinfo  = Cart::instance('pos_shopping')->content();
        return view('backEnd.order.edit',compact('products','cartinfo','shippingcharge','shippinginfo','order','orderstatus'));
    }
    
    public function order_update(Request $request){
        $this->validate($request,[
            'name'=>'required',
            'phone'=>'required',
            'address'=>'required',
            'area'=>'required',
            'order_status' => 'nullable|integer|exists:order_statuses,id',
            'admin_note' => 'nullable|string|max:5000',
        ]);

        if(Cart::instance('pos_shopping')->count() <= 0) {
            Toastr::error('Your shopping empty', 'Failed!');
            return redirect()->back();
        }

        try {
            $subtotal = Cart::instance('pos_shopping')->subtotal();
            $subtotal = str_replace(',','',$subtotal);
            $subtotal = str_replace('.00', '',$subtotal);
            $discount = $this->posTotalDiscount();
            $shippingfee  = ShippingCharge::find($request->area);

            $exits_customer = Customer::where('phone',$request->phone)->select('phone','id')->first();
            if($exits_customer){
                $customer_id = $exits_customer->id;
            }else{
                $password = rand(111111,999999);
                $store              = new Customer();
                $store->name        = $request->name;
                $store->slug        = $request->name;
                $store->phone       = $request->phone;
                $store->password    = bcrypt($password);
                $store->verify      = 1;
                $store->status      = 'active';
                $store->save();
                $customer_id = $store->id;
            }
     
             // order data save
            $order                   =  Order::where('id',$request->order_id)->first();
            $previousStatusId         =  $order ? (int) $order->order_status : null;
            $order->amount           = ($subtotal + $shippingfee->amount) - $discount;
            $order->discount         = $discount ? $discount : 0;
            $order->shipping_charge  = $shippingfee->amount;
            $order->customer_id      =  $customer_id;
            if ($request->filled('order_status')) {
                $order->order_status = (int) $request->order_status;
            }
            /*
             * The admin note is a real column (2026_03_03_124223) and the edit
             * screen now carries a field for it. Guard on has() so any other
             * caller that posts the old payload does not wipe the note, and keep
             * the previous text for the audit trail.
             */
            $noteBefore = (string) ($order->admin_note ?? '');
            if ($request->has('admin_note')) {
                $order->admin_note = $request->admin_note;
            }
            $order->save();
            if ($request->has('admin_note')) {
                app(\App\Services\AuditLogger::class)->orderNote($order, $noteBefore, (string) $order->admin_note);
            }

            // Admin Approve Trigger: Pending -> On The Way (Edit Order screen)
            if ($request->filled('order_status')) {
                $newStatusRow = OrderStatus::find((int) $order->order_status);
                if ($newStatusRow && (string) $newStatusRow->slug === 'on-the-way' && (int) $previousStatusId !== (int) $order->order_status) {
                    DB::transaction(function () use ($order) {
                        $orders_details = OrderDetails::select('id','order_id','product_id','variant_id','qty','product_size')
                            ->where('order_id', $order->id)
                            ->get();

                        foreach ($orders_details as $order_details) {
                            $qty = (int) ($order_details->qty ?? 0);
                            if ($qty <= 0) {
                                continue;
                            }

                            $product = Product::select('id','product_code','name')->find($order_details->product_id);
                            if (!$product) {
                                continue;
                            }

                            $variantId = null;

                            if (!empty($order_details->variant_id)) {
                                $candidateId = (int) $order_details->variant_id;
                                $exists = DB::table('product_variants')
                                    ->where('id', $candidateId)
                                    ->where('product_id', $product->id)
                                    ->exists();
                                if ($exists) {
                                    $variantId = $candidateId;
                                }
                            }

                            $sizeMarker = (string) ($order_details->product_size ?? '');
                            if (!$variantId && $sizeMarker !== '' && preg_match('/\bvariant_id\s*:\s*(\d+)\b/i', $sizeMarker, $m)) {
                                $candidateId = (int) $m[1];
                                $exists = DB::table('product_variants')
                                    ->where('id', $candidateId)
                                    ->where('product_id', $product->id)
                                    ->exists();
                                if ($exists) {
                                    $variantId = $candidateId;
                                }
                            }

                            if (!$variantId) {
                                $defaultSku = ($product->product_code ? $product->product_code : ('P'.$product->id)) . '-DEFAULT';
                                $variantId = DB::table('product_variants')
                                    ->where('sku', $defaultSku)
                                    ->value('id');

                                if (!$variantId) {
                                    $variantId = DB::table('product_variants')->insertGetId([
                                        'product_id' => $product->id,
                                        'sku' => $defaultSku,
                                        'title' => 'Default',
                                        'price_override' => null,
                                        'weight_gram' => null,
                                        'status' => 1,
                                        'attributes_json' => null,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }

                            $stockRowExists = DB::table('variant_stocks')
                                ->where('variant_id', $variantId)
                                ->exists();
                            if (!$stockRowExists) {
                                $initial = 0;
                                DB::table('variant_stocks')->insert([
                                    'variant_id' => $variantId,
                                    'on_hand' => $initial,
                                    'reserved' => 0,
                                    'available' => $initial,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }

                            $alreadyApplied = DB::table('inventory_movements')
                                ->where('variant_id', $variantId)
                                ->whereIn('type', ['ORDER_PLACED_DEDUCT', 'DELIVERED_DEDUCT', 'ON_THE_WAY_DEDUCT'])
                                ->where('ref_type', 'order')
                                ->where('ref_id', $order->id)
                                ->exists();
                            if ($alreadyApplied) {
                                continue;
                            }

                            $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->lockForUpdate()->first();
                            $available = (int) ($stock->available ?? 0);
                            if ($available < $qty) {
                                continue;
                            }

                            InventoryMovement::record([
                                'variant_id' => $variantId,
                                'type' => 'ON_THE_WAY_DEDUCT',
                                'qty_delta' => -1 * $qty,
                                'ref_type' => 'order',
                                'ref_id' => $order->id,
                                'created_by' => auth()->id(),
                                'note' => 'On the way approve stock deduct',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            DB::table('variant_stocks')
                                ->where('variant_id', $variantId)
                                ->update([
                                    'on_hand' => DB::raw('on_hand - ' . (int) $qty),
                                    'available' => DB::raw('available - ' . (int) $qty),
                                    'updated_at' => now(),
                                ]);

                        }

                        if (Schema::hasTable('inventory_reservations')) {
                            DB::table('inventory_reservations')->where('order_id', (int) $order->id)->delete();
                        }
                    });
                }
            }


            // shipping data save
            $shipping              =   Shipping::where('order_id',$request->order_id)->first();
            $shipping->order_id    =   $order->id;
            $shipping->customer_id =   $customer_id;
            $shipping->name        =   $request->name;
            $shipping->phone       =   $request->phone;
            $shipping->address     =   $request->address;
            $shipping->area        =   $shippingfee->name;
            $shipping->save();

            // payment data save
            $payment                 =  Payment::where('order_id',$request->order_id)->first();
            $payment->order_id       = $order->id;
            $payment->customer_id    = $customer_id;
            $payment->payment_method = 'Cash On Delivery';
            $payment->amount         = $order->amount;
            $payment->payment_status = 'pending';
            $payment->save();

           // order details data save
            foreach(Cart::instance('pos_shopping')->content() as $cart){
                $exits = OrderDetails::where('id',$cart->options->details_id)->first();
                if($exits){
                    $order_details                   =   OrderDetails::find($exits->id);
                    $order_details->product_discount =   $cart->options->product_discount;
                    $order_details->sale_price       =   $cart->price;
                    $order_details->qty              =   $cart->qty;
                    $order_details->save();
                }else{
                    $order_details                   =   new OrderDetails();
                    $order_details->order_id         =   $order->id;
                    $order_details->product_id       =   $cart->id;
                    $order_details->product_name     =   $cart->name;
                    $order_details->purchase_price   =   $cart->options->purchase_price;
                    $order_details->product_discount =   $cart->options->product_discount;
                    $order_details->sale_price       =   $cart->price;
                    $order_details->qty              =   $cart->qty;
                    $order_details->save();
                }
                
            }
            Cart::instance('pos_shopping')->destroy();
            Session::forget('pos_shipping');
            Session::forget('pos_manual_discount');
            Session::forget('pos_coupon_discount');
            Session::forget('pos_coupon_code');
            Session::forget('product_discount');
            Toastr::success('Thanks, Your order updated successfully', 'Success!');
            return redirect()->route('admin.order.edit', ['invoice_id' => $order->invoice_id]);
        } catch (\Exception $e) {
            Toastr::error('Order update failed: ' . $e->getMessage(), 'Error!');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Auto-send order to Pathao courier without popup
     * Automatically detects location from address and uses first available store
     */
    public function order_pathao_auto(Request $request)
    {
        $orders_id = (array) $request->order_ids;
        $results = [];

        foreach ($orders_id as $order_id) {
            $order = Order::with('shipping')->find($order_id);
            if (!$order) {
                $results[] = ['order_id' => $order_id, 'status' => 'failed', 'message' => 'Order not found'];
                continue;
            }

            // Check if already sent to courier
            $existingShipment = CourierShipment::where('order_id', $order->id)
                ->where('provider', 'pathao')
                ->first();
            if ($existingShipment && $existingShipment->consignment_id) {
                $results[] = ['order_id' => $order_id, 'status' => 'skipped', 'message' => 'Already sent to Pathao'];
                continue;
            }

            /*
             * One sender for every Pathao send.
             *
             * This block used to do four things inline: fetch a token with
             * Http::post() and NO timeout, fetch the store list, take
             * `$stores[0]` as the pickup point, then POST a payload that always
             * carried `recipient_area => null` (the documentation says to omit
             * optional location fields, not send null). All of that now lives
             * in PathaoOrderSender, which also records the consignment.
             */
            $sent = app(PathaoOrderSender::class)->send($order);

            if ($sent['ok']) {
                $consignmentId = $sent['consignment_id'];

                // Auto change order status to "On The Way"
                $onTheWayStatus = OrderStatus::where('slug', 'on-the-way')->orWhere('name', 'like', '%Way%')->first();
                if ($onTheWayStatus) {
                    $order->order_status = $onTheWayStatus->id;
                    $order->save();

                    // Deduct stock at admin approve (On The Way)
                    DB::transaction(function () use ($order) {
                        $orders_details = OrderDetails::select('id','order_id','product_id','variant_id','qty','product_size')
                            ->where('order_id', $order->id)
                            ->get();

                        foreach ($orders_details as $order_details) {
                            $qty = (int) ($order_details->qty ?? 0);
                            if ($qty <= 0) {
                                continue;
                            }

                            $product = Product::select('id','product_code','name')->find($order_details->product_id);
                            if (!$product) {
                                continue;
                            }

                            $variantId = null;

                            if (!empty($order_details->variant_id)) {
                                $candidateId = (int) $order_details->variant_id;
                                $exists = DB::table('product_variants')
                                    ->where('id', $candidateId)
                                    ->where('product_id', $product->id)
                                    ->exists();
                                if ($exists) {
                                    $variantId = $candidateId;
                                }
                            }

                            $sizeMarker = (string) ($order_details->product_size ?? '');
                            if (!$variantId && $sizeMarker !== '' && preg_match('/\bvariant_id\s*:\s*(\d+)\b/i', $sizeMarker, $m)) {
                                $candidateId = (int) $m[1];
                                $exists = DB::table('product_variants')
                                    ->where('id', $candidateId)
                                    ->where('product_id', $product->id)
                                    ->exists();
                                if ($exists) {
                                    $variantId = $candidateId;
                                }
                            }

                            if (!$variantId) {
                                $defaultSku = ($product->product_code ? $product->product_code : ('P'.$product->id)) . '-DEFAULT';
                                $variantId = DB::table('product_variants')
                                    ->where('sku', $defaultSku)
                                    ->value('id');

                                if (!$variantId) {
                                    $variantId = DB::table('product_variants')->insertGetId([
                                        'product_id' => $product->id,
                                        'sku' => $defaultSku,
                                        'title' => 'Default',
                                        'price_override' => null,
                                        'weight_gram' => null,
                                        'status' => 1,
                                        'attributes_json' => null,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }

                            $stockRowExists = DB::table('variant_stocks')
                                ->where('variant_id', $variantId)
                                ->exists();
                            if (!$stockRowExists) {
                                $initial = 0;
                                DB::table('variant_stocks')->insert([
                                    'variant_id' => $variantId,
                                    'on_hand' => $initial,
                                    'reserved' => 0,
                                    'available' => $initial,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }

                            $alreadyApplied = DB::table('inventory_movements')
                                ->where('variant_id', $variantId)
                                ->whereIn('type', ['ORDER_PLACED_DEDUCT', 'DELIVERED_DEDUCT', 'ON_THE_WAY_DEDUCT'])
                                ->where('ref_type', 'order')
                                ->where('ref_id', $order->id)
                                ->exists();
                            if ($alreadyApplied) {
                                continue;
                            }

                            $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->lockForUpdate()->first();
                            $available = (int) ($stock->available ?? 0);
                            if ($available < $qty) {
                                continue;
                            }

                            InventoryMovement::record([
                                'variant_id' => $variantId,
                                'type' => 'ON_THE_WAY_DEDUCT',
                                'qty_delta' => -1 * $qty,
                                'ref_type' => 'order',
                                'ref_id' => $order->id,
                                'created_by' => auth()->id(),
                                'note' => 'On the way approve stock deduct',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            DB::table('variant_stocks')
                                ->where('variant_id', $variantId)
                                ->update([
                                    'on_hand' => DB::raw('on_hand - ' . (int) $qty),
                                    'available' => DB::raw('available - ' . (int) $qty),
                                    'updated_at' => now(),
                                ]);

                        }

                        if (Schema::hasTable('inventory_reservations')) {
                            DB::table('inventory_reservations')->where('order_id', (int) $order->id)->delete();
                        }
                    });
                }

                $results[] = [
                    'order_id' => $order_id,
                    'status' => 'success',
                    'message' => 'Order sent to Pathao',
                    'consignment_id' => $consignmentId,
                ];
            } else {
                $results[] = [
                    'order_id' => $order_id,
                    'status' => 'failed',
                    'message' => $sent['message'],
                ];
            }
        }

        return response()->json([
            'status' => 'completed',
            'results' => $results,
        ]);
    }


}
