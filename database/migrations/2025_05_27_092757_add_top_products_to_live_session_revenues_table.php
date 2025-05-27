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
        Schema::table('live_session_revenues', function (Blueprint $table) {
            $table->json('top_products')->nullable()->after('orders_by_province');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_session_revenues', function (Blueprint $table) {
            $table->dropColumn('top_products');
        });
    }
};
