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
            // Add product_code column if it doesn't exist
            if (!Schema::hasColumn('order_items', 'product_code')) {
                $table->string('product_code')->nullable()->after('product_name');
            }

            // Add product_name column if it doesn't exist
            if (!Schema::hasColumn('order_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('order_id');
            }

            // Add pancake_variant_id column if it doesn't exist
            if (!Schema::hasColumn('order_items', 'pancake_variant_id')) {
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
            // We don't remove columns in down method to prevent data loss
        });
    }
};
