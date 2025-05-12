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
        Schema::create('daily_revenue_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('summary_date')->unique()->comment('Ngày thực hiện thống kê doanh thu');
            $table->decimal('total_revenue', 15, 2)->default(0.00)->comment('Tổng doanh thu trong ngày');
            $table->unsignedInteger('total_orders')->default(0)->comment('Tổng số đơn hàng thành công tính vào doanh thu');
            $table->unsignedInteger('total_quantity_sold')->default(0)->comment('Tổng số lượng sản phẩm bán ra trong các đơn thành công');
            // $table->decimal('average_order_value', 15, 2)->default(0.00);
            $table->timestamps(); // Để theo dõi khi record summary được tạo/cập nhật
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_revenue_summaries');
    }
};
