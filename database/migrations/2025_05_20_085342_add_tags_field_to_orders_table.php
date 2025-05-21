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
            // Add tags column for order categorization
            if (!Schema::hasColumn('orders', 'tags')) {
                $table->text('tags')->nullable()->after('returned_reason');
            }

            // Add pancake warehouse and shipping provider IDs for better mapping
            if (!Schema::hasColumn('orders', 'pancake_warehouse_id')) {
                $table->string('pancake_warehouse_id')->nullable()->after('warehouse_id');
            }

            if (!Schema::hasColumn('orders', 'pancake_shipping_provider_id')) {
                $table->string('pancake_shipping_provider_id')->nullable()->after('shipping_provider_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'tags')) {
                $table->dropColumn('tags');
            }

            if (Schema::hasColumn('orders', 'pancake_warehouse_id')) {
                $table->dropColumn('pancake_warehouse_id');
            }

            if (Schema::hasColumn('orders', 'pancake_shipping_provider_id')) {
                $table->dropColumn('pancake_shipping_provider_id');
            }
        });
    }
};
