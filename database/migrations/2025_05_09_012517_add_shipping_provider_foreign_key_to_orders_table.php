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
            $table->unsignedBigInteger('shipping_provider_id')->nullable()->after('warehouse_id');
            $table->foreign('shipping_provider_id')->references('id')->on('shipping_providers')->onDelete('set null');

            // Drop the old column if it exists and you are ready to replace it
            if (Schema::hasColumn('orders', 'shipping_provider')) {
                $table->dropColumn('shipping_provider');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_provider_id']);
            $table->dropColumn('shipping_provider_id');

            // Add back the old column if you need to revert
            $table->string('shipping_provider')->nullable()->after('payment_method');
        });
    }
};
