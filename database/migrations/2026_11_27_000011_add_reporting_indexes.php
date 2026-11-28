<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.1 (Wave 1 index set) — index-only migration.
 *
 * Three indexes the report queries need, on columns that gained no new
 * column. All three were verified missing with SHOW INDEX before writing:
 * courier_events, inventory_movements and products carried only PRIMARY and
 * their own unique/lookup indexes. Reports filter or group on these columns,
 * so without them the page cost grows with the row count.
 *
 * products.category_id and inventory_movements were re-checked for the column
 * itself before the index was written (all three columns exist).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_events', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('courier_events', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
        });
    }
};
