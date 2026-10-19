<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('courier_fraud_checks', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 32);
            $table->string('provider', 32)->nullable();
            $table->unsignedTinyInteger('success_rate_percent')->nullable();
            $table->string('risk_label', 32)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamps();

            $table->index(['phone', 'provider']);
            $table->index(['checked_at']);
            $table->index(['risk_label']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('courier_fraud_checks');
    }
};
