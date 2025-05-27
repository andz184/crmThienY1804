<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_session_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('live_session_id');
            $table->date('session_date');
            $table->integer('total_orders')->default(0);
            $table->integer('successful_orders')->default(0);
            $table->integer('canceled_orders')->default(0);
            $table->integer('delivering_orders')->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->integer('total_customers')->default(0);
            $table->longText('customer_ids')->nullable();
            $table->longText('product_stats')->nullable();

            // Dữ liệu cho biểu đồ
            $table->longText('daily_stats')->nullable(); // Thống kê theo ngày
            $table->longText('hourly_stats')->nullable(); // Thống kê theo giờ
            $table->longText('product_chart_data')->nullable(); // Dữ liệu biểu đồ sản phẩm
            $table->longText('status_chart_data')->nullable(); // Dữ liệu biểu đồ trạng thái
            $table->longText('revenue_chart_data')->nullable(); // Dữ liệu biểu đồ doanh thu

            $table->timestamp('last_order_date')->nullable();
            $table->timestamp('last_aggregated_at')->useCurrent();
            $table->timestamps();

            $table->unique(['live_session_id', 'session_date']);
            $table->index('session_date');
            $table->index('last_order_date');
            $table->index('last_aggregated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_session_aggregates');
    }
};
