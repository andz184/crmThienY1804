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
        // Drop the unique constraint on the email column
        Schema::table('customers', function (Blueprint $table) {
            // First check if the index exists before trying to drop it
            if(Schema::hasIndex('customers', 'customers_email_unique')) {
                $table->dropUnique('customers_email_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the unique constraint
        Schema::table('customers', function (Blueprint $table) {
            // We won't add the unique constraint back to avoid potential data issues
            // Instead, application logic will handle email uniqueness
        });
    }
};
