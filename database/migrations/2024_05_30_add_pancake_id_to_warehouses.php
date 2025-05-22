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
        Schema::table('warehouses', function (Blueprint $table) {
            // Add pancake_id column if it doesn't exist
            if (!Schema::hasColumn('warehouses', 'pancake_id')) {
                $table->string('pancake_id')->nullable()->after('code')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'pancake_id')) {
                $table->dropColumn('pancake_id');
            }
        });
    }
};
