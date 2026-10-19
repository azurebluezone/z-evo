<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the `password_g` flag to sms_gateways.
 *
 * WHY
 * The same gap as 2026_10_18_000003 (`order` / `forget_pass`), found the hard
 * way: the admin template backEnd/apiintegration/sms_manage.blade.php renders a
 * "Password Generator" switch bound to `$sms->password_g`, and
 * ApiIntegrationController::sms_update() wrote that key into the model. With no
 * such column the UPDATE would have failed with
 *     SQLSTATE[42S22]: Unknown column 'password_g'
 * and the switch could never persist - exactly the "template describes a schema
 * the database never received" pattern this project keeps hitting.
 *
 * Defaults to 0 = off, so no existing gateway is treated as password-capable by
 * default. Nothing reads the flag yet: it is stored so the admin screen stops
 * lying, and is there for the day the temp-password SMS path is wired up.
 *
 * DOWN drops it, which discards that setting.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sms_gateways')) {
            return;
        }

        if (Schema::hasColumn('sms_gateways', 'password_g')) {
            return;                 // already added by hand on this install
        }

        Schema::table('sms_gateways', function (Blueprint $table) {
            $table->tinyInteger('password_g')->default(0)->after('forget_pass');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sms_gateways')) {
            return;
        }

        if (! Schema::hasColumn('sms_gateways', 'password_g')) {
            return;
        }

        Schema::table('sms_gateways', function (Blueprint $table) {
            $table->dropColumn('password_g');
        });
    }
};
