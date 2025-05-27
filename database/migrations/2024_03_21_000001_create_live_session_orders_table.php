<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('live_session_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('live_session_id');
            $table->date('live_session_date');
            $table->unsignedBigInteger('customer_id');
            $table->string('customer_name');
            $table->longText('shipping_address');
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');

            // Indexes
            $table->index(['live_session_id', 'live_session_date']);
            $table->index('customer_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('live_session_orders');
    }
};
