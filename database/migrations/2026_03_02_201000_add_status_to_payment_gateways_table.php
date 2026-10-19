<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('payment_gateways', 'status')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->boolean('status')->default(true)->after('prefix');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('payment_gateways', 'status')) {
            Schema::table('payment_gateways', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
