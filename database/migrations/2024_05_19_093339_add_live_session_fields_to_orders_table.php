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
        Schema::table('orders', function (Blueprint $table) {
            // Thêm cột live_session_id và live_session_date nếu chưa tồn tại
            if (!Schema::hasColumn('orders', 'live_session_id')) {
                // Check if campaign_id exists to determine the position
                if (Schema::hasColumn('orders', 'campaign_id')) {
                    $table->integer('live_session_id')->nullable()->after('campaign_id')->comment('ID phiên live (từ ghi chú)');
                } else {
                    // If campaign_id doesn't exist, add it after user_id
                    $table->integer('live_session_id')->nullable()->after('user_id')->comment('ID phiên live (từ ghi chú)');
                }
            }

            if (!Schema::hasColumn('orders', 'live_session_date')) {
                $table->date('live_session_date')->nullable()->after('live_session_id')->comment('Ngày diễn ra phiên live');
            }

            // Thêm index cho các trường live_session
            if (Schema::hasColumn('orders', 'live_session_id') && Schema::hasColumn('orders', 'live_session_date')) {
                $table->index(['live_session_id', 'live_session_date'], 'idx_orders_live_session');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Xóa index trước
            if (Schema::hasColumn('orders', 'live_session_id') && Schema::hasColumn('orders', 'live_session_date')) {
                $table->dropIndex('idx_orders_live_session');
            }

            // Xóa các cột
            if (Schema::hasColumn('orders', 'live_session_id')) {
                $table->dropColumn('live_session_id');
            }

            if (Schema::hasColumn('orders', 'live_session_date')) {
                $table->dropColumn('live_session_date');
            }
        });
    }
};
