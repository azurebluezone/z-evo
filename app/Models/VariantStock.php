<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The per-variant stock projection.
 *
 * SSOT is `inventory_movements`; this table is the current balance. The legacy
 * The legacy product-level stock column was dropped (migration
 * 2026_11_26_000002), so a missing row must start at zero rather than inherit
 * a stale product figure.
 *
 * `ensureRow()` is the one place that creates a missing row. It is idempotent:
 * an existing row is never touched, so a repeated checkout or seeder run cannot
 * reset a balance.
 */
class VariantStock extends Model
{
    protected $table = 'variant_stocks';

    protected $primaryKey = 'variant_id';

    public $incrementing = false;

    protected $fillable = [
        'variant_id',
        'on_hand',
        'reserved',
        'available',
        'damaged',
        'low_stock_threshold',
    ];

    /**
     * Create the stock row for ONE variant that has none.
     *
     * @param  int       $initial  opening units; 0 for the first real read
     * @param  int|null  $byAdmin  auth id recorded on the opening movement
     * @return bool                true when a row was created
     */
    public static function ensureRow(int $variantId, int $initial = 0, ?int $byAdmin = null): bool
    {
        if ($variantId <= 0) {
            return false;
        }

        if (DB::table('variant_stocks')->where('variant_id', $variantId)->exists()) {
            return false;
        }

        $variantExists = DB::table('product_variants')->where('id', $variantId)->exists();
        if (! $variantExists) {
            return false;
        }

        $initial = max(0, $initial);
        $now = now();

        DB::table('variant_stocks')->insert([
            'variant_id' => $variantId,
            'on_hand' => $initial,
            'reserved' => 0,
            'available' => $initial,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (Schema::hasTable('inventory_movements')) {
            InventoryMovement::record([
                'variant_id' => $variantId,
                'type' => 'OPENING_BALANCE',
                'bucket' => 'sellable',
                'qty_delta' => $initial,
                'ref_type' => 'variant',
                'ref_id' => $variantId,
                'created_by' => $byAdmin,
                'note' => $initial > 0
                    ? 'Opening balance created with the first stock read.'
                    : 'Zero opening balance created with the first stock read.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return true;
    }
}
