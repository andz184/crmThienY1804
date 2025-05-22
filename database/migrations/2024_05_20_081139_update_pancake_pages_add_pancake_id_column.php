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
        Schema::table('pancake_pages', function (Blueprint $table) {
            // Check if the column already exists
            if (!Schema::hasColumn('pancake_pages', 'pancake_id')) {
                // Add the column - using string as the IDs from Pancake appear to be large numbers
                $table->string('pancake_id')->nullable()->after('id');
            }

            // Ensure pancake_page_id exists for backward compatibility
            if (!Schema::hasColumn('pancake_pages', 'pancake_page_id')) {
                $table->string('pancake_page_id')->nullable()->after('pancake_id');
            }

            // Create an index on both fields for faster lookups
            $table->index(['pancake_id']);
            $table->index(['pancake_page_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pancake_pages', function (Blueprint $table) {
            // Drop the indexes first
            $table->dropIndex(['pancake_id']);
            $table->dropIndex(['pancake_page_id']);

            // Only drop the columns if they exist
            if (Schema::hasColumn('pancake_pages', 'pancake_id')) {
                $table->dropColumn('pancake_id');
            }
        });
    }
};
