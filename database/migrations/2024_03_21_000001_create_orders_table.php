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
            $table->string('order_code')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('shipping_provider_id')->nullable();
            $table->string('internal_status')->nullable();
            $table->text('notes')->nullable();
            $table->text('additional_notes')->nullable();
            $table->decimal('total_value', 10, 2)->default(0);
            $table->string('status')->default('moi');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('province_code')->nullable();
            $table->string('district_code')->nullable();
            $table->string('ward_code')->nullable();
            $table->string('street_address')->nullable();
            $table->text('full_address')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('warehouse_code')->nullable();
            $table->string('pancake_shop_id')->nullable();
            $table->string('pancake_page_id')->nullable();
            $table->decimal('transfer_money', 10, 2)->default(0);
            $table->string('pancake_push_status')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
