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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->string('full_address')->nullable();
            $table->string('province_code')->nullable();
            $table->string('district_code')->nullable();
            $table->string('ward_code')->nullable();
            $table->string('street_address')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('product_code')->nullable()->comment('Parent product code or slug');
            $table->string('product_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2)->default(0)->comment('Unit price of the variation');
            $table->decimal('shipping_fee', 15, 2)->nullable()->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->decimal('transfer_amount', 15, 2)->nullable()->default(0);
            $table->decimal('cod_amount', 15, 2)->nullable()->default(0);
            $table->string('status')->default('pending');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->comment('General order notes');
            $table->string('inventory_status')->nullable();
            $table->string('shipping_provider')->nullable();
            $table->string('internal_status')->nullable()->comment('e.g., Đã lên đơn Pancake');
            $table->text('additional_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
