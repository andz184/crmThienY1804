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
        Schema::create('pancake_shops', function (Blueprint $table) {
            $table->id(); // Internal auto-incrementing ID
            $table->bigInteger('pancake_id')->unique()->comment('Shop ID from Pancake API');
            $table->string('name');
            $table->string('avatar_url')->nullable();
            $table->json('raw_data')->nullable()->comment('Full shop data from Pancake API');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pancake_shops');
    }
};
