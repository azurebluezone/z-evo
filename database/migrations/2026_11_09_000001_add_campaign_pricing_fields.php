<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 of campaign-specific pricing.
 *
 * The product catalog remains the source of truth for products.new_price.
 * A campaign owns its own selling price, and an order line snapshots the
 * campaign identity plus the pre-campaign regular price. All columns are
 * nullable so existing campaigns and order lines keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('template_id')->index();
            $table->decimal('campaign_price', 12, 2)->nullable()->after('product_id');
            $table->boolean('campaign_price_enabled')->default(false)->after('campaign_price');
            $table->decimal('campaign_discount_percent', 5, 2)->nullable()->after('campaign_price_enabled');
            $table->boolean('allow_coupon')->default(false)->after('campaign_discount_percent');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->unsignedInteger('campaign_id')->nullable()->after('variant_id')->index();
            $table->decimal('regular_price', 12, 2)->nullable()->after('sale_price');

            $table->foreign('campaign_id')
                ->references('id')
                ->on('campaigns')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn(['campaign_id', 'regular_price']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'product_id',
                'campaign_price',
                'campaign_price_enabled',
                'campaign_discount_percent',
                'allow_coupon',
            ]);
        });
    }
};
