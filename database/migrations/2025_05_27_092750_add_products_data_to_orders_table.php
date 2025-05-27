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
        Schema::table('orders', function (Blueprint $table) {
            // products_data structure:
            // [
            //     'added_to_cart_quantity' => int,
            //     'components' => mixed,
            //     'composite_item_id' => mixed,
            //     'discount_each_product' => int,
            //     'exchange_unit' => int,
            //     'id' => string,
            //     'is_bonus_product' => boolean,
            //     'is_canceled' => boolean,
            //     'is_discount_percent' => boolean,
            //     'is_wholesale' => boolean,
            //     'measure_group_id' => mixed,
            //     'note' => mixed,
            //     'product_id' => string,
            //     'quantity' => int,
            //     'return_quantity' => int,
            //     'returned_count' => int,
            //     'status' => int,
            //     'total_discount' => int,
            //     'variation_id' => string,
            //     'variation_info' => array,
            //     // ... other fields
            // ]
            $table->longText('products_data')->nullable()->after('live_session_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('products_data');
        });
    }
};
