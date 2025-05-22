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
        Schema::create('payment_reports', function (Blueprint $table) {
            $table->id();
            $table->string('payment_method');
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->decimal('average_order_value', 15, 2)->default(0);
            $table->decimal('completed_revenue', 15, 2)->default(0);
            $table->integer('completed_orders')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->date('report_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Ensure uniqueness - only one record per payment method per day
            $table->unique(['payment_method', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reports');
    }
};
