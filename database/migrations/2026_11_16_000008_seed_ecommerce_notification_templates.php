<?php

use App\Support\NotificationTemplateCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Put the canonical e-commerce notification templates in the database.
 *
 * This is a data migration, not a schema one: the notification-template
 * screen must work on a freshly migrated database before any seeder runs.
 * The catalog is shared with NotificationTemplateSeeder, and install() is
 * idempotent, so a live database with the old five generic rows keeps them
 * (adopted under their event keys) instead of getting duplicates.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_templates')
            || ! Schema::hasColumn('notification_templates', 'event_key')) {
            return;
        }

        NotificationTemplateCatalog::install();
    }

    public function down(): void
    {
        // Deliberately no-op: deleting rows an admin may have edited (or that
        // customers already received) is worse than leaving them behind.
        // Rolling back the schema drops event_key, not the content.
    }
};
