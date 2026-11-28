<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.1 — payments.
 *
 * C4  gateway_fee           what the gateway kept, so received != invoiced
 * C5  settled_amount        what actually arrived
 * C6  settlement_status     unsettled / settled / failed
 * C7  settled_at            settlement date
 * C8  settlement_reference  bank or gateway reference
 * C16 is_cod                COD is cash in hand, not gateway money
 *
 * Indexes: settlement_status, is_cod, plus the report filters order_id,
 * payment_status and created_at — all verified missing with SHOW INDEX.
 *
 * Backfill for is_cod only restates payment_method: 'Cash On Delivery' is
 * already the value in the table (LIVE_SCHEMA_VERIFIED 2026-11-27).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('gateway_fee', 12, 2)->default(0);
            $table->decimal('settled_amount', 12, 2)->default(0);
            $table->string('settlement_status', 20)->default('unsettled');
            $table->timestamp('settled_at')->nullable();
            $table->string('settlement_reference', 64)->nullable();
            $table->boolean('is_cod')->default(false);

            $table->index('settlement_status');
            $table->index('is_cod');
            $table->index('order_id');
            $table->index('payment_status');
            $table->index('created_at');
        });

        DB::table('payments')
            ->whereIn('payment_method', ['Cash On Delivery', 'COD', 'cod', 'Cash on delivery'])
            ->update(['is_cod' => true]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['order_id']);
            $table->dropIndex(['is_cod']);
            $table->dropIndex(['settlement_status']);

            $table->dropColumn([
                'gateway_fee',
                'settled_amount',
                'settlement_status',
                'settled_at',
                'settlement_reference',
                'is_cod',
            ]);
        });
    }
};
