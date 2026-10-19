<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors_master', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('visitor_id')->unique();
            $table->string('ip', 64)->index();
            $table->text('user_agent')->nullable();
            $table->string('country', 100)->nullable()->index();
            $table->string('city', 100)->nullable()->index();
            $table->string('isp', 150)->nullable()->index();
            $table->unsignedTinyInteger('status')->default(1)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors_master');
    }
};
