<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.1 (Wave 2) — report_views: saved filters.
 *
 * A report page is a query plus a filter set; this table stores the filter set
 * so an admin can name it and open it again. view_key is the route-level page
 * key (for example 'orders' or 'stock-alerts'), filters is the JSON the page
 * reads back. The user is nullable so a shared view survives its owner, and it
 * disappears with the account on delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('view_key', 64);
            $table->string('name', 120);
            $table->json('filters');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'view_key'], 'report_views_user_view_index');
            $table->index('view_key');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_views');
    }
};
