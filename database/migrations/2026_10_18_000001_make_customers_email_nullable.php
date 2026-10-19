<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make customers.email nullable.
 *
 * WHY
 * Guest checkout has no email step - the form collects name, phone, address
 * and area only. order_save() auto-creates a Customer for a guest so the order
 * can hang off a real account, and it did not set an email. Because
 * customers.email was varchar(55) NOT NULL with no default, that INSERT failed
 * with
 *
 *     SQLSTATE[HY000] 1364 Field 'email' doesn't have a default value
 *
 * which means EVERY guest checkout returned HTTP 500 and no order was created.
 * Verified live against this database before the migration.
 *
 * WHY NULLABLE RATHER THAN A SYNTHETIC EMAIL
 * The alternative is storing something like guest+01712345678@example.invalid.
 * That is worse than null in every way:
 *   - it puts an undeliverable address in a column other code treats as real
 *     (Admin\CustomerManageController edits it, UserSubmissionBulkActionController
 *     matches on it, the review screen prints it),
 *   - it is a lookup key, so a later real registration on the same phone would
 *     collide with it,
 *   - it implies an address exists when none was ever given.
 * A guest order genuinely has no email, so NULL is the truthful value. A guest
 * who later registers or fills in their profile simply supplies one.
 *
 * Every existing reader of ->email in the codebase is already null-safe
 * ($x->email ?? null, ternary, or 'N / A' fallbacks), so nothing else changes.
 *
 * DOWN restores NOT NULL. It fails loudly if guests have since registered
 * without an address, which is the correct behaviour - the operator has to
 * decide what to do with those rows rather than have the database invent a
 * value for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('email', 55)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('email', 55)->nullable(false)->change();
        });
    }
};
