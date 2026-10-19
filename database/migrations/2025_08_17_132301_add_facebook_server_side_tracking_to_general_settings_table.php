<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('facebook_pixel_id')->nullable()->after('favicon');
            $table->text('facebook_conversion_api_token')->nullable()->after('facebook_pixel_id');
            $table->string('facebook_conversion_api_version')->default('v17.0')->after('facebook_conversion_api_token');
            $table->boolean('facebook_server_side_tracking')->default(false)->after('facebook_conversion_api_version');
            $table->boolean('facebook_enhanced_ecommerce')->default(false)->after('facebook_server_side_tracking');
            $table->text('facebook_custom_events')->nullable()->after('facebook_enhanced_ecommerce');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_pixel_id',
                'facebook_conversion_api_token',
                'facebook_conversion_api_version',
                'facebook_server_side_tracking',
                'facebook_enhanced_ecommerce',
                'facebook_custom_events'
            ]);
        });
    }
};
