<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\InventoryMovement;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $search = function ($query) use ($q) {
            $like = '%' . $q . '%';
            $maybeId = is_numeric($q) ? (int) $q : null;
            $query->where(function ($qq) use ($like, $maybeId) {
                $qq->where('p.name', 'like', $like)
                    ->orWhere('v.sku', 'like', $like)
                    ->orWhere('v.title', 'like', $like);

                if ($maybeId) {
                    $qq->orWhere('v.id', '=', $maybeId);
                }
            });
        };

        $base = DB::table('product_variants as v')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('variant_stocks as s', 's.variant_id', '=', 'v.id')
            ->when($q !== '', $search);

        /*
         * Available-to-promise, not the raw projection.
         *
         * `variant_stocks.reserved` is kept at 0 for compatibility; real
         * checkout holds live in inventory_reservations. Reading only
         * s.available told the admin a fully reserved variant was sellable.
         * The storefront already subtracts the holds, so the admin list must
         * use the same number or the two screens disagree.
         */
        $availableToPromise = 'GREATEST(COALESCE(s.available, 0) - COALESCE(s.reserved, 0) - ('
            . 'SELECT COALESCE(SUM(r.qty), 0) FROM inventory_reservations r '
            . 'WHERE r.variant_id = v.id AND r.expires_at > NOW()), 0)';

        // KPIs count the whole filtered result, not the 50 rows on this page.
        $kpiTotal = (clone $base)->count('v.id');
        $kpiOut = (clone $base)->whereRaw($availableToPromise . ' <= 0')->count();
        $kpiLow = (clone $base)
            ->whereRaw($availableToPromise . ' > 0')
            ->whereRaw($availableToPromise . ' <= 2')
            ->count();

        $variants = (clone $base)
            ->select(
                'v.id',
                'v.sku',
                'v.title',
                'v.product_id',
                'p.name as product_name',
                DB::raw('COALESCE(s.on_hand, 0) as on_hand'),
                DB::raw('COALESCE(s.reserved, 0) as reserved'),
                DB::raw($availableToPromise . ' as available'),
                DB::raw('COALESCE(s.damaged, 0) as damaged')
            )
            ->orderBy('v.id', 'desc')
            ->paginate(50)
            ->appends($request->query());

        $activeReservedTotal = 0;
        if (DB::getSchemaBuilder()->hasTable('inventory_reservations')) {
            $activeReservedTotal = (int) DB::table('inventory_reservations')
                ->where('expires_at', '>', now())
                ->sum('qty');
        }
        $damagedTotal = (int) DB::table('variant_stocks')->sum('damaged');

        $filters = ['q' => $q];
        return view('backEnd.inventory.index', compact(
            'variants',
            'filters',
            'activeReservedTotal',
            'damagedTotal',
            'kpiTotal',
            'kpiLow',
            'kpiOut'
        ));
    }

    public function adjustment(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $preselectedVariantId = $request->query('variant_id');

        $variants = DB::table('product_variants as v')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->select('v.id', 'v.sku', 'v.title', 'p.name as product_name')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($qq) use ($like) {
                    $qq->where('p.name', 'like', $like)
                        ->orWhere('v.sku', 'like', $like)
                        ->orWhere('v.title', 'like', $like);
                });
            })
            ->orderBy('v.id', 'desc')
            ->limit($q !== '' ? 200 : 500)
            ->get();

        $filters = ['q' => $q, 'variant_id' => $preselectedVariantId];
        return view('backEnd.inventory.adjustment', compact('variants', 'filters'));
    }

    public function ledger(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|integer',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'type' => 'nullable|string',
            'bucket' => 'nullable|string|in:sellable,damaged',
            'created_by' => 'nullable|integer',
        ]);

        $variantId = (int) $request->query('variant_id');

        $variant = DB::table('product_variants as v')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->select('v.id', 'v.sku', 'v.title', 'v.product_id', 'p.name as product_name')
            ->where('v.id', $variantId)
            ->first();
        if (!$variant) {
            abort(404);
        }

        $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->first();

        $activeReserved = 0;
        if (DB::getSchemaBuilder()->hasTable('inventory_reservations')) {
            $activeReserved = (int) DB::table('inventory_reservations')
                ->where('variant_id', $variantId)
                ->where('expires_at', '>', now())
                ->sum('qty');
        }

        $filters = [
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
            'type' => (string) $request->query('type', ''),
            'bucket' => (string) $request->query('bucket', ''),
            'created_by' => (string) $request->query('created_by', ''),
        ];

        $movements = DB::table('inventory_movements as m')
            ->leftJoin('users as u', 'u.id', '=', 'm.created_by')
            ->where('m.variant_id', $variantId)
            ->select('m.*', 'u.name as created_by_name')
            ->when($filters['type'] !== '', fn ($q) => $q->where('type', $filters['type']))
            ->when($filters['bucket'] !== '', fn ($q) => $q->where('bucket', $filters['bucket']))
            ->when($filters['created_by'] !== '', fn ($q) => $q->where('created_by', (int) $filters['created_by']))
            ->when($filters['from'] !== '', fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->appends($request->query());

        $recentMovements = DB::table('inventory_movements as m')
            ->leftJoin('users as u', 'u.id', '=', 'm.created_by')
            ->where('m.variant_id', $variantId)
            ->select('m.*', 'u.name as created_by_name')
            ->orderBy('m.id', 'desc')
            ->limit(10)
            ->get();

        $recentSummary = DB::table('inventory_movements')
            ->select('type', DB::raw('SUM(qty_delta) as qty_sum'), DB::raw('COUNT(*) as rows_count'))
            ->where('variant_id', $variantId)
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('type')
            ->orderByRaw('ABS(SUM(qty_delta)) DESC')
            ->limit(8)
            ->get();

        return view('backEnd.inventory.ledger', [
            'variant' => $variant,
            'stock' => $stock,
            'activeReserved' => $activeReserved,
            'filters' => $filters,
            'movements' => $movements,
            'recentMovements' => $recentMovements,
            'recentSummary' => $recentSummary,
        ]);
    }

    public function adjustmentStore(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'type' => 'nullable|string|in:ADJUSTMENT,RECEIVE,DAMAGE,LOST',
            'qty_delta' => 'required|integer|not_in:0',
            'note' => 'nullable|string|max:255',
        ], [
            'variant_id.exists' => 'That variant no longer exists. Pick another one.',
            'qty_delta.not_in' => 'Enter a positive number to add stock or a negative number to reduce it.',
        ]);

        $variantId = (int) $request->variant_id;
        $type = $request->input('type') ?: 'ADJUSTMENT';
        $qtyDelta = (int) $request->qty_delta;
        $note = $request->note;

        /*
         * DAMAGE and LOST name a positive COUNT of units, not a warehouse
         * delta. Normalising once here keeps the projection and the ledger
         * row pointing in the same direction:
         *
         *   RECEIVE  +N on hand
         *   DAMAGE    N moved from sellable to damaged; on_hand is unchanged
         *             because a damaged unit is still physically in the
         *             warehouse
         *   LOST     -N on hand, nothing added to damaged
         */
        $unitCount = abs($qtyDelta);
        $onHandDelta = match ($type) {
            'RECEIVE' => $unitCount,
            'DAMAGE' => 0,
            'LOST' => -$unitCount,
            default => $qtyDelta,
        };
        $damagedDelta = $type === 'DAMAGE' ? $unitCount : 0;
        $ledgerQty = $type === 'DAMAGE' ? $unitCount : $onHandDelta;

        DB::transaction(function () use ($variantId, $type, $qtyDelta, $onHandDelta, $damagedDelta, $ledgerQty, $unitCount, $note) {
            $variant = DB::table('product_variants')->where('id', $variantId)->first();
            if (!$variant) {
                throw ValidationException::withMessages([
                    'variant_id' => 'That variant no longer exists. Pick another one.',
                ]);
            }

            $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->lockForUpdate()->first();
            if (!$stock) {
                DB::table('variant_stocks')->insert([
                    'variant_id' => $variantId,
                    'on_hand' => 0,
                    'reserved' => 0,
                    'available' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $stock = DB::table('variant_stocks')->where('variant_id', $variantId)->lockForUpdate()->first();
            }

            $currentOnHand = (int) ($stock->on_hand ?? 0);
            $currentReserved = (int) ($stock->reserved ?? 0);
            $currentDamaged = (int) ($stock->damaged ?? 0);
            $newOnHand = $currentOnHand + $onHandDelta;
            if ($newOnHand < 0) {
                throw ValidationException::withMessages([
                    'qty_delta' => 'Stock cannot go below 0. Only ' . $currentOnHand . ' unit(s) are on hand.',
                ]);
            }

            $sellable = max(0, $currentOnHand - $currentDamaged - $currentReserved);
            if ($type === 'DAMAGE' && $unitCount > $sellable) {
                throw ValidationException::withMessages([
                    'qty_delta' => 'Only ' . $sellable . ' sellable unit(s) can be marked damaged.',
                ]);
            }

            $bucket = $type === 'DAMAGE' ? 'damaged' : 'sellable';
            $hasBucket = DB::getSchemaBuilder()->hasColumn('inventory_movements', 'bucket');
            $hasDamaged = DB::getSchemaBuilder()->hasColumn('variant_stocks', 'damaged');

            $newDamaged = $currentDamaged + $damagedDelta;
            $newAvailable = max(0, $newOnHand - $newDamaged - $currentReserved);

            $movement = [
                'variant_id' => $variantId,
                'type' => $type,
                'qty_delta' => $ledgerQty,
                'ref_type' => 'manual',
                'ref_id' => null,
                'created_by' => auth()->id(),
                'note' => $note ?: 'Manual stock adjustment',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($hasBucket) {
                $movement['bucket'] = $bucket;
            }
            InventoryMovement::record($movement);

            $stockUpdate = [
                'on_hand' => $newOnHand,
                'available' => $newAvailable,
                'updated_at' => now(),
            ];
            if ($hasDamaged) {
                $stockUpdate['damaged'] = $newDamaged;
            }
            DB::table('variant_stocks')->where('variant_id', $variantId)->update($stockUpdate);
        });

        return redirect()->route('admin.inventory.index')->with('success', 'Stock adjusted successfully');
    }
}
