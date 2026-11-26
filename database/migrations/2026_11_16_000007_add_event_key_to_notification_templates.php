<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the event-key metadata to notification_templates.
 *
 * event_key     the business event that selects this template (null = manual)
 * channels      JSON array; database is the default, mail is opt-in
 * variables     JSON array documenting the supported {placeholders}
 * description   admin-facing help text
 *
 * Additive only: every column is nullable, so the five existing generic rows
 * keep working until the data migration adopts them.
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = 'notification_templates';

        if (! Schema::hasColumn($table, 'event_key')) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->string('event_key', 100)->nullable()->unique()->after('name');
            });
        }

        if (! Schema::hasColumn($table, 'description')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('description', 255)->nullable()->after('event_key');
            });
        }

        if (! Schema::hasColumn($table, 'channels')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->json('channels')->nullable()->after('body');
            });
        }

        if (! Schema::hasColumn($table, 'variables')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->json('variables')->nullable()->after('channels');
            });
        }
    }

    public function down(): void
    {
        $table = 'notification_templates';

        if (Schema::hasColumn($table, 'variables')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('variables');
            });
        }

        if (Schema::hasColumn($table, 'channels')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('channels');
            });
        }

        if (Schema::hasColumn($table, 'description')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('description');
            });
        }

        if (Schema::hasColumn($table, 'event_key')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropUnique(['event_key']);
                $blueprint->dropColumn('event_key');
            });
        }
    }
};
