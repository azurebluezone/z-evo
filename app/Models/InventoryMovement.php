<?php

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The inventory ledger.
 *
 * ---------------------------------------------------------------------------
 * WHY THIS EXISTS BESIDE THE TABLE
 * ---------------------------------------------------------------------------
 * The ledger was written with DB::table('inventory_movements')->insert() at
 * ten different call sites, which meant:
 *
 *   - no model events fired anywhere, so nothing could listen for a movement,
 *   - and the audit trail the shop asked for could not be attached to the
 *     movement itself.
 *
 * Every one of those call sites now goes through record()/recordMany(), which
 * writes the ledger row AND the audit row in one place. The audit row keeps
 * the "who" that the ledger row often lacks (a movement written by a
 * background sync has no session, but the admin who triggered it can still be
 * named).
 */
class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    public $timestamps = false;

    protected $fillable = [
        'variant_id',
        'type',
        'bucket',
        'qty_delta',
        'ref_type',
        'ref_id',
        'created_by',
        'note',
    ];

    /**
     * Write one movement and its audit row.
     *
     * @param array $row the ledger row, exactly as the caller used to build it
     */
    public static function record(array $row): void
    {
        DB::table('inventory_movements')->insert($row);

        static::audit($row);
    }

    /**
     * Write many movements and an audit row for each.
     */
    public static function recordMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        DB::table('inventory_movements')->insert($rows);

        foreach ($rows as $row) {
            static::audit($row);
        }
    }

    /**
     * The readable copy of a movement in the audit trail.
     */
    private static function audit(array $row): void
    {
        try {
            app(AuditLogger::class)->inventoryMovement(
                $row,
                (string) ($row['note'] ?? '')
            );
        } catch (\Throwable $e) {
            // The audit trail must never cost the caller its ledger write.
            report($e);
        }
    }
}
