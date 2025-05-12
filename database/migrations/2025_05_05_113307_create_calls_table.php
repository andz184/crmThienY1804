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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // ID của nhân viên sale thực hiện cuộc gọi
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade'); // Đơn hàng liên quan
            $table->string('customer_name'); // Tên khách hàng (có thể lấy từ order)
            $table->string('phone_number'); // Số điện thoại khách hàng (có thể lấy từ order)
            $table->integer('call_duration')->nullable(); // Thời lượng cuộc gọi (giây)
            $table->text('notes')->nullable(); // Ghi chú về cuộc gọi
            $table->timestamp('call_time')->useCurrent(); // Thời điểm thực hiện cuộc gọi
            $table->string('recording_url')->nullable(); // URL file ghi âm
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
