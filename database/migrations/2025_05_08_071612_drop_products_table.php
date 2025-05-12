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
        Schema::dropIfExists('products');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To be able to roll back, you'd redefine the table structure here.
        // For a permanent removal, this can be left empty.
        /*
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Add other original columns like name, description, category_id etc.
            $table->timestamps();
            $table->softDeletes();
        });
        */
    }
};
