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
        Schema::create('pancake_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->integer('status_code')->unique()->comment('Mã trạng thái đơn hàng từ Pancake');
            $table->string('name')->comment('Tên trạng thái bằng tiếng Việt');
            $table->string('api_name')->nullable()->comment('Tên trạng thái gốc từ API (ví dụ: new, confirmed)');
            $table->string('color')->nullable()->comment('Mã màu hiển thị cho trạng thái');
            $table->string('description')->nullable()->comment('Mô tả chi tiết trạng thái');
            $table->boolean('active')->default(true)->comment('Trạng thái có đang được sử dụng');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pancake_order_statuses');
    }
};
