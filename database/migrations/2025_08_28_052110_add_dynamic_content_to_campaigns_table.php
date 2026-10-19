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
        Schema::table('campaigns', function (Blueprint $table) {
            // Hero Section
            $table->string('hero_title')->nullable()->after('description');
            $table->text('hero_subtitle')->nullable()->after('hero_title');
            $table->string('hero_button_text')->nullable()->after('hero_subtitle');
            $table->string('hero_button_link')->nullable()->after('hero_button_text');
            
            // Features Section
            $table->string('features_title')->nullable()->after('hero_button_link');
            $table->text('features_subtitle')->nullable()->after('features_title');
            
            // Benefits Section
            $table->string('benefits_title')->nullable()->after('features_subtitle');
            $table->text('benefits_subtitle')->nullable()->after('benefits_title');
            
            // Testimonials Section
            $table->string('testimonials_title')->nullable()->after('benefits_subtitle');
            $table->text('testimonials_subtitle')->nullable()->after('testimonials_title');
            
            // FAQ Section
            $table->string('faq_title')->nullable()->after('testimonials_subtitle');
            $table->text('faq_subtitle')->nullable()->after('faq_title');
            
            // CTA Section
            $table->string('cta_title')->nullable()->after('faq_subtitle');
            $table->text('cta_subtitle')->nullable()->after('cta_title');
            $table->string('cta_button_text')->nullable()->after('cta_subtitle');
            
            // SEO & Meta
            $table->string('meta_title')->nullable()->after('cta_button_text');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->text('meta_keywords')->nullable()->after('meta_description');
            
            // Custom CSS & JS
            $table->text('custom_css')->nullable()->after('meta_keywords');
            $table->text('custom_js')->nullable()->after('custom_css');
            
            // Campaign Settings
            $table->boolean('show_features')->default(true)->after('custom_js');
            $table->boolean('show_benefits')->default(true)->after('show_features');
            $table->boolean('show_testimonials')->default(true)->after('show_benefits');
            $table->boolean('show_faq')->default(true)->after('show_testimonials');
            $table->boolean('show_cta')->default(true)->after('show_faq');
            
            // Campaign Type
            $table->enum('campaign_type', ['product', 'service', 'landing', 'promotional'])->default('product')->after('show_cta');
            
            // Campaign Status
            $table->date('start_date')->nullable()->after('campaign_type');
            $table->date('end_date')->nullable()->after('start_date');
            $table->boolean('is_featured')->default(false)->after('end_date');
            $table->integer('sort_order')->default(0)->after('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'hero_title', 'hero_subtitle', 'hero_button_text', 'hero_button_link',
                'features_title', 'features_subtitle',
                'benefits_title', 'benefits_subtitle',
                'testimonials_title', 'testimonials_subtitle',
                'faq_title', 'faq_subtitle',
                'cta_title', 'cta_subtitle', 'cta_button_text',
                'meta_title', 'meta_description', 'meta_keywords',
                'custom_css', 'custom_js',
                'show_features', 'show_benefits', 'show_testimonials', 'show_faq', 'show_cta',
                'campaign_type', 'start_date', 'end_date', 'is_featured', 'sort_order'
            ]);
        });
    }
};
