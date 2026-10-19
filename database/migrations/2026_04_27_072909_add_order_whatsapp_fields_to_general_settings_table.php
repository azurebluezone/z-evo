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
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'order_whatsapp_enabled')) {
                $table->boolean('order_whatsapp_enabled')->default(false)->after('support_phone');
            }

            if (!Schema::hasColumn('general_settings', 'order_whatsapp_number')) {
                $table->string('order_whatsapp_number')->nullable()->after('order_whatsapp_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (Schema::hasColumn('general_settings', 'order_whatsapp_number')) {
                $table->dropColumn('order_whatsapp_number');
            }

            if (Schema::hasColumn('general_settings', 'order_whatsapp_enabled')) {
                $table->dropColumn('order_whatsapp_enabled');
            }
        });
    }
};
