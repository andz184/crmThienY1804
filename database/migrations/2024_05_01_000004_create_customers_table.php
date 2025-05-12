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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique()->comment('Primary key for linking to orders initially');
            $table->string('email')->nullable()->unique();

            $table->string('full_address')->nullable()->comment('Last known full address from an order');
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('ward')->nullable();
            $table->string('street_address')->nullable();

            $table->date('first_order_date')->nullable();
            $table->date('last_order_date')->nullable();
            $table->unsignedInteger('total_orders_count')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0.00);

            $table->text('notes')->nullable()->comment('Internal notes about the customer');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
