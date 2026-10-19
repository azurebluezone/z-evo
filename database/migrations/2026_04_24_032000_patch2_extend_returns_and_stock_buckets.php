<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('order_returns', 'return_mode')) {
                $table->string('return_mode', 30)->default('customer_return')->index()->after('status');
            }
            if (!Schema::hasColumn('order_returns', 'restock_condition')) {
                $table->string('restock_condition', 20)->default('sellable')->index()->after('return_mode');
            }
            if (!Schema::hasColumn('order_returns', 'refund_required')) {
                $table->boolean('refund_required')->default(true)->index()->after('restocked');
            }
            if (!Schema::hasColumn('order_returns', 'refund_note')) {
                $table->text('refund_note')->nullable()->after('refund_amount');
            }
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_movements', 'bucket')) {
                $table->string('bucket', 20)->default('sellable')->index()->after('type');
            }
        });

        Schema::table('variant_stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('variant_stocks', 'damaged')) {
                $table->integer('damaged')->default(0)->after('available');
            }
        });

        if (Schema::hasColumn('inventory_movements', 'bucket')) {
            DB::table('inventory_movements')->whereNull('bucket')->update(['bucket' => 'sellable']);
        }
    }

    public function down(): void
    {
        Schema::table('order_returns', function (Blueprint $table) {
            if (Schema::hasColumn('order_returns', 'refund_note')) {
                $table->dropColumn('refund_note');
            }
            if (Schema::hasColumn('order_returns', 'refund_required')) {
                $table->dropColumn('refund_required');
            }
            if (Schema::hasColumn('order_returns', 'restock_condition')) {
                $table->dropColumn('restock_condition');
            }
            if (Schema::hasColumn('order_returns', 'return_mode')) {
                $table->dropColumn('return_mode');
            }
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_movements', 'bucket')) {
                $table->dropColumn('bucket');
            }
        });

        Schema::table('variant_stocks', function (Blueprint $table) {
            if (Schema::hasColumn('variant_stocks', 'damaged')) {
                $table->dropColumn('damaged');
            }
        });
    }
};
