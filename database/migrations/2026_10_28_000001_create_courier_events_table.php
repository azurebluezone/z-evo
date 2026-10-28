<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Append-only log of every courier webhook event.
 *
 * courier_shipments only holds the LATEST status (current_status + one
 * raw_payload), so each callback overwrote the last and the event history was
 * unrecoverable - once an order went delivered -> returned there was no record
 * of the delivered step. This table keeps every event in arrival order so the
 * admin can see THIS order's courier timeline instead of guessing.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('courier_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shipment_id')->nullable()->index();
            $table->unsignedInteger('order_id')->index();
            $table->string('provider', 32);
            $table->string('consignment_id', 64)->nullable();
            $table->string('courier_status', 64)->nullable();
            $table->string('order_status_slug', 32)->nullable();
            $table->json('payload')->nullable();
            $table->string('message', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['provider', 'consignment_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('courier_events');
    }
};
