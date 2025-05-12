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
        Schema::create('daily_revenue_aggregates', function (Blueprint $table) {
            $table->id();
            $table->date('aggregation_date');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->unsignedInteger('completed_orders_count')->default(0);
            $table->timestamps();

            $table->unique(['aggregation_date', 'user_id']);
            $table->index('aggregation_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_revenue_aggregates');
    }
};
