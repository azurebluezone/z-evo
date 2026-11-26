<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The forgot-password flow writes `customers.forgot` but no migration ever
 * created that column. On a real MySQL the first UPDATE raised
 * "Unknown column 'forgot' in 'field list'" (SQLSTATE 42S22), so the reset
 * path could never start. Guarded so a database that already carries the
 * column (older dumps) is left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('customers', 'forgot')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('forgot')->nullable()->after('verify');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('customers', 'forgot')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('forgot');
        });
    }
};
