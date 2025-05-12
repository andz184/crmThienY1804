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
        Schema::dropIfExists('product_variations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To be able to roll back, you'd redefine the table structure here.
        // For a permanent removal, this can be left empty.
        /*
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            // Add other original columns like name, price, stock etc.
            $table->timestamps();
        });
        */
    }
};
