<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $variants = DB::table('product_variants as v')
            ->leftJoin('products as p', 'p.id', '=', 'v.product_id')
            ->leftJoin('variant_stocks as s', 's.variant_id', '=', 'v.id')
            ->select(
                'v.id',
                'v.sku',
                'v.title',
                'v.product_id',
                'p.name as product_name',
                DB::raw('COALESCE(s.on_hand, 0) as on_hand'),
                DB::raw('COALESCE(s.reserved, 0) as reserved'),
                DB::raw('COALESCE(s.available, 0) as available'),
                DB::raw('COALESCE(s.damaged, 0) as damaged')
            )
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $maybeId = is_numeric($q) ? (int) $q : null;
                $query->where(function ($qq) use ($like, $maybeId) {
                    $qq->where('p.name', 'like', $like)
                        ->orWhere('v.sku', 'like', $like)
                        ->orWhere('v.title', 'like', $like)
                        ->orWhere('v.id', '=', -1);

                    if ($maybeId) {
                        $qq->orWhere('v.id', '=', $maybeId);
                    }
                });
            })
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
        return view('backEnd.inventory.index', compact('variants', 'filters', 'activeReservedTotal', 'damagedTotal'));
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
            'variant_id' => 'required|integer',
            'type' => 'nullable|string|in:ADJUSTMENT,RECEIVE,DAMAGE,LOST',
            'qty_delta' => 'required|integer|not_in:0',
            'note' => 'nullable|string|max:255',
        ]);

        $variantId = (int) $request->variant_id;
        $type = $request->input('type') ?: 'ADJUSTMENT';
        $qtyDelta = (int) $request->qty_delta;
        $note = $request->note;

        DB::transaction(function () use ($variantId, $type, $qtyDelta, $note) {
            $variant = DB::table('product_variants')->where('id', $variantId)->first();
            if (!$variant) {
                abort(404);
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
            $newOnHand = $currentOnHand + $qtyDelta;
            if ($newOnHand < 0) {
                abort(422, 'Stock cannot go below 0');
            }

            $newAvailable = $newOnHand - $currentReserved;
            if ($newAvailable < 0) {
                $newAvailable = 0;
            }

            $bucket = 'sellable';
            if (in_array($type, ['DAMAGE', 'LOST'], true)) {
                $bucket = 'damaged';
            }
            $hasBucket = DB::getSchemaBuilder()->hasColumn('inventory_movements', 'bucket');

            $damagedDelta = 0;
            $hasDamaged = DB::getSchemaBuilder()->hasColumn('variant_stocks', 'damaged');
            if ($hasDamaged && $bucket === 'damaged' && $qtyDelta > 0) {
                $damagedDelta = $qtyDelta;
            }

            $movement = [
                'variant_id' => $variantId,
                'type' => $type,
                'qty_delta' => $qtyDelta,
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
            DB::table('inventory_movements')->insert($movement);

            $stockUpdate = [
                'on_hand' => $newOnHand,
                'available' => $newAvailable,
                'updated_at' => now(),
            ];
            if ($hasDamaged && $damagedDelta !== 0) {
                $stockUpdate['damaged'] = (int) ($stock->damaged ?? 0) + (int) $damagedDelta;
            }
            DB::table('variant_stocks')->where('variant_id', $variantId)->update($stockUpdate);

            if (!empty($variant->product_id)) {
                $product = DB::table('products')->where('id', $variant->product_id)->lockForUpdate()->first();
                if ($product) {
                    $newLegacy = (int) ($product->stock ?? 0) + $qtyDelta;
                    if ($newLegacy < 0) {
                        $newLegacy = 0;
                    }
                    DB::table('products')->where('id', $variant->product_id)->update([
                        'stock' => $newLegacy,
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('admin.inventory.index')->with('success', 'Stock adjusted successfully');
    }
}
