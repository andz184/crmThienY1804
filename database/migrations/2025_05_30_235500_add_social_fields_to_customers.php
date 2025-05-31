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
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'social_type')) {
                $table->string('social_type')->nullable()->after('email');
            }
            if (!Schema::hasColumn('customers', 'social_id')) {
                $table->string('social_id')->nullable()->after('social_type');
            }
            if (!Schema::hasColumn('customers', 'social_info')) {
                $table->json('social_info')->nullable()->after('social_id');
            }
            // Đảm bảo fb_id tồn tại
            if (!Schema::hasColumn('customers', 'fb_id')) {
                $table->string('fb_id')->nullable()->after('social_info');
            }
            // Thêm các mạng xã hội khác
            if (!Schema::hasColumn('customers', 'zalo_id')) {
                $table->string('zalo_id')->nullable()->after('fb_id');
            }
            if (!Schema::hasColumn('customers', 'telegram_id')) {
                $table->string('telegram_id')->nullable()->after('zalo_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $columns = [
                'social_type',
                'social_id',
                'social_info',
                'fb_id',
                'zalo_id',
                'telegram_id'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
