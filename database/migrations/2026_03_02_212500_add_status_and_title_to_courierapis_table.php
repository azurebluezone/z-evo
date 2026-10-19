<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('courierapis', function (Blueprint $table) {
            if (!Schema::hasColumn('courierapis', 'status')) {
                $table->boolean('status')->default(true)->after('type');
            }
            if (!Schema::hasColumn('courierapis', 'title')) {
                $table->string('title', 100)->nullable()->after('type');
            }
        });
    }

    public function down()
    {
        Schema::table('courierapis', function (Blueprint $table) {
            if (Schema::hasColumn('courierapis', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('courierapis', 'title')) {
                $table->dropColumn('title');
            }
        });
    }
};
