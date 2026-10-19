<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('order_details', 'variant_id')) {
                $table->unsignedBigInteger('variant_id')->nullable()->index()->after('product_id');
            }
        });

        // Backfill variant_id using legacy marker in product_size: "variant_id:923"
        // Only run the backfill when both `variant_id` and `product_size` exist.
        if (Schema::hasColumn('order_details', 'variant_id') && Schema::hasColumn('order_details', 'product_size')) {
            $rows = DB::table('order_details')
                ->select('id', 'product_id', 'product_size')
                ->whereNull('variant_id')
                ->whereNotNull('product_size')
                ->get();

            foreach ($rows as $r) {
                $sizeMarker = (string) ($r->product_size ?? '');
                if ($sizeMarker !== '' && preg_match('/\bvariant_id\s*:\s*(\d+)\b/i', $sizeMarker, $m)) {
                    $candidateId = (int) $m[1];
                    $exists = DB::table('product_variants')
                        ->where('id', $candidateId)
                        ->where('product_id', (int) $r->product_id)
                        ->exists();
                    if ($exists) {
                        DB::table('order_details')->where('id', (int) $r->id)->update([
                            'variant_id' => $candidateId,
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            if (Schema::hasColumn('order_details', 'variant_id')) {
                $table->dropIndex(['variant_id']);
                $table->dropColumn('variant_id');
            }
        });
    }
};
