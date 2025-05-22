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
        Schema::table('shipping_providers', function (Blueprint $table) {
            // Add code field if it doesn't exist
            if (!Schema::hasColumn('shipping_providers', 'code')) {
                $table->string('code')->nullable()->after('name');
            }

            // Add description field if it doesn't exist
            if (!Schema::hasColumn('shipping_providers', 'description')) {
                $table->text('description')->nullable()->after('code');
            }

            // Add is_active field if it doesn't exist
            if (!Schema::hasColumn('shipping_providers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_providers', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_providers', 'code')) {
                $table->dropColumn('code');
            }

            if (Schema::hasColumn('shipping_providers', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('shipping_providers', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
