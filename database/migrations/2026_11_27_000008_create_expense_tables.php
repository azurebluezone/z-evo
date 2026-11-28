<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * report-column-map v1.1 (Wave 3) — business expenses.
 *
 * Net Profit is revenue minus every cost of running the business, and the
 * courier/COGS numbers alone are not that. expense_categories keeps the split
 * (ads, packing, salary, rent, ...), business_expenses is one row per spend.
 *
 * amount is decimal(14,2) like every other money column in this project,
 * spent_at is a date (an expense has a day, not an instant), and created_by
 * references users.id which is int unsigned in this database
 * (LIVE_SCHEMA_VERIFIED 2026-11-27).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('business_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id');
            $table->decimal('amount', 14, 2);
            $table->date('spent_at')->index();
            $table->string('payment_method', 32)->nullable();
            $table->string('note', 191)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('expense_category_id');

            $table->foreign('expense_category_id')
                ->references('id')
                ->on('expense_categories')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_expenses');
        Schema::dropIfExists('expense_categories');
    }
};
