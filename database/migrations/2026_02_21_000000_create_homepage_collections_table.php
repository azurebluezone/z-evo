<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('homepage_collections')) {
            Schema::create('homepage_collections', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->unsignedInteger('category_id')->nullable()->index();
                $table->string('banner_image')->nullable();
                $table->unsignedInteger('product_limit')->default(12);
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_collections');
    }
};
