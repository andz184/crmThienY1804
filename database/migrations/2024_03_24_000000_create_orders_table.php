<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 100)->unique();
            $table->string('customer_name', 255);
            $table->string('customer_phone', 50);
            $table->text('full_address')->nullable();
            $table->string('province_code', 50)->nullable();
            $table->string('district_code', 50)->nullable();
            $table->string('ward_code', 50)->nullable();
            $table->text('street_address')->nullable();
            $table->text('notes')->nullable();
            $table->text('additional_notes')->nullable();
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('transfer_money', 15, 2)->nullable()->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->string('status')->default('moi');
            $table->uuid('user_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
