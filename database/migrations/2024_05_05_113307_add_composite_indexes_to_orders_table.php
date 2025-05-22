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
            // Thêm chỉ mục phức hợp để tối ưu filter/group by trên nhiều cột
            $table->index(['user_id', 'status', 'created_at'], 'idx_orders_user_status_created');
            $table->index(['status', 'created_at'], 'idx_orders_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Xóa chỉ mục khi rollback
            $table->dropIndex('idx_orders_user_status_created');
            $table->dropIndex('idx_orders_status_created');
        });
    }
};
