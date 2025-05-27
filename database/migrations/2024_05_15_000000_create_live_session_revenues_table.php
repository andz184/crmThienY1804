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
        Schema::create('live_session_revenues', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('live_number');
            $table->string('session_name')->nullable();
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->integer('successful_orders')->default(0);
            $table->integer('canceled_orders')->default(0);
            $table->integer('delivering_orders')->default(0);
            $table->integer('total_customers')->default(0);
            $table->integer('new_customers')->default(0);
            $table->integer('returning_customers')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('cancellation_rate', 5, 2)->default(0);
            $table->json('top_products')->nullable();
            $table->json('orders_by_status')->nullable();
            $table->json('orders_by_province')->nullable();
            $table->timestamps();

            $table->unique(['date', 'live_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_session_revenues');
    }
};
