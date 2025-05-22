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
            // Ensure all potentially missing columns exist
            if (!Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('orders', 'tracking_code')) {
                $table->string('tracking_code')->nullable();
            }
            if (!Schema::hasColumn('orders', 'tracking_url')) {
                $table->string('tracking_url')->nullable();
            }
            if (!Schema::hasColumn('orders', 'sale_id')) {
                $table->unsignedBigInteger('sale_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'pancake_sale_id')) {
                $table->string('pancake_sale_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'province_code')) {
                $table->string('province_code')->nullable();
            }
            if (!Schema::hasColumn('orders', 'district_code')) {
                $table->string('district_code')->nullable();
            }
            if (!Schema::hasColumn('orders', 'ward_code')) {
                $table->string('ward_code')->nullable();
            }
            if (!Schema::hasColumn('orders', 'full_address')) {
                $table->text('full_address')->nullable();
            }
            if (!Schema::hasColumn('orders', 'street_address')) {
                $table->text('street_address')->nullable();
            }
            if (!Schema::hasColumn('orders', 'pancake_shop_id')) {
                $table->unsignedBigInteger('pancake_shop_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'pancake_page_id')) {
                $table->unsignedBigInteger('pancake_page_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to drop columns in down method
        // It's safer to leave them in case of data loss
    }
};
