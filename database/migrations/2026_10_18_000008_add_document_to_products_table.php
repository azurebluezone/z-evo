<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `products.document` was written by the admin product form but the column was
 * never created.
 *
 * ProductController@store sets $input['document'] = $docRelativePath as soon as
 * a document is uploaded (line ~295) and then calls Product::create($input)
 * (line ~300). @update does the same at ~657, and the rollback path issues
 * Product::where('id', ...)->update(['document' => null]) at ~586. The form
 * offers a `document` file input and validates it (pdf/doc/xls/... up to 20MB),
 * so the feature is intended - only the storage was missing.
 *
 * The effect was a hard failure on the happy path: creating a product WITH a
 * document raised SQLSTATE[42S22] "Unknown column 'document'" and the whole
 * transaction rolled back, while creating one WITHOUT a document worked fine.
 * That is the worst shape a bug can take - it only breaks for the people using
 * the feature.
 *
 * Added as nullable so existing rows and the no-document path are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'document')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('document', 255)->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'document')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('document');
            });
        }
    }
};
