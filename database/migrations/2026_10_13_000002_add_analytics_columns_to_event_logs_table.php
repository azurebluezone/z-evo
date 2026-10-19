<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Event-level attribution: this is what makes Browser (Pixel) vs Server (CAPI)
 * reporting and deduplication possible.
 *
 * `event_id` is the shared key the browser pixel and the server-side CAPI call
 * both use. The first writer wins and sets `dedup_status = 'primary'`; the
 * second arrival is rejected at the controller level, and any late arrival is
 * recorded as `duplicate` so the admin can see dedup health instead of
 * silently losing the row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            // --- Cross-platform identity -------------------------------------------
            $table->string('event_id', 64)->nullable()->index();

            // browser | server | hybrid
            $table->string('tracking_method', 20)->nullable()->index();

            // facebook | google | tiktok | direct | organic | referral
            $table->string('channel', 40)->nullable()->index();

            $table->string('page_path', 500)->nullable()->index();
            $table->string('device_type', 20)->nullable()->index();

            // primary | duplicate | browser_only | server_only
            $table->string('dedup_status', 20)->nullable()->index();
            $table->string('dedup_matched_with', 64)->nullable();

            // Scroll depth + outbound click targets feed the Sankey / drop-off view.
            $table->string('click_target', 500)->nullable()->index();
            $table->string('outbound_host', 190)->nullable()->index();
            $table->unsignedTinyInteger('scroll_depth')->nullable();

            $table->string('fbclid', 190)->nullable()->index();
            $table->string('ttclid', 190)->nullable()->index();
            $table->string('gclid', 190)->nullable()->index();
        });

        Schema::table('event_logs', function (Blueprint $table) {
            $table->index(['created_at', 'channel'], 'el_created_channel_idx');
            $table->index(['visitor_id', 'event_time'], 'el_visitor_time_idx');
        });
    }

    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropIndex('el_created_channel_idx');
            $table->dropIndex('el_visitor_time_idx');

            $table->dropColumn([
                'event_id',
                'tracking_method',
                'channel',
                'page_path',
                'device_type',
                'dedup_status',
                'dedup_matched_with',
                'click_target',
                'outbound_host',
                'scroll_depth',
                'fbclid',
                'ttclid',
                'gclid',
            ]);
        });
    }
};