<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pancake_category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pancake_category_id')->constrained('pancake_categories')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->timestamps();

            // Add unique constraint to prevent duplicate relationships
            $table->unique(['pancake_category_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pancake_category_product');
    }
};
