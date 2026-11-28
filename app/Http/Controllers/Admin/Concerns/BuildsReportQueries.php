<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\OrderDetails;
use App\Models\OrderStatus;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The single query source behind the Product Report and the Sales Report.
 *
 * OrderController (the pages) and ReportExportController (the downloads) both
 * use this trait, so the rows a user sees and the rows inside the file cannot
 * drift apart. Filters follow docs/report-metrics.md 1.3: a date range is
 * ">= start 00:00" and "< next day 00:00", and each bound stands on its own.
 *
 * Methods are prefixed `report` so they cannot collide with the controller's
 * own helpers.
 */
trait BuildsReportQueries
{
    /**
     * Filtered products for the Product Report. No select list, so callers can
     * clone it for inventory totals, sales totals or the paginated table.
     */
    protected function reportStockBase(Request $request)
    {
        $products = Product::query()->where('products.status', 1);

        if ($request->keyword) {
            $products->where('products.name', 'LIKE', '%' . $request->keyword . '%');
        }
        if ($request->category_id) {
            $products->where('products.category_id', $request->category_id);
        }
        if ($request->filled('start_date')) {
            $products->where('products.updated_at', '>=', Carbon::parse($request->start_date)->startOfDay());
        }
        if ($request->filled('end_date')) {
            $products->where('products.updated_at', '<', Carbon::parse($request->end_date)->addDay()->startOfDay());
        }

        return $products;
    }

    /**
     * Inventory totals over the whole filter, not the current page.
     */
    protected function reportStockTotals(Request $request): array
    {
        $totals = $this->reportStockBase($request)
            ->join('product_variants as v', function ($join) {
                $join->on('v.product_id', '=', 'products.id')->where('v.status', 1);
            })
            ->join('variant_stocks as s', 's.variant_id', '=', 'v.id')
            ->selectRaw('COALESCE(SUM(s.on_hand), 0) as units')
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(v.price_override, 0), products.new_price) * s.on_hand), 0) as stock_value')
            ->selectRaw('COALESCE(SUM(COALESCE(NULLIF(v.purchase_price, 0), products.purchase_price) * s.on_hand), 0) as stock_cost')
            ->first();

        return [
            'units' => (int) ($totals->units ?? 0),
            'stock_value' => (float) ($totals->stock_value ?? 0),
            'stock_cost' => (float) ($totals->stock_cost ?? 0),
        ];
    }

    /**
     * Lifetime delivered sales per product (docs/report-metrics.md,
     * "Product Report sales basis"). Grouped on order_details.product_id, not
     * on the historical product_name.
     */
    protected function reportStockSalesSub()
    {
        return DB::table('order_details as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->whereIn('o.order_status', ['delivered', '4'])
            ->groupBy('d.product_id')
            ->selectRaw('d.product_id, COALESCE(SUM(d.qty),0) AS units_sold, COALESCE(SUM(d.sale_price * d.qty),0) AS revenue, COALESCE(SUM(d.purchase_price * d.qty),0) AS cogs');
    }

    /**
     * Sales totals over the whole filter. The join is 1:1 per product, so this
     * is safe to sum after the inventory totals have been computed.
     */
    protected function reportStockSalesTotals(Request $request): array
    {
        $totals = $this->reportStockBase($request)
            ->leftJoinSub($this->reportStockSalesSub(), 'sales', 'sales.product_id', '=', 'products.id')
            ->selectRaw('COALESCE(SUM(sales.units_sold),0) AS units_sold, COALESCE(SUM(sales.revenue),0) AS revenue, COALESCE(SUM(sales.cogs),0) AS cogs')
            ->first();

        return [
            'units_sold' => (int) ($totals->units_sold ?? 0),
            'revenue' => (float) ($totals->revenue ?? 0),
            'cogs' => (float) ($totals->cogs ?? 0),
        ];
    }

    /**
     * The paginated/exportable Product Report rows: product columns plus the
     * variant stock projection and the lifetime delivered sales aliases.
     */
    protected function reportStockRows(Request $request)
    {
        return $this->reportStockBase($request)
            ->leftJoinSub($this->reportStockSalesSub(), 'sales', 'sales.product_id', '=', 'products.id')
            ->select('products.id', 'products.name', 'products.new_price')
            ->selectRaw('COALESCE(sales.units_sold, 0) AS units_sold')
            ->selectRaw('COALESCE(sales.revenue, 0) AS revenue')
            ->selectRaw('COALESCE(sales.cogs, 0) AS cogs')
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
            }, 'variant_stock_value');
    }

    /**
     * Cast the raw aliases and derive the display values the blade and the
     * export both print.
     */
    protected function castReportStockRows(iterable $products): void
    {
        foreach ($products as $product) {
            $units = (int) ($product->variant_stock ?? 0);
            $value = (float) ($product->variant_stock_value ?? 0);

            $product->setAttribute('stock_on_hand', $units);
            $product->setAttribute('stock_value', $value);
            $product->setAttribute('unit_price', $units > 0 ? $value / $units : (float) ($product->new_price ?? 0));
            $product->setAttribute('units_sold', (int) ($product->units_sold ?? 0));
            $product->setAttribute('revenue', (float) ($product->revenue ?? 0));
            $product->setAttribute('cogs', (float) ($product->cogs ?? 0));
        }
    }

    /**
     * Filtered delivered lines for the Sales Report line table and export.
     */
    protected function reportOrderLineQuery(Request $request)
    {
        $delivered = $this->reportDeliveredStatus();

        $orders = OrderDetails::with('shipping', 'order')->whereHas('order', function ($query) use ($delivered) {
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
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $orders = $orders->where(function ($q) use ($request) {
                if ($request->filled('start_date')) {
                    $q->where('order_details.updated_at', '>=', Carbon::parse($request->start_date)->startOfDay());
                }
                if ($request->filled('end_date')) {
                    $q->where('order_details.updated_at', '<', Carbon::parse($request->end_date)->addDay()->startOfDay());
                }
            });
        }

        return $orders;
    }

    /**
     * Line-grain totals over the whole filter.
     */
    protected function reportOrderLineTotals($lineQuery): array
    {
        return [
            'purchase' => (float) (clone $lineQuery)->sum(DB::raw('purchase_price * qty')),
            'items' => (int) (clone $lineQuery)->sum('qty'),
            'sales' => (float) (clone $lineQuery)->sum(DB::raw('sale_price * qty')),
        ];
    }

    /**
     * Order-grain summary cards: distinct filtered orders, then orders.discount
     * / shipping_charge / amount over that set.
     */
    protected function reportOrderSummary(array $orderIds): array
    {
        $row = DB::table('orders')->whereIn('id', $orderIds)
            ->selectRaw('COUNT(*) AS order_count, COALESCE(SUM(discount),0) AS order_discount, COALESCE(SUM(shipping_charge),0) AS shipping, COALESCE(SUM(amount),0) AS order_total')
            ->first();

        return [
            'orders' => (int) ($row->order_count ?? 0),
            'order_discount' => (float) ($row->order_discount ?? 0),
            'shipping' => (float) ($row->shipping ?? 0),
            'order_total' => (float) ($row->order_total ?? 0),
        ];
    }

    /**
     * One row per filtered delivered order for the order-level view/export.
     * Money comes straight from `orders`; line_count/qty/sales come from the
     * same filtered line query the line table uses.
     */
    protected function reportOrderGrainRows($lineQuery, array $orderIds)
    {
        if (empty($orderIds)) {
            return collect();
        }

        $rows = DB::table('orders')->whereIn('id', $orderIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $statusLabels = [];
        foreach (OrderStatus::all() as $status) {
            $statusLabels[(string) $status->id] = $status->name;
            $statusLabels[$status->slug] = $status->name;
        }

        $lineStats = (clone $lineQuery)->toBase()
            ->selectRaw('order_id, COUNT(*) AS line_count, COALESCE(SUM(qty),0) AS qty, COALESCE(SUM(sale_price * qty),0) AS sales')
            ->groupBy('order_details.order_id')
            ->get()
            ->keyBy('order_id');

        $shippings = DB::table('shippings')->whereIn('order_id', $orderIds)
            ->orderByDesc('id')->get()->groupBy('order_id')->map(fn ($g) => $g->first());
        $payments = DB::table('payments')->whereIn('order_id', $orderIds)
            ->orderByDesc('id')->get()->groupBy('order_id')->map(fn ($g) => $g->first());
        $couriers = DB::table('courier_shipments')->whereIn('order_id', $orderIds)
            ->orderByDesc('id')->get()->groupBy('order_id')->map(fn ($g) => $g->first());

        return $rows->map(function ($row) use ($statusLabels, $lineStats, $shippings, $payments, $couriers) {
            $lines = $lineStats->get($row->id);
            $courier = $couriers->get($row->id);
            $courierLabel = $courier
                ? trim(($courier->provider ?? '') . ' ' . ($courier->consignment_id ?? ''))
                : '';

            return (object) [
                'row' => $row,
                'shipping' => $shippings->get($row->id),
                'line_count' => (int) ($lines->line_count ?? 0),
                'qty' => (int) ($lines->qty ?? 0),
                'sales' => (float) ($lines->sales ?? 0),
                'status' => $statusLabels[(string) $row->order_status] ?? (string) $row->order_status,
                'payment' => $payments->get($row->id)->payment_method ?? '-',
                'courier' => $courierLabel === '' ? '-' : $courierLabel,
            ];
        });
    }

    private function reportDeliveredStatus(): ?OrderStatus
    {
        $candidates = ['delivered', 'completed', 'complete'];

        return OrderStatus::whereIn('slug', $candidates)
            ->select('id', 'name', 'slug')
            ->first();
    }
}
