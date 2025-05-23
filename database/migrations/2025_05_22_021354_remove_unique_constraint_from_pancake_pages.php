<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove the unique constraint from pancake_page_id
     */
    public function up(): void
    {
        Schema::table('pancake_pages', function (Blueprint $table) {
            // Drop the unique constraint on pancake_page_id
            $table->dropUnique(['pancake_page_id']);
        });
    }

    /**
     * Reverse the migrations.
     * Add back the unique constraint if needed
     */
    public function down(): void
    {
        Schema::table('pancake_pages', function (Blueprint $table) {
            // Add back the unique constraint
            $table->unique('pancake_page_id');
        });
    }
};
