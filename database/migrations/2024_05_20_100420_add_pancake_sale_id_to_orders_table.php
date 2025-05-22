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
            // First check if sale_id exists
            if (!Schema::hasColumn('orders', 'sale_id')) {
                $table->unsignedBigInteger('sale_id')->nullable();
                $table->foreign('sale_id')->references('id')->on('users')->onDelete('set null');
            }

            // Now add pancake_sale_id
            $table->string('pancake_sale_id')->nullable();
            $table->index('pancake_sale_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['pancake_sale_id']);
            $table->dropColumn('pancake_sale_id');

            // We won't remove sale_id as it might be used elsewhere
        });
    }
};
