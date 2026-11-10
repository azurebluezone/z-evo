<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which source produced the order's risk score.
 *
 * `risk_source` already records WHERE the check ran (checkout, sync, manual).
 * It cannot answer the question the admin actually asks on the order row:
 * "was this number checked against Pathao, SteadFast or only our own
 * shipments?" Those are different answers, and a local-only score must not
 * look like a courier-verified one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'risk_provider')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('risk_provider', 32)->nullable()->after('risk_source');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'risk_provider')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('risk_provider');
        });
    }
};
