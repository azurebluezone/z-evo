<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The slug column had no unique index. Keep the oldest row for each
        // duplicate and suffix the rest by id before enforcing uniqueness.
        $duplicates = DB::table('campaigns')
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug');

        foreach ($duplicates as $slug) {
            $rows = DB::table('campaigns')->where('slug', $slug)->orderBy('id')->get();
            $keep = true;

            foreach ($rows as $row) {
                if ($keep) {
                    $keep = false;
                    continue;
                }

                DB::table('campaigns')->where('id', $row->id)->update([
                    'slug' => $slug . '-' . $row->id,
                ]);
            }
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });
    }
};
