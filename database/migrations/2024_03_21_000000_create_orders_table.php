<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('shipping_province')->nullable();
            $table->string('shipping_district')->nullable();
            $table->string('shipping_ward')->nullable();
            $table->string('street_address')->nullable();
            $table->decimal('total_value', 15, 2)->default(0);
            $table->string('status')->default('pending')->nullable();
            $table->text('notes')->nullable();
            $table->json('live_session_info')->nullable()->comment('JSON containing live session information');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
