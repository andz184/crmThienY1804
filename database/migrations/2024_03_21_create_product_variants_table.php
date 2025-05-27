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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->string('pancake_variant_id')->unique()->index();
            $table->string('pancake_product_id')->index();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->json('category_ids')->nullable();
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Create a pivot table for order items and variants
        Schema::create('order_item_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id');
            $table->string('pancake_variant_id');
            $table->integer('quantity');
            $table->decimal('price', 15, 2);
            $table->json('variant_data')->nullable();
            $table->timestamps();

            $table->foreign('order_item_id')
                ->references('id')
                ->on('order_items')
                ->onDelete('cascade');

            $table->foreign('pancake_variant_id')
                ->references('pancake_variant_id')
                ->on('product_variants')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_variants');
        Schema::dropIfExists('product_variants');
    }
};
