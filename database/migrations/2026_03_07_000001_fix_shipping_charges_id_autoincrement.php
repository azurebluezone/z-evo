<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('shipping_charges')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE `shipping_charges` MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE `shipping_charges` ADD PRIMARY KEY (`id`)');
        } catch (\Throwable $e) {
        }
    }

    public function down()
    {
        // Intentionally left blank.
    }
};
