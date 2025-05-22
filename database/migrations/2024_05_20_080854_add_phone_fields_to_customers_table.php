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
        Schema::table('customers', function (Blueprint $table) {
            // Add phone field for backwards compatibility
            $table->string('phone')->nullable()->after('email');
            // Make sure phone_number exists (in case it's already there)
            if (!Schema::hasColumn('customers', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('phone');
            // Only drop if it exists
            if (Schema::hasColumn('customers', 'phone_number')) {
                $table->dropColumn('phone_number');
            }
        });
    }
};
