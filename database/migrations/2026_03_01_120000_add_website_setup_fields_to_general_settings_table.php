<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->text('footer_about_text')->nullable()->after('copyright');
            $table->string('footer_payment_image')->nullable()->after('footer_about_text');
            $table->string('primary_color', 32)->nullable()->after('footer_payment_image');
            $table->string('secondary_color', 32)->nullable()->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn([
                'footer_about_text',
                'footer_payment_image',
                'primary_color',
                'secondary_color',
            ]);
        });
    }
};
