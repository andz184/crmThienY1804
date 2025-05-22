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
            // Add location name columns if they don't exist
            if (!Schema::hasColumn('orders', 'province_name')) {
                $table->string('province_name')->nullable()->after('province_code');
            }
            
            if (!Schema::hasColumn('orders', 'district_name')) {
                $table->string('district_name')->nullable()->after('district_code');
            }
            
            if (!Schema::hasColumn('orders', 'ward_name')) {
                $table->string('ward_name')->nullable()->after('ward_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Remove location name columns if they exist
            if (Schema::hasColumn('orders', 'province_name')) {
                $table->dropColumn('province_name');
            }
            
            if (Schema::hasColumn('orders', 'district_name')) {
                $table->dropColumn('district_name');
            }
            
            if (Schema::hasColumn('orders', 'ward_name')) {
                $table->dropColumn('ward_name');
            }
        });
    }
};
