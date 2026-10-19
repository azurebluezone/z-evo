<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\InteractsWithDataTable;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Toastr;

class ReturnsRefundsController extends Controller
{
    use InteractsWithDataTable;
    private function resolveLegacyProductRowIdFromOrderDetail($detail): ?int
    {
        $directId = isset($detail->product_id) ? (int) $detail->product_id : null;
        if ($directId) {
            $exists = DB::table('products')->where('id', $directId)->exists();
            if ($exists) {
                return $directId;
            }
        }

        $name = (string) ($detail->product_name ?? '');
        if ($name === '') {
            return null;
        }

        // Try to extract last token as product_code (e.g., LR8604, COD12, SP8606)
        $tokens = preg_split('/\s+/', trim($name));
        $code = $tokens ? end($tokens) : '';
        $code = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $code);

        if ($code === '') {
            return null;
        }

        $id = DB::table('products')->where('product_code', $code)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveVariantIdFromOrderDetail(int $productId, ?string $productSize): ?int
    {
        $sizeMarker = (string) ($productSize ?? '');
        if ($sizeMarker !== '' && preg_match('/\bvariant_id\s*:\s*(\d+)\b/i', $sizeMarker, $m)) {
            $candidateId = (int) $m[1];
            $exists = DB::table('product_variants')
                ->where('id', $candidateId)
                ->where('product_id', $productId)
                ->exists();
            if ($exists) {
                return $candidateId;
            }
        }

        $variantId = DB::table('product_variants')
            ->where('product_id', $productId)
            ->orderBy('id')
            ->value('id');

        if ($variantId) {
            return (int) $variantId;
        }

        $productCode = DB::table('products')->where('id', $productId)->value('product_code');
        $defaultSku = ($productCode ? (string) $productCode : ('P' . $productId)) . '-DEFAULT';

        $variantId = DB::table('product_variants')->where('sku', $defaultSku)->value('id');
        if ($variantId) {
            return (int) $variantId;
        }

        return (int) DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
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

    public function returnsIndex(Request $request)
    {
        $deliveredStatus = $this->resolveOrderStatusBySlug('delivered');

        $orders = Order::with(['customer', 'shipping'])
            ->when($deliveredStatus, function ($q) use ($deliveredStatus) {
                $q->where('order_status', $deliveredStatus->id);
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        $returnRows = DB::table('order_returns')
            ->whereIn('order_id', $orders->pluck('id')->all())
            ->get()
            ->keyBy('order_id');

        return view('backEnd.returns.index', compact('orders', 'returnRows'));
    }

    public function approveReturn(Request $request, $orderId)
    {
        $request->validate([
            'reason' => 'nullable|string',
            'return_mode' => 'nullable|string|in:customer_return,rto',
            'restock_condition' => 'nullable|string|in:sellable,damaged',
            'refund_required' => 'nullable|boolean',
            'refund_amount' => 'nullable|numeric',
            'refund_note' => 'nullable|string',
        ]);

        $order = Order::with('orderdetails')->findOrFail($orderId);

        DB::transaction(function () use ($order, $request) {
            $row = DB::table('order_returns')->where('order_id', $order->id)->lockForUpdate()->first();

            $returnMode = $request->input('return_mode', 'customer_return');
            $restockCondition = $request->input('restock_condition', 'sellable');
            $refundRequired = $request->has('refund_required') ? 1 : 0;
            $refundAmount = $request->input('refund_amount');
            $refundNote = $request->input('refund_note');

            if (!$row) {
                DB::table('order_returns')->insert([
                    'order_id' => $order->id,
                    'status' => 'approved',
                    'return_mode' => $returnMode,
                    'restock_condition' => $restockCondition,
                    'restocked' => 0,
                    'refund_required' => $refundRequired,
                    'refunded' => 0,
                    'refund_amount' => null,
                    'refund_note' => $refundNote,
                    'reason' => $request->input('reason'),
                    'requested_by' => auth()->id(),
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $row = DB::table('order_returns')->where('order_id', $order->id)->lockForUpdate()->first();
            }

            if ((int) ($row->restocked ?? 0) === 1) {
                return;
            }

            // Keep return meta in sync on re-approve attempts (idempotent restock)
            DB::table('order_returns')->where('order_id', $order->id)->update([
                'return_mode' => $returnMode,
                'restock_condition' => $restockCondition,
                'refund_required' => $refundRequired,
                'refund_amount' => $refundAmount,
                'refund_note' => $refundNote,
                'reason' => $request->input('reason'),
                'updated_at' => now(),
            ]);

            foreach ($order->orderdetails as $detail) {
                $qty = (int) ($detail->qty ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $variantId = null;
                if (!empty($detail->variant_id)) {
                    $candidateId = (int) $detail->variant_id;
                    $exists = DB::table('product_variants')
                        ->where('id', $candidateId)
                        ->where('product_id', (int) $detail->product_id)
                        ->exists();
                    if ($exists) {
                        $variantId = $candidateId;
                    }
                }
                if (!$variantId) {
                    $variantId = $this->resolveVariantIdFromOrderDetail((int) $detail->product_id, $detail->product_size ?? null);
                }
                if (!$variantId) {
                    continue;
                }

                $bucket = $restockCondition === 'damaged' ? 'damaged' : 'sellable';

                if ($bucket === 'sellable') {
                    // Legacy stock (back-compat)
                    $legacyProductId = $this->resolveLegacyProductRowIdFromOrderDetail($detail);
                    if ($legacyProductId) {
                        DB::table('products')->where('id', $legacyProductId)->increment('stock', $qty);
                    }
                }

                DB::table('inventory_movements')->insert([
                    'variant_id' => $variantId,
                    'type' => $bucket === 'damaged' ? 'RETURN_DAMAGED' : 'RETURN',
                    'bucket' => $bucket,
                    'qty_delta' => $qty,
                    'ref_type' => 'order_return',
                    'ref_id' => $order->id,
                    'created_by' => auth()->id(),
                    'note' => $bucket === 'damaged' ? 'Return received (damaged)' : 'Return restock (sellable)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->lockForUpdate()->first();
                $onHand = (int) ($stock->on_hand ?? 0);
                $reserved = (int) ($stock->reserved ?? 0);
                $damaged = (int) ($stock->damaged ?? 0);

                $newOnHand = $bucket === 'sellable' ? ($onHand + $qty) : $onHand;
                $newDamaged = $bucket === 'damaged' ? ($damaged + $qty) : $damaged;
                $available = max(0, $newOnHand - $reserved);

                if ($stock) {
                    DB::table('variant_stocks')->where('variant_id', $variantId)->update([
                        'on_hand' => $newOnHand,
                        'available' => $available,
                        'damaged' => $newDamaged,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('variant_stocks')->insert([
                        'variant_id' => $variantId,
                        'on_hand' => $newOnHand,
                        'reserved' => 0,
                        'available' => $available,
                        'damaged' => $newDamaged,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('order_returns')->where('order_id', $order->id)->update([
                'status' => 'approved',
                'restocked' => 1,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'refund_amount' => $refundAmount,
                'refund_note' => $refundNote,
                'updated_at' => now(),
            ]);
        });

        Toastr::success('Success', 'Return approved and stock restocked');
        return redirect()->back();
    }

    public function refundsIndex(Request $request)
    {
        $rows = DB::table('order_returns')
            ->where('restocked', 1)
            ->where('refund_required', 1)
            ->orderBy('id', 'desc')
            ->paginate(10);

        $orders = Order::with(['customer', 'shipping'])
            ->whereIn('id', $rows->pluck('order_id')->all())
            ->get()
            ->keyBy('id');

        return view('backEnd.refunds.index', compact('rows', 'orders'));
    }

    public function markRefunded(Request $request, $orderId)
    {
        $request->validate([
            'refund_amount' => 'nullable|numeric',
            'refund_note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($orderId, $request) {
            $row = DB::table('order_returns')->where('order_id', $orderId)->lockForUpdate()->first();
            if (!$row) {
                return;
            }

            if ((int) ($row->refunded ?? 0) === 1) {
                return;
            }

            DB::table('order_returns')->where('order_id', $orderId)->update([
                'status' => 'refunded',
                'refunded' => 1,
                'refund_amount' => $request->input('refund_amount'),
                'refund_note' => $request->input('refund_note'),
                'updated_at' => now(),
            ]);
        });

        Toastr::success('Success', 'Refund marked successfully');
        return redirect()->back();
    }

    /**
     * DataTables feed for the returns grid.
     *
     * Same set as the blade page: delivered orders, newest first, each with the
     * customer, the shipping phone and whatever return row is attached to it.
     */
    public function datatable(Request $request)
    {
        [$start, $length] = $this->dtPaging($request);
        $search = $this->dtSearch($request);
        $deliveredStatus = $this->resolveOrderStatusBySlug('delivered');
        $query = Order::with(['customer', 'shipping'])
            ->when($deliveredStatus, fn ($q) => $q->where('order_status', $deliveredStatus->id));

        // How many rows exist at all - the pager needs this
        // number before the search narrows the table down.
        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_id', 'LIKE', "%{$search}%")
                  ->orWhereHas('shipping', fn ($sub) => $sub->where('phone', 'LIKE', "%{$search}%"));
            });
        }
        // How many survive the search; the pager counts these.
        $filtered = (clone $query)->count();

        [$column, $dir] = $this->dtOrdering($request, [1 => 'invoice_id', 4 => 'total'], 'id');
        $page = $query->orderBy($column ?: 'id', $dir)->skip($start)->take($length)->get();
        // The row prints "requested / restocked / none" per order, so the
        // return records for this page of orders have to come with them.
        $returnRows = DB::table('order_returns')
            ->whereIn('order_id', $page->pluck('id')->all())
            ->get()
            ->keyBy('order_id');

        $rows = [];
        foreach ($page as $i => $order) {
            $html = trim(view('backEnd.returns._row', [
                'value'      => $order,
                'index'      => $start + $i + 1,
                'returnRows' => $returnRows,
            ])->render());
            $rows[] = $this->dtCells($html, 7, 'returns/_row.blade.php');
        }

        return $this->dtResponse((int) $request->input('draw', 1), $total, $filtered, $rows);
    }
}
