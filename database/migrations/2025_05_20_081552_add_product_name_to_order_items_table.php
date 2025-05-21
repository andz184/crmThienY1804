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
            // Make 'code' nullable if it exists and is not already nullable
            if (Schema::hasColumn('order_items', 'code')) {
                $table->string('code')->nullable()->change();
            }

            // Add product_name if it doesn't exist
            if (!Schema::hasColumn('order_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('order_id');
            }

            // Add product_code if it doesn't exist
            if (!Schema::hasColumn('order_items', 'product_code') && !Schema::hasColumn('order_items', 'code')) {
                $table->string('product_code')->nullable()->after(Schema::hasColumn('order_items', 'product_name') ? 'product_name' : 'order_id');
            }

            // Add pancake_variant_id if it doesn't exist
            if (!Schema::hasColumn('order_items', 'pancake_variant_id') && !Schema::hasColumn('order_items', 'pancake_variation_id')) {
                $table->string('pancake_variant_id')->nullable()->after('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Only drop columns that were actually added in this migration
            if (Schema::hasColumn('order_items', 'product_name')) {
                $table->dropColumn('product_name');
            }

            if (Schema::hasColumn('order_items', 'product_code') && !Schema::hasColumn('order_items', 'code')) {
                $table->dropColumn('product_code');
            }

            if (Schema::hasColumn('order_items', 'pancake_variant_id') && !Schema::hasColumn('order_items', 'pancake_variation_id')) {
                $table->dropColumn('pancake_variant_id');
            }
        });
    }
};
