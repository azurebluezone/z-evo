<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable copy for the public cookie page.
 *
 * The consent controls, the cookie table and the retention truth stay in code:
 * only the human-readable title, intro and body live here. That is deliberate -
 * an admin may explain the policy in their own words, but may not edit away the
 * fact that analytics data is kept for one year (App\Support\Consent::RETENTION_NOTICE).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cookie_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('intro')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_pages');
    }
};
