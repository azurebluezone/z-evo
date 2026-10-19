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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Brian2694\Toastr\Facades\Toastr;
use Session;
use Cart;
use Mail;

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

    private function pathaoUrl(string $base, string $path): string
    {
        // Sandbox URL (courier-api-sandbox.pathao.com) does not need /aladdin prefix
        // Production URL (api-hermes.pathao.com) requires /aladdin
        $path = ltrim($path, '/');
        if (str_contains($base, 'sandbox')) {
            return $this->joinUrl($base, $path);
        }
        if (str_starts_with($path, 'aladdin/')) {
            return $this->joinUrl($base, $path);
        }
        return $this->joinUrl($base, 'aladdin/' . $path);
    }

    /**
     * Fetch Pathao city + store reference data for the orders screen.
     *
     * Guard rails (added after a 39s page load was traced to these two calls):
     *  - hard response timeout, so an unreachable courier API fails fast
     *  - circuit breaker: after a failure the calls are skipped for a while, so the
     *    orders screen never pays the timeout again on every single request
     *  - success cached 6h; a failure opens the breaker for 5 minutes
     *  - never throws: an unreachable courier degrades to empty reference data
     *
     * @param  \App\Models\Courierapi|null  $pathaoInfo
     * @return array{cities: array, stores: array}
     */
    private function pathaoReferenceData($pathaoInfo): array
    {
        $fallback = ['cities' => [], 'stores' => []];

        if (!$pathaoInfo) {
            return $fallback;
        }

        $breakerKey = 'admin.courier.pathao.breaker';

        // Circuit open: the courier API recently failed, so do not call it at all.
        if (Cache::has($breakerKey)) {
            return $fallback;
        }

        $baseUrl = $pathaoInfo->url;
        $token = $pathaoInfo->token;
        $timeout = 2;

        try {
            $data = Cache::remember('admin.courier.pathao.reference', now()->addHours(6), function () use ($baseUrl, $token, $timeout) {
                $cities = Http::timeout($timeout)
                    ->get($this->pathaoUrl($baseUrl, 'api/v1/city-list'))
                    ->json();

                $stores = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])->timeout($timeout)
                    ->get($this->pathaoUrl($baseUrl, 'api/v1/stores'))
                    ->json();

                // A Cloudflare/API error body is non-empty but unusable downstream,
                // so normalise anything unexpected to an empty list.
                return [
                    'cities' => is_array($cities) ? $cities : [],
                    'stores' => is_array($stores) ? $stores : [],
                ];
            });

            // A structurally broken payload is treated like a transport failure.
            if (empty($data['stores'])) {
                throw new \RuntimeException('Pathao returned an unusable reference payload.');
            }

            return $data;
        } catch (\Throwable $e) {
            Cache::put($breakerKey, true, now()->addMinutes(5));
            Log::warning('Pathao reference data unavailable, circuit open for 5 minutes.', [
                'message' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }

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
    private const ORDER_TABLE_COLUMNS = 8;

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
        $query = Order::query()->with('shipping', 'status', 'courierShipments');

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
            5 => 'total',
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
            $show_data = Order::latest()->with('shipping','status');
            if($request->status){
                $show_data = $show_data->where('order_status', (int) $request->status);
            }
            if($request->keyword){
                $show_data = $show_data->where(function ($query) use ($request) {
                    $query->orWhere('invoice_id', 'LIKE', '%' . $request->keyword . '%')
                          ->orWhereHas('shipping', function ($subQuery) use ($request) {
                              $subQuery->where('phone', $request->keyword);
                          });
                });
            }
           $show_data = $show_data->paginate(20);
           $this->attachRiskFallbackToOrders($show_data);
        }else{
            $resolved = $this->resolveOrderStatusBySlug($slug);
            if ($resolved) {
                $order_status = OrderStatus::where('id', $resolved->id)->withCount('orders')->first();
                $show_data = Order::where(['order_status'=>$order_status->id])->latest()->with('shipping','status');
                if($request->status){
                    $show_data = $show_data->where('order_status', (int) $request->status);
                }
                if($request->keyword){
                    $show_data = $show_data->where(function ($query) use ($request) {
                        $query->orWhere('invoice_id', 'LIKE', '%' . $request->keyword . '%')
                              ->orWhereHas('shipping', function ($subQuery) use ($request) {
                                  $subQuery->where('phone', $request->keyword);
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

        $pathaoQuery = Courierapi::query()->where('type', 'pathao');
        if ($courierEnabledColumnExists) {
            $pathaoQuery->where('status', 1);
        }
        $pathao_info = $pathaoQuery->select('id', 'type', 'url', 'token')->when($courierEnabledColumnExists, function ($q) {
            return $q->addSelect('status');
        })->first();
        // Pathao reference data (city list + store list).
        // These are third-party calls: they are cached and hard-timeout bounded so a
        // slow/unreachable courier API can never block the orders screen again.
        $pathaoReference = $this->pathaoReferenceData($pathao_info);
        $pathaocities = $pathaoReference['cities'];
        $pathaostore = $pathaoReference['stores'];
        return view('backEnd.order.index',compact('show_data','order_status','orderstatus','order_kpis','users', 'steadfast','pathaostore','pathaocities'));
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

        foreach ($orders_id as $order_id) {
            $order = Order::with('shipping')->find($order_id);
            if (!$order) {
                return response()->json(['status' => 'failed', 'message' => 'Order not found']);
            }
            $order_count = OrderDetails::select('order_id')->where('order_id', $order->id)->count();
            // return $request->all();
            // pathao
            $courierEnabledColumnExists = Schema::hasColumn('courier_apis', 'status');
            $pathaoQuery = Courierapi::query()->where('type', 'pathao');
            if ($courierEnabledColumnExists) {
                $pathaoQuery->where('status', 1);
            }
            $pathao_info = $pathaoQuery->select('id', 'type', 'url', 'token')->when($courierEnabledColumnExists, function ($q) {
                return $q->addSelect('status');
            })->first();
            if ($pathao_info) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $pathao_info->token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post($this->pathaoUrl($pathao_info->url, 'api/v1/orders'), [
                    'store_id' => $request->pathaostore,
                    'merchant_order_id' => $order->invoice_id,
                    'sender_name' => 'Test',
                    'sender_phone' => $order->shipping ? $order->shipping->phone : '',
                    'recipient_name' => $order->shipping ? $order->shipping->name : '',
                    'recipient_phone' => $order->shipping ? $order->shipping->phone : '',
                    'recipient_address' => $order->shipping ? $order->shipping->address : '',
                    'recipient_city' => $request->pathaocity,
                    'recipient_zone' => $request->pathaozone,
                    'recipient_area' => $request->pathaoarea,
                    'delivery_type' => 48,
                    'item_type' => 2,
                    'special_instruction' => 'Special note- product must be check after delivery',
                    'item_quantity' => 1,
                    'item_weight' => 0.5,
                    'amount_to_collect' => round($order->amount),
                    'item_description' => 'Special note- product must be check after delivery',
                ]);
            }
            if (!isset($response)) {
                return response()->json(['status' => 'failed', 'message' => 'Pathao is not configured']);
            }

            if ($response->status() === 200) {
                $consignmentId = data_get($response->json(), 'data.consignment_id');

                try {
                    CourierShipment::query()->updateOrCreate(
                        [
                            'order_id' => $order->id,
                            'provider' => 'pathao',
                        ],
                        [
                            'merchant_order_id' => (string) $order->invoice_id,
                            'consignment_id' => $consignmentId ? (string) $consignmentId : null,
                            'tracking_code' => null,
                            'current_status' => (string) data_get($response->json(), 'data.order_status'),
                            'raw_payload' => $response->json(),
                            'last_synced_at' => now(),
                        ]
                    );
                } catch (\Throwable $e) {
                }

                Toastr::success($consignmentId ?: 'Success', 'Courier Tracking ID');
                return response()->json(['status' => 'success', 'message' => $consignmentId ?: 'Success']);
            }

            $message = data_get($response->json(), 'message', 'Courier Order Failed');
            Toastr::error($message, 'Courier Order Faild');
            return response()->json(['status' => 'failed', 'message' => $message]);
            return redirect()->back();


        }
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

        return view('backEnd.order.process', compact('order', 'data', 'shippingcharge', 'orderstatus', 'previousCourierShipments', 'hasCourierHistory'));
    }
    
    public function order_process(Request $request)
    {

        $statusRow = OrderStatus::find($request->status);
        $link = $statusRow ? $statusRow->slug : '';
        $order = Order::find($request->id);
        $courier = $order->order_status;
        $order->order_status = $request->status;
        $order->admin_note = $request->admin_note;
        $order->save();

        $shipping_update = Shipping::where('order_id', $order->id)->first();
        $shippingfee = ShippingCharge::find($request->area);
        if ($shippingfee->name != $request->area) {
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

        $shipping_update->name = $request->name;
        $shipping_update->phone = $request->phone;
        $shipping_update->address = $request->address;
        $shipping_update->area = $shippingfee->name;
        $shipping_update->save();

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

                    $product = Product::select('id','stock','product_code','name')->find($order_details->product_id);
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
                        $initial = (int) ($product->stock ?? 0);
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

                    DB::table('inventory_movements')->insert([
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

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'stock' => DB::raw('GREATEST(0, stock - ' . (int) $qty . ')'),
                        ]);
                }

                // Clear any active reservations once approved
                if (Schema::hasTable('inventory_reservations')) {
                    DB::table('inventory_reservations')->where('order_id', (int) $order->id)->delete();
                }
            });
        }

        if ($request->status == 5 && $courier != 5) {
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

                    Toastr::error('Courier', $processResult['message']);
                } else {
                    Toastr::success('Courier', $processResult['message']);
                }
            } else {
                return "ok";
            }
            Toastr::success('Success', 'Order status change successfully');
            return redirect('admin/order/' . $link);
        }
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

                        $product = Product::select('id','stock','product_code','name')->find($order_details->product_id);
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
                            $initial = (int) ($product->stock ?? 0);
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

                        DB::table('inventory_movements')->insert([
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

                        DB::table('products')
                            ->where('id', $product->id)
                            ->update([
                                'stock' => DB::raw('GREATEST(0, stock - ' . (int) $qty . ')'),
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

                        $product = Product::select('id','stock','product_code','name')->find($order_details->product_id);
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
                            $initial = (int) ($product->stock ?? 0);
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
                        DB::table('inventory_movements')->insert([
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

                        // Existing behavior (back-compat): deduct product stock too
                        $product->stock = (int) ($product->stock ?? 0) - $qty;
                        $product->save();

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
 * A thin caller now: it validates, writes the new status and hands the stock
 * side-effects to applyOrderStatusStock(), which the Kanban board also calls.
 */
public function order_status(Request $request)
    {
        $data = $request->validate([
            'order_ids'    => ['required', 'array', 'min:1'],
            'order_ids.*'  => ['integer', 'exists:orders,id'],
            'order_status' => ['required', 'integer', 'exists:order_statuses,id'],
        ]);

        $ids = array_map('intval', $data['order_ids']);

        $this->changeOrderStatus($ids, (int) $data['order_status']);

        return response()->json(['status'=>'success','message'=>'Order status change successfully']);
    }

    /**
     * The one status-change routine. The list view's dropdown and the Kanban
     * board's drag both land here.
     *
     * Public because OrderBoardController reuses it, not to be called freely:
     * it assumes the ids and the status have already been validated. The two
     * callers validate first, and each of them goes through its own
     * permission-checked route.
     *
     * @param  array<int,int>  $ids
     */
    public function changeOrderStatus(array $ids, int $statusId): void
    {
        if ($ids === []) {
            return;
        }

        Order::whereIn('id', $ids)->update(['order_status' => $statusId]);

        $this->applyOrderStatusStock($ids, $statusId);
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
                $courier = $order->order_status;
                if ((int) $courier === 5) {
                    $results[] = ['order_id' => $order_id, 'status' => 'skipped', 'message' => 'Order already delivered'];
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
    public function stock_report(Request $request){
        $products = Product::select('id', 'name','new_price','stock')
            ->where('status', 1);
        if ($request->keyword) {
            $products = $products->where('name', 'LIKE', '%' . $request->keyword . "%");
        }
        if ($request->category_id) {
            $products = $products->where('category_id', $request->category_id);
        }
        if ($request->start_date && $request->end_date) {
            $products =$products->whereBetween('updated_at', [$request->start_date,$request->end_date]);
        }
        $total_purchase = $products->sum(\DB::raw('purchase_price * stock'));
        $total_stock = $products->sum('stock');
        $total_price = $products->sum(\DB::raw('new_price * stock'));
        $products = $products->paginate(10);
        $categories = Category::where('status',1)->get();
        return view('backEnd.reports.stock',compact('products','categories','total_purchase','total_stock','total_price'));
    }
    public function order_report(Request $request){
        $users = User::where('status',1)->get();
        $orders = OrderDetails::with('shipping','order')->whereHas('order', function ($query){
                $query->where('order_status', 6);
            });
        if ($request->keyword) {
            $orders = $orders->where('name', 'LIKE', '%' . $request->keyword . "%");
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
            $order->note             = $request->note;
            $order->save();

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
            ->select('id','name','stock','new_price','old_price','purchase_price','slug')
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
            $discount += $cart->options->product_discount*$cart->qty;
        }
        Session::put('product_discount',$discount);
        return view('backEnd.order.cart_details',compact('cartinfo'));
    }
    public function cart_increment(Request $request){
        $qty = $request->qty + 1;
        $cartinfo = Cart::instance('pos_shopping')->update($request->id, $qty);
        return response()->json($cartinfo);
    }
    public function cart_decrement(Request $request){
        $qty = $request->qty - 1;
        $cartinfo = Cart::instance('pos_shopping')->update($request->id, $qty);
        return response()->json($cartinfo);
    }
    public function cart_remove(Request $request){
        $remove = Cart::instance('pos_shopping')->remove($request->id);
        $cartinfo = Cart::instance('pos_shopping')->content();
        return response()->json($cartinfo);
    }
    public function product_discount(Request $request){
        $discount = $request->discount;
        $cart = Cart::instance('pos_shopping')->content()->where('rowId', $request->id)->first();
        $cartinfo = Cart::instance('pos_shopping')->update($request->id, [
            'options' => [
                'slug' => $cart->options->slug,
                'image' => $cart->options->image,
                'old_price' => $cart->options->old_price,
                'purchase_price' => $cart->options->purchase_price,
                'product_discount' => $request->discount,
            ],
        ]);
        return response()->json($cartinfo);
    }
    public function cart_shipping(Request $request){
         $shipping = ShippingCharge::where(['status'=>1,'id'=>$request->id])->first()->amount;
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
            $order->note             = $request->note;
            $order->save();

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

                            $product = Product::select('id','stock','product_code','name')->find($order_details->product_id);
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
                                $initial = (int) ($product->stock ?? 0);
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

                            DB::table('inventory_movements')->insert([
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

                            DB::table('products')
                                ->where('id', $product->id)
                                ->update([
                                    'stock' => DB::raw('GREATEST(0, stock - ' . (int) $qty . ')'),
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

            $courierEnabledColumnExists = Schema::hasColumn('courier_apis', 'status');
            $pathaoQuery = Courierapi::query()->where('type', 'pathao');
            if ($courierEnabledColumnExists) {
                $pathaoQuery->where('status', 1);
            }
            $pathao_info = $pathaoQuery->select('id', 'type', 'url', 'token', 'username', 'password', 'client_id', 'client_secret')
                ->when($courierEnabledColumnExists, function ($q) {
                    return $q->addSelect('status');
                })->first();

            if (!$pathao_info) {
                $results[] = ['order_id' => $order_id, 'status' => 'failed', 'message' => 'Pathao is not configured'];
                continue;
            }

            // Get token if not available
            $token = $pathao_info->token;
            if (!$token && $pathao_info->client_id && $pathao_info->client_secret) {
                $tokenResponse = Http::post($this->pathaoUrl($pathao_info->url, 'api/v1/issue-token'), [
                    'client_id' => $pathao_info->client_id,
                    'client_secret' => $pathao_info->client_secret,
                    'grant_type' => 'password',
                    'username' => $pathao_info->username,
                    'password' => $pathao_info->password,
                ]);
                if ($tokenResponse->successful()) {
                    $token = $tokenResponse->json('access_token');
                    $pathao_info->update(['token' => $token]);
                }
            }

            if (!$token) {
                $results[] = ['order_id' => $order_id, 'status' => 'failed', 'message' => 'Pathao authentication failed'];
                continue;
            }

            // Get stores and use first one
            $storesResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->get($this->pathaoUrl($pathao_info->url, 'api/v1/stores'));

            if (!$storesResponse->successful()) {
                $results[] = ['order_id' => $order_id, 'status' => 'failed', 'message' => 'Failed to fetch Pathao stores'];
                continue;
            }

            $stores = $storesResponse->json('data.data', []);
            if (empty($stores)) {
                $results[] = ['order_id' => $order_id, 'status' => 'failed', 'message' => 'No Pathao stores available'];
                continue;
            }

            $store = $stores[0];
            $store_id = $store['store_id'];

            // Auto-detect location from address
            $address = $order->shipping ? $order->shipping->address : '';
            $location = $this->detectPathaoLocation($address, $token, $pathao_info->url);

            if (!$location) {
                // Fallback: use store location as recipient location
                $location = [
                    'city_id' => $store['city_id'] ?? 1,
                    'zone_id' => $store['zone_id'] ?? 1,
                    'area_id' => $store['area_id'] ?? null,
                ];
            }

            // Create order
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->pathaoUrl($pathao_info->url, 'api/v1/orders'), [
                'store_id' => $store_id,
                'merchant_order_id' => $order->invoice_id,
                'recipient_name' => $order->shipping ? $order->shipping->name : '',
                'recipient_phone' => $order->shipping ? $order->shipping->phone : '',
                'recipient_address' => $order->shipping ? $order->shipping->address : '',
                'recipient_city' => $location['city_id'],
                'recipient_zone' => $location['zone_id'],
                'recipient_area' => $location['area_id'] ?? null,
                'delivery_type' => 48,
                'item_type' => 2,
                'special_instruction' => 'Special note- product must be check after delivery',
                'item_quantity' => 1,
                'item_weight' => 0.5,
                'amount_to_collect' => round($order->amount),
                'item_description' => 'Order from Adora',
            ]);

            if ($response->status() === 200) {
                $consignmentId = data_get($response->json(), 'data.consignment_id');

                // Save shipment record
                CourierShipment::query()->updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'provider' => 'pathao',
                    ],
                    [
                        'merchant_order_id' => (string) $order->invoice_id,
                        'consignment_id' => $consignmentId ? (string) $consignmentId : null,
                        'tracking_code' => null,
                        'current_status' => (string) data_get($response->json(), 'data.order_status', 'Pending'),
                        'raw_payload' => $response->json(),
                        'last_synced_at' => now(),
                    ]
                );

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

                            $product = Product::select('id','stock','product_code','name')->find($order_details->product_id);
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
                                $initial = (int) ($product->stock ?? 0);
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

                            DB::table('inventory_movements')->insert([
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

                            DB::table('products')
                                ->where('id', $product->id)
                                ->update([
                                    'stock' => DB::raw('GREATEST(0, stock - ' . (int) $qty . ')'),
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
                    'message' => data_get($response->json(), 'message', 'Pathao order creation failed'),
                ];
            }
        }

        return response()->json([
            'status' => 'completed',
            'results' => $results,
        ]);
    }

    /**
     * Detect Pathao city/zone/area from address string
     */
    private function detectPathaoLocation(string $address, string $token, string $baseUrl): ?array
    {
        // Common Bangladesh city keywords
        $cityKeywords = [
            'dhaka' => 1,
            'chittagong' => 2,
            'chattogram' => 2,
            'sylhet' => 3,
            'rajshahi' => 4,
            'khulna' => 5,
            'barishal' => 6,
            'barisal' => 6,
            'rangpur' => 7,
            'mymensingh' => 8,
        ];

        $addressLower = strtolower($address);
        $city_id = null;

        // Detect city from address
        foreach ($cityKeywords as $keyword => $id) {
            if (strpos($addressLower, $keyword) !== false) {
                $city_id = $id;
                break;
            }
        }

        // Default to Dhaka if no city found
        if (!$city_id) {
            $city_id = 1;
        }

        // Get zones for the city
        $zonesResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->get($this->pathaoUrl($baseUrl, 'api/v1/cities/' . $city_id . '/zone-list'));

        if (!$zonesResponse->successful()) {
            return ['city_id' => $city_id, 'zone_id' => 1];
        }

        $zones = $zonesResponse->json('data.data', []);
        if (empty($zones)) {
            return ['city_id' => $city_id, 'zone_id' => 1];
        }

        // Try to match zone from address
        $zone_id = $zones[0]['zone_id'];
        foreach ($zones as $zone) {
            if (strpos($addressLower, strtolower($zone['zone_name'])) !== false) {
                $zone_id = $zone['zone_id'];
                break;
            }
        }

        // Get areas for the zone
        $areasResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->get($this->pathaoUrl($baseUrl, 'api/v1/zones/' . $zone_id . '/area-list'));

        $area_id = null;
        if ($areasResponse->successful()) {
            $areas = $areasResponse->json('data.data', []);
            if (!empty($areas)) {
                // Try to match area from address
                $area_id = $areas[0]['area_id'];
                foreach ($areas as $area) {
                    if (strpos($addressLower, strtolower($area['area_name'])) !== false) {
                        $area_id = $area['area_id'];
                        break;
                    }
                }
            }
        }

        return [
            'city_id' => $city_id,
            'zone_id' => $zone_id,
            'area_id' => $area_id,
        ];
    }

}
