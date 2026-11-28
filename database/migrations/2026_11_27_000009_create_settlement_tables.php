<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.1 (Wave 3) — settlement ledgers.
 *
 * Two ledgers, two questions:
 *   courier_settlements   COD money the courier collected and paid over
 *   payment_settlements   what a gateway settled after its fee
 *
 * The per-row columns on courier_shipments and payments hold the running
 * totals; these tables are the dated entries those totals are built from, so a
 * Payment or Courier page can show the entries instead of a single unexplained
 * number. ids referenced from the old tables are int unsigned there
 * (LIVE_SCHEMA_VERIFIED 2026-11-27), hence unsignedInteger, not foreignId.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id')->nullable();
            $table->string('provider', 32);
            $table->string('consignment_id', 64)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->date('remitted_at')->nullable();
            $table->string('reference', 64)->nullable();
            $table->string('note', 191)->nullable();
            $table->timestamps();

            $table->index('provider');
            $table->index('status');
            $table->index('remitted_at');
            $table->index('order_id');

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();
        });

        Schema::create('payment_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('payment_id');
            $table->decimal('settled_amount', 12, 2)->default(0);
            $table->decimal('gateway_fee', 12, 2)->default(0);
            $table->string('status', 20)->default('unsettled');
            $table->date('settled_at')->nullable();
            $table->string('reference', 64)->nullable();
            $table->string('note', 191)->nullable();
            $table->timestamps();

            $table->index('payment_id');
            $table->index('status');
            $table->index('settled_at');

            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settlements');
        Schema::dropIfExists('courier_settlements');
    }
};
