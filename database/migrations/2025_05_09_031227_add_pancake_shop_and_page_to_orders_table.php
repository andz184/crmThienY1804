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
            if (!Schema::hasColumn('orders', 'pancake_shop_id')) {
                $table->foreignId('pancake_shop_id')->nullable()->constrained('pancake_shops')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'pancake_page_id')) {
                $table->foreignId('pancake_page_id')->nullable()->constrained('pancake_pages')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['pancake_shop_id']);
            $table->dropForeign(['pancake_page_id']);
            $table->dropColumn(['pancake_shop_id', 'pancake_page_id']);
        });
    }
};
