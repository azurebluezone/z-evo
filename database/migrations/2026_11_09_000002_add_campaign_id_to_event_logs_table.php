<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campaign attribution for first-party analytics.
 *
 * The browser event layer already records page/path/consent; a campaign landing
 * page additionally needs its campaign id as a first-class column so reports can
 * group without parsing event_details JSON. This is deliberately not a foreign
 * key: deleting a campaign must not delete (or block) its historical events.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('event_logs', function (Blueprint $table) {
            $table->dropColumn('campaign_id');
        });
    }
};
