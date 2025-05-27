<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('live_session_stats', function (Blueprint $table) {
            $table->id();
            $table->string('live_session_id');
            $table->date('live_session_date');
            $table->string('live_number');
            $table->string('session_name');

            // Order stats
            $table->integer('total_orders')->default(0);
            $table->integer('successful_orders')->default(0);
            $table->integer('canceled_orders')->default(0);
            $table->integer('delivering_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);

            // Rate stats
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('cancellation_rate', 5, 2)->default(0);
            $table->decimal('delivering_rate', 5, 2)->default(0);

            // Customer stats
            $table->integer('total_customers')->default(0);
            $table->integer('new_customers')->default(0);
            $table->integer('returning_customers')->default(0);

            // Product stats
            $table->json('top_products')->nullable();
            $table->json('orders_by_status')->nullable();

            $table->timestamp('last_calculated_at');
            $table->timestamps();

            // Composite unique key
            $table->unique(['live_session_id', 'live_session_date']);

            // Indexes for common queries
            $table->index('live_session_date');
            $table->index('live_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('live_session_stats');
    }
};
