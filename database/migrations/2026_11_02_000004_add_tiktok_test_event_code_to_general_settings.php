<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TikTok Events API 2.0 needs a `test_event_code` to route an event to the
 * Test Events tab. The old boolean `tiktok_test_mode` had nowhere to put the
 * code, so "test mode" could not actually produce a test event.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('general_settings', 'tiktok_test_event_code')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            $table->string('tiktok_test_event_code', 64)->nullable()->after('tiktok_test_mode');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('general_settings', 'tiktok_test_event_code')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn('tiktok_test_event_code');
        });
    }
};
