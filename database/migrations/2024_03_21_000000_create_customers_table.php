<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Address fields
            $table->text('full_address')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('ward')->nullable();
            $table->string('street_address')->nullable();

            // Order statistics
            $table->date('first_order_date')->nullable();
            $table->date('last_order_date')->nullable();
            $table->integer('total_orders_count')->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);

            // Pancake fields
            $table->string('pancake_customer_id')->nullable();
            $table->string('pancake_shop_id')->nullable();
            $table->string('pancake_page_id')->nullable();
            $table->json('social_info')->nullable();

            // Additional fields
            $table->text('notes')->nullable();
            $table->json('additional_data')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('phone');
            $table->index('email');
            $table->index('pancake_customer_id');
            $table->index(['pancake_shop_id', 'pancake_page_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
