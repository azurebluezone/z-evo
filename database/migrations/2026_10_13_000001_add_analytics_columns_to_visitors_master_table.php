<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Advanced Real-time Analytics: enrich visitor records so the admin dashboard can
 * slice traffic by division, ISP, device, channel and attribution method.
 *
 * Existing rows stay untouched — the columns are nullable so historical data
 * never breaks and new traffic simply fills them in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors_master', function (Blueprint $table) {
            // --- Geo intelligence -------------------------------------------------
            $table->string('division', 60)->nullable()->index();

            // --- Device fingerprint ------------------------------------------------
            $table->string('device_type', 20)->nullable()->index();   // mobile|desktop|tablet|bot
            $table->string('browser', 60)->nullable()->index();
            $table->string('os', 60)->nullable()->index();

            // --- Acquisition / attribution ------------------------------------------
            $table->string('channel', 40)->nullable()->index();        // facebook|google|tiktok|direct|organic|referral
            $table->string('referrer_host', 190)->nullable()->index();
            $table->string('referrer_url', 1000)->nullable();
            $table->string('utm_source', 190)->nullable()->index();
            $table->string('utm_medium', 190)->nullable()->index();
            $table->string('utm_campaign', 190)->nullable()->index();
            $table->string('utm_term', 190)->nullable();
            $table->string('utm_content', 190)->nullable();

            // --- Landing / engagement -----------------------------------------------
            $table->string('landing_page', 1000)->nullable();
            $table->string('first_page_path', 500)->nullable()->index();

            // --- Live telemetry ------------------------------------------------------
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->string('last_page_url', 1000)->nullable();
            $table->string('last_event_name', 100)->nullable();
            $table->unsignedInteger('event_count')->default(0);
            $table->unsignedSmallInteger('pageview_count')->default(0);

            // --- Attribution bookkeeping ----------------------------------------------
            $table->unsignedTinyInteger('browser_event_count')->default(0);
            $table->unsignedTinyInteger('server_event_count')->default(0);
        });

        // Covering index for the dashboard's default range + channel slice.
        Schema::table('visitors_master', function (Blueprint $table) {
            $table->index(['created_at', 'channel'], 'vm_created_channel_idx');
            $table->index(['created_at', 'division'], 'vm_created_division_idx');
        });
    }

    public function down(): void
    {
        Schema::table('visitors_master', function (Blueprint $table) {
            $table->dropIndex('vm_created_channel_idx');
            $table->dropIndex('vm_created_division_idx');

            $table->dropColumn([
                'division',
                'device_type',
                'browser',
                'os',
                'channel',
                'referrer_host',
                'referrer_url',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'landing_page',
                'first_page_path',
                'last_seen_at',
                'last_page_url',
                'last_event_name',
                'event_count',
                'pageview_count',
                'browser_event_count',
                'server_event_count',
            ]);
        });
    }
};