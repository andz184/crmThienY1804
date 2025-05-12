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
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('name')->comment('e.g., Large, Red, etc.'); // Variation name like "Size L, Color Red"
            $table->string('sku')->unique()->comment('Stock Keeping Unit, will be used as variation_id in orders table');
            $table->decimal('price', 10, 2)->comment('Specific price for this variation');
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            // $table->json('attributes')->nullable(); // For dynamic attributes like {"size": "XL", "color": "Blue"}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variations');
    }
};
