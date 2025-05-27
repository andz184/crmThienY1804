<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('live_session_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('live_session_order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();

            $table->foreign('live_session_order_id')
                ->references('id')
                ->on('live_session_orders')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            // Index for faster queries
            $table->index(['live_session_order_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('live_session_order_items');
    }
};
