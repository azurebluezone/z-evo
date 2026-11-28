<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.1 (Wave 4) — marketing spend and order attribution.
 *
 * marketing_spends   one row per spend on a channel and a day: ROAS is
 *                    revenue from that channel over what that channel cost.
 * order_attributions the bridge from an order back to the visit that produced
 *                    it, fed by orders.attributed_visitor_id (C19).
 *
 * Empty by design until the traffic source writes into them: a table that
 * starts empty lets the report show "not tracked yet" instead of a false zero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_spends', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 32);
            $table->string('campaign_name', 120)->nullable();
            $table->decimal('spent', 14, 2)->default(0);
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->date('spent_at')->index();
            $table->string('source_reference', 64)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('channel');
        });

        Schema::create('order_attributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->string('source', 32);
            $table->string('medium', 32)->nullable();
            $table->string('campaign', 120)->nullable();
            $table->string('visitor_id', 64)->nullable();
            $table->string('model', 16)->default('first_touch');
            $table->timestamp('attributed_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('source');
            $table->index('visitor_id');

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_attributions');
        Schema::dropIfExists('marketing_spends');
    }
};
