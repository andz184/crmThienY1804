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
            if (!Schema::hasColumn('orders', 'shipping_provider_id')) {
                $table->unsignedBigInteger('shipping_provider_id')->nullable();
                $table->foreign('shipping_provider_id')->references('id')->on('shipping_providers')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipping_provider_id')) {
                $table->dropForeign(['shipping_provider_id']);
                $table->dropColumn('shipping_provider_id');
            }
        });
    }
}; 