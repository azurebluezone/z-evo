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
        Schema::table('visitors_master', function (Blueprint $table) {
            $table->index('created_at', 'visitors_master_created_at_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('phone', 'customers_phone_index');
        });

        Schema::table('shippings', function (Blueprint $table) {
            $table->index('phone', 'shippings_phone_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors_master', function (Blueprint $table) {
            $table->dropIndex('visitors_master_created_at_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_phone_index');
        });

        Schema::table('shippings', function (Blueprint $table) {
            $table->dropIndex('shippings_phone_index');
        });
    }
};
