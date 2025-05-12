<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pancake_pages', function (Blueprint $table) {
            $table->id(); // Internal auto-incrementing ID

            $table->unsignedBigInteger('pancake_shop_table_id')->comment('FK to pancake_shops.id');
            $table->foreign('pancake_shop_table_id')->references('id')->on('pancake_shops')->onDelete('cascade');

            $table->string('pancake_page_id')->unique()->comment('Page ID from Pancake API (usually platform page ID)');
            $table->string('name');
            $table->string('platform')->nullable();
            $table->json('settings')->nullable()->comment('Page settings from Pancake API');
            $table->json('raw_data')->nullable()->comment('Full page data from Pancake API');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pancake_pages');
    }
};
