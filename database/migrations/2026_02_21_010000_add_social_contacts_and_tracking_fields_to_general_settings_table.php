<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('whatsapp_number')->nullable()->after('facebook_custom_events');
            $table->string('messenger_link')->nullable()->after('whatsapp_number');
            $table->string('support_phone')->nullable()->after('messenger_link');

            $table->boolean('order_whatsapp_enabled')->default(false)->after('support_phone');
            $table->string('order_whatsapp_number')->nullable()->after('order_whatsapp_enabled');

            $table->string('gtm_server_container_id')->nullable()->after('support_phone');
            $table->string('gtm_server_url')->nullable()->after('gtm_server_container_id');
            $table->boolean('gtm_server_side_tracking')->default(false)->after('gtm_server_url');

            $table->string('tiktok_pixel_id')->nullable()->after('gtm_server_side_tracking');
            $table->text('tiktok_access_token')->nullable()->after('tiktok_pixel_id');
            $table->boolean('tiktok_server_side_tracking')->default(false)->after('tiktok_access_token');
            $table->boolean('tiktok_browser_tracking')->default(false)->after('tiktok_server_side_tracking');
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_number',
                'messenger_link',
                'support_phone',
                'order_whatsapp_enabled',
                'order_whatsapp_number',
                'gtm_server_container_id',
                'gtm_server_url',
                'gtm_server_side_tracking',
                'tiktok_pixel_id',
                'tiktok_access_token',
                'tiktok_server_side_tracking',
                'tiktok_browser_tracking',
            ]);
        });
    }
};
