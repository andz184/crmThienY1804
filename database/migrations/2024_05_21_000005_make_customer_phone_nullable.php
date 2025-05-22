<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // We'll use a direct SQL query to modify the column constraint
        // This is more reliable than using Blueprint for changing existing columns
        DB::statement('ALTER TABLE orders MODIFY customer_phone VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't revert this change as it could cause data loss
    }
};
