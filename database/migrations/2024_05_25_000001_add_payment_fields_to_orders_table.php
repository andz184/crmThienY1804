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
            $table->string('payment_method')->nullable()->after('total_value')->comment('Phương thức thanh toán');
            $table->string('payment_status')->nullable()->after('payment_method')->comment('Trạng thái thanh toán');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('payment_status')->comment('Số tiền đã thanh toán');
            $table->timestamp('paid_at')->nullable()->after('paid_amount')->comment('Thời gian thanh toán');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'paid_amount',
                'paid_at'
            ]);
        });
    }
}; 