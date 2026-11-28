<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.1 — courier_shipments.
 *
 * C9  status_normalized  provider-agnostic status (Courier page comparisons)
 * C10 delivered_at       average delivery time
 * C11 cod_amount         COD the courier collected
 * C12 return_charge      reverse shipment cost
 * C13 remitted_amount    what the courier actually paid over
 * C14 settlement_status  pending / remitted
 * C20 status_updated_at  stalled shipment (SLA) signal
 *
 * Backfills restate data that already exists:
 *   - status_normalized is derived from the stored current_status
 *     ('Delivered', 'in_transit' are the only live values); unmapped values
 *     keep their own lowercased slug instead of being forced into a bucket.
 *   - delivered_at takes orders.delivered_at when the order has one; it is
 *     the same event recorded on the order row, not an estimate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_shipments', function (Blueprint $table) {
            $table->string('status_normalized', 24)->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('cod_amount', 12, 2)->default(0);
            $table->decimal('return_charge', 12, 2)->default(0);
            $table->decimal('remitted_amount', 12, 2)->default(0);
            $table->string('settlement_status', 20)->default('pending');
            $table->timestamp('status_updated_at')->nullable();

            $table->index('status_normalized');
            $table->index('delivered_at');
            $table->index('settlement_status');
            $table->index('status_updated_at');
        });

        $this->backfillStatus();
    }

    public function down(): void
    {
        Schema::table('courier_shipments', function (Blueprint $table) {
            $table->dropIndex(['status_updated_at']);
            $table->dropIndex(['settlement_status']);
            $table->dropIndex(['delivered_at']);
            $table->dropIndex(['status_normalized']);

            $table->dropColumn([
                'status_normalized',
                'delivered_at',
                'cod_amount',
                'return_charge',
                'remitted_amount',
                'settlement_status',
                'status_updated_at',
            ]);
        });
    }

    private function backfillStatus(): void
    {
        $map = [
            'delivered' => 'delivered',
            'success' => 'delivered',
            'completed' => 'delivered',
            'in_transit' => 'in_transit',
            'in-transit' => 'in_transit',
            'shipped' => 'in_transit',
            'dispatched' => 'in_transit',
            'picked' => 'in_transit',
            'returned' => 'returned',
            'return' => 'returned',
            'reverse' => 'returned',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'failed' => 'cancelled',
            'pending' => 'pending',
            'created' => 'pending',
            'booked' => 'pending',
        ];

        foreach (DB::table('courier_shipments')->get(['id', 'current_status']) as $row) {
            $raw = strtolower(trim((string) $row->current_status));
            $normalized = $map[$raw] ?? preg_replace('/[^a-z0-9_]+/', '_', substr($raw, 0, 24));

            DB::table('courier_shipments')
                ->where('id', $row->id)
                ->update(['status_normalized' => $normalized === '' ? 'pending' : $normalized]);
        }

        DB::statement(
            'UPDATE courier_shipments cs'
            . ' JOIN orders o ON o.id = cs.order_id'
            . ' SET cs.delivered_at = o.delivered_at'
            . ' WHERE o.delivered_at IS NOT NULL AND cs.delivered_at IS NULL'
        );
    }
};
