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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Update existing orders with customer_id based on customer_phone
        $orders = DB::table('orders')
            ->whereNotNull('customer_phone')
            ->whereNull('customer_id')
            ->get();

        foreach ($orders as $order) {
            $customerPhone = DB::table('customer_phones')
                ->where('phone_number', $order->customer_phone)
                ->first();

            if ($customerPhone) {
                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['customer_id' => $customerPhone->customer_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
