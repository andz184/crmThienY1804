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
            // ID from your local pancake_shops table
            $table->foreignId('pancake_shop_id')->nullable()->after('warehouse_id')->constrained('pancake_shops')->onDelete('set null');
            // ID from your local pancake_pages table
            $table->foreignId('pancake_page_id')->nullable()->after('pancake_shop_id')->constrained('pancake_pages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pancake_shop_id']);
            $table->dropColumn('pancake_shop_id');
            $table->dropForeign(['pancake_page_id']);
            $table->dropColumn('pancake_page_id');
        });
    }
};
