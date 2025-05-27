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
        Schema::create('variant_revenues', function (Blueprint $table) {
            $table->id();
            $table->string('category_id');
            $table->date('order_date');
            $table->integer('total_orders')->default(0);
            $table->integer('total_quantity')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Composite unique key to prevent duplicates
            $table->unique(['category_id', 'order_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_revenues');
    }
};
