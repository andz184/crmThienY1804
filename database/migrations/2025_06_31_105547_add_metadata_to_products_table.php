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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'metadata')) {
                // Add the metadata column, type json is generally best for storing structured data.
                // Make it nullable if not all products will have metadata.
                // Place it after 'pancake_id' or another relevant column.
                $table->json('metadata')->nullable()->after('pancake_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
