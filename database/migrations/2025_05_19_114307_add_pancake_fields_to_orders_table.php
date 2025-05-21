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
            // Campos adicionais do Pancake
            $table->string('bill_full_name')->nullable()->after('customer_email');
            $table->string('bill_phone_number')->nullable()->after('bill_full_name');
            $table->string('bill_email')->nullable()->after('bill_phone_number');
            $table->boolean('is_free_shipping')->default(false)->after('shipping_fee');
            $table->boolean('is_livestream')->default(false)->after('is_free_shipping');
            $table->boolean('is_live_shopping')->default(false)->after('is_livestream');
            $table->decimal('partner_fee', 12, 2)->nullable()->after('is_live_shopping');
            $table->boolean('customer_pay_fee')->default(false)->after('partner_fee');
            $table->integer('returned_reason')->nullable()->after('customer_pay_fee');
            $table->json('tags')->nullable()->after('returned_reason');
            $table->json('warehouse_info')->nullable()->after('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'bill_full_name',
                'bill_phone_number',
                'bill_email',
                'is_free_shipping',
                'is_livestream',
                'is_live_shopping',
                'partner_fee',
                'customer_pay_fee',
                'returned_reason',
                'tags',
                'warehouse_info',
            ]);
        });
    }
};
