<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Singleton settings row for the report dashboard, stock thresholds and the
 * scheduled email. Defaults are part of the schema, so a fresh install warns
 * only on out-of-stock (0) and shows the "only N left" badge at 2 pieces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('stock_warning_threshold')->default(0);
            $table->unsignedInteger('stock_remaining_threshold')->default(2);
            $table->string('stock_alert_dismissed_signature', 64)->nullable();
            $table->string('report_email')->nullable();
            $table->boolean('auto_send_enabled')->default(false);
            $table->time('send_time')->default('09:00:00');
            $table->date('last_sent_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_settings');
    }
};
