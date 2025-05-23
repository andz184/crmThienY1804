<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Change total_value to DECIMAL(15, 2) to accommodate larger values
            $table->decimal('total_value', 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert total_value back to DECIMAL(10, 2) if needed
            $table->decimal('total_value', 10, 2)->default(0)->change();
        });
    }
}; 