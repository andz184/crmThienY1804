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
        Schema::table('live_session_reports', function (Blueprint $table) {
            // Xóa cột cũ nếu có
            if (Schema::hasColumn('live_session_reports', 'session_name')) {
                $table->dropColumn('session_name');
            }
            if (Schema::hasColumn('live_session_reports', 'start_time')) {
                $table->dropColumn('start_time');
            }
            if (Schema::hasColumn('live_session_reports', 'end_time')) {
                $table->dropColumn('end_time');
            }

            // Thêm cột mới
            if (!Schema::hasColumn('live_session_reports', 'live_session_id')) {
                $table->integer('live_session_id')->after('id')->comment('ID phiên live (số phiên)');
            }
            if (!Schema::hasColumn('live_session_reports', 'session_date')) {
                $table->date('session_date')->after('live_session_id')->comment('Ngày diễn ra phiên live');
            }
            if (!Schema::hasColumn('live_session_reports', 'total_customers')) {
                $table->integer('total_customers')->default(0)->after('total_orders')->comment('Tổng số khách hàng tham gia mua');
            }
            if (!Schema::hasColumn('live_session_reports', 'top_products')) {
                $table->text('top_products')->nullable()->after('total_customers')->comment('JSON lưu trữ thông tin top sản phẩm bán chạy');
            }

            // Đảm bảo phiên live ID và ngày là duy nhất
            if (!Schema::hasColumn('live_session_reports', 'live_session_id') || !Schema::hasColumn('live_session_reports', 'session_date')) {
                $table->unique(['live_session_id', 'session_date'], 'live_session_date_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_session_reports', function (Blueprint $table) {
            // Loại bỏ các cột mới thêm nếu cần
            $table->dropIfExists('live_session_date_unique');

            if (Schema::hasColumn('live_session_reports', 'live_session_id')) {
                $table->dropColumn('live_session_id');
            }
            if (Schema::hasColumn('live_session_reports', 'session_date')) {
                $table->dropColumn('session_date');
            }
            if (Schema::hasColumn('live_session_reports', 'total_customers')) {
                $table->dropColumn('total_customers');
            }
            if (Schema::hasColumn('live_session_reports', 'top_products')) {
                $table->dropColumn('top_products');
            }

            // Khôi phục các cột cũ
            $table->string('session_name')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
        });
    }
};
