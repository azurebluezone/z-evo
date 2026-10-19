<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Geo-IP lookup cache.
 *
 * Every pageview triggers VisitorIdentify middleware, so without a cache the
 * app would hit MaxMind / the fallback HTTP drivers on *every single request*.
 * Resolving once per IP and keeping the answer here removes that cost and keeps
 * the dashboard's division/ISP breakdown stable (one resolution = one answer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_geo_cache', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ip', 64)->unique();
            $table->string('country', 100)->nullable()->index();
            $table->string('city', 100)->nullable()->index();
            $table->string('division', 60)->nullable()->index();
            $table->string('district', 100)->nullable()->index();
            $table->string('isp', 150)->nullable()->index();
            $table->string('latitude', 32)->nullable();
            $table->string('longitude', 32)->nullable();
            $table->string('timezone', 60)->nullable();

            // ok = resolved, null = provider answered but had nothing,
            // error = provider failed (worth retrying later).
            $table->string('lookup_status', 20)->default('ok')->index();
            $table->string('driver', 60)->nullable();

            $table->timestamp('last_lookup_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_geo_cache');
    }
};