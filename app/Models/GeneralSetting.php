<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSeoMeta;

class GeneralSetting extends Model
{
    use HasFactory;
    use HasSeoMeta;
        /**
     * Allow-list instead of `$guarded = []`.
     * This controller fed a request-derived array into create()/update();
     * with an open guard any posted key - including `id` - reached a column.
     *
     * Set from the live `GeneralSetting` schema: every writable column
     * except id/created_at/updated_at.
     */
    protected $fillable = [
        'name',
        'white_logo',
        'dark_logo',
        'favicon',
        'facebook_pixel_id',
        'facebook_conversion_api_token',
        'facebook_conversion_api_version',
        'facebook_server_side_tracking',
        'facebook_enhanced_ecommerce',
        'facebook_custom_events',
        'whatsapp_number',
        'messenger_link',
        'support_phone',
        'gtm_server_container_id',
        'gtm_server_url',
        'gtm_server_side_tracking',
        'tiktok_pixel_id',
        'tiktok_access_token',
        'tiktok_server_side_tracking',
        'tiktok_browser_tracking',
        'tiktok_test_mode',
        'order_whatsapp_enabled',
        'order_whatsapp_number',
        'copyright',
        'footer_about_text',
        'footer_payment_image',
        'primary_color',
        'secondary_color',
        'frontend_empty_image',
        'about_url',
        'contact_url',
        'header_links',
        'status',
        'frontend_theme',
        'home_page_top_categories_section',
        'home_page_brands_section',
        'home_page_hot_deal_section',
        'home_hero_section',
        'home_hero_title',
        'home_recommended_section',
        'home_recommended_title',
        'home_category_section',
        'home_category_title',
        'home_footer_section',
        'home_footer_title',
        'home_promo_section',
        'home_promo_title',
        'home_featured_section',
        'home_featured_title',
        'home_collections_section',
        'home_collections_title',
        'home_brands_section',
        'home_brands_title',
        'home_service_section',
        'home_service_title',
        'home_best_selling_section',
        'home_best_selling_title',
        'home_discount_products_section',
        'home_discount_products_title',
        'home_flash_sale_section',
        'home_flash_sale_title',
    ];

    protected $casts = [
        'facebook_server_side_tracking' => 'boolean',
        'facebook_enhanced_ecommerce' => 'boolean',
        'order_whatsapp_enabled' => 'boolean',
        'tiktok_server_side_tracking' => 'boolean',
        'tiktok_browser_tracking' => 'boolean',
        'tiktok_test_mode' => 'boolean',
        'gtm_server_side_tracking' => 'boolean',
    ];
}
