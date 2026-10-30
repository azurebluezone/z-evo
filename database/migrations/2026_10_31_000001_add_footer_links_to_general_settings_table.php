<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront footer's link columns, editable from Website Setup -> Footer.
 *
 * One JSON column instead of a table because the footer is a display list,
 * not a relational entity: rows are {group, label, url, new_tab} and every
 * read is "give me the whole footer" - the same shape header_links already
 * uses on this table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('general_settings', 'footer_links')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->json('footer_links')->nullable()->after('footer_payment_image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('general_settings', 'footer_links')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('footer_links');
            });
        }
    }
};
