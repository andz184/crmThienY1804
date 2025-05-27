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
            if (!Schema::hasColumn('order_items', 'pancake_product_id')) {
                $table->string('pancake_product_id')->nullable()->after('order_id');
            }
            if (!Schema::hasColumn('order_items', 'product_code')) {
                $table->string('product_code')->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('order_items', 'code')) {
                $table->string('code')->nullable()->after('product_code');
            }
            if (!Schema::hasColumn('order_items', 'pancake_variant_id')) {
                $table->string('pancake_variant_id')->nullable()->after('code');
            }
            if (!Schema::hasColumn('order_items', 'pancake_variation_id')) {
                $table->string('pancake_variation_id')->nullable()->after('pancake_variant_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'pancake_product_id',
                'product_code',
                'code',
                'pancake_variant_id',
                'pancake_variation_id'
            ]);
        });
    }
};
