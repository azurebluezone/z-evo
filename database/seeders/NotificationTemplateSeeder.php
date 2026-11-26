<?php

namespace Database\Seeders;

use App\Support\NotificationTemplateCatalog;
use Illuminate\Database\Seeder;

/**
 * Event-keyed e-commerce notification templates.
 *
 * install() inserts missing rows, adopts the five legacy generic rows and
 * never overwrites an admin edit. The data migration calls the same routine,
 * so `migrate` and `migrate:fresh --seed` converge on the same catalog.
 */
class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $stats = NotificationTemplateCatalog::install();

        $this->command?->info(sprintf(
            'Notification templates: %d inserted, %d legacy adopted, %d already present.',
            $stats['inserted'],
            $stats['adopted'],
            $stats['present']
        ));
    }
}
