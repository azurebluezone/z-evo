<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'risk_label')) {
                $table->string('risk_label', 32)->nullable()->after('order_status');
            }
            if (!Schema::hasColumn('orders', 'risk_score_percent')) {
                $table->unsignedTinyInteger('risk_score_percent')->nullable()->after('risk_label');
            }
            if (!Schema::hasColumn('orders', 'risk_last_checked_at')) {
                $table->timestamp('risk_last_checked_at')->nullable()->after('risk_score_percent');
            }
            if (!Schema::hasColumn('orders', 'risk_source')) {
                $table->string('risk_source', 32)->nullable()->after('risk_last_checked_at');
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'risk_source')) {
                $table->dropColumn('risk_source');
            }
            if (Schema::hasColumn('orders', 'risk_last_checked_at')) {
                $table->dropColumn('risk_last_checked_at');
            }
            if (Schema::hasColumn('orders', 'risk_score_percent')) {
                $table->dropColumn('risk_score_percent');
            }
            if (Schema::hasColumn('orders', 'risk_label')) {
                $table->dropColumn('risk_label');
            }
        });
    }
};
