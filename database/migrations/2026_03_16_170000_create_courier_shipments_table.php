<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('courier_shipments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('order_id');
            $table->string('provider', 32); // pathao, steadfast
            $table->string('merchant_order_id', 64)->nullable(); // usually invoice_id
            $table->string('consignment_id', 64)->nullable();
            $table->string('tracking_code', 64)->nullable();
            $table->string('current_status', 64)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['provider', 'merchant_order_id']);
            $table->index(['provider', 'consignment_id']);
            $table->index(['order_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('courier_shipments');
    }
};
