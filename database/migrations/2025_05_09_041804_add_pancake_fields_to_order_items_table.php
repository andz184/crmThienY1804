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
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('pancake_product_id')->nullable()->after('quantity');
            $table->string('pancake_variation_id')->nullable()->after('pancake_product_id');
            $table->string('name')->nullable()->after('pancake_variation_id');
            $table->decimal('price', 15, 2)->nullable()->after('name');
            $table->decimal('weight', 10, 3)->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['pancake_product_id', 'pancake_variation_id', 'name', 'price', 'weight']);
        });
    }
};
