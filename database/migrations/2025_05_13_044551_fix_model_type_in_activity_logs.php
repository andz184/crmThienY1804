<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Thêm cột model_type nếu chưa tồn tại
        if (!Schema::hasColumn('activity_logs', 'model_type')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->text('model_type')->nullable()->after('module');
            });
        }

        // Cập nhật dữ liệu cũ
        DB::statement("UPDATE activity_logs SET model_type = 'App\\\\Models\\\\Order' WHERE module = 'Order'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('activity_logs', 'model_type')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropColumn('model_type');
            });
        }
    }
};
