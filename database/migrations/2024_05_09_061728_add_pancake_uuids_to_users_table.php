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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('pancake_uuid')->nullable()->after('remember_token')->comment('Pancake User UUID for assigning_seller_id');
            $table->uuid('pancake_care_uuid')->nullable()->after('pancake_uuid')->comment('Pancake User UUID for assigning_care_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pancake_uuid', 'pancake_care_uuid']);
        });
    }
};
