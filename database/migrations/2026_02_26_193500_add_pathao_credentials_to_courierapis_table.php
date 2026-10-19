<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('courierapis', function (Blueprint $table) {
            $table->string('client_id', 155)->nullable()->after('token');
            $table->string('client_secret', 155)->nullable()->after('client_id');
            $table->string('username', 155)->nullable()->after('client_secret');
            $table->string('password', 155)->nullable()->after('username');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courierapis', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'client_secret', 'username', 'password']);
        });
    }
};
