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
        // Kiểm tra xem bảng đã tồn tại chưa
        if (!Schema::hasTable('live_session_reports')) {
            Schema::create('live_session_reports', function (Blueprint $table) {
                $table->id();
                $table->integer('live_session_id')->unique()->comment('ID phiên live (số phiên)');
                $table->date('session_date')->comment('Ngày diễn ra phiên live');
                $table->integer('total_orders')->default(0)->comment('Tổng số đơn hàng trong phiên live');
                $table->decimal('total_revenue', 15, 2)->default(0)->comment('Tổng doanh thu của phiên live');
                $table->integer('total_customers')->default(0)->comment('Tổng số khách hàng tham gia mua');
                $table->text('top_products')->nullable()->comment('JSON lưu trữ thông tin top sản phẩm bán chạy');
                $table->text('notes')->nullable()->comment('Ghi chú về phiên live');
                $table->timestamps();

                // Đảm bảo phiên live ID và ngày là duy nhất
                $table->unique(['live_session_id', 'session_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không xóa bảng, vì có thể được tạo bởi migration khác
        // Schema::dropIfExists('live_session_reports');
    }
};
