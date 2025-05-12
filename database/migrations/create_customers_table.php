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
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->string('full_address')->nullable();
            $table->string('province')->nullable();
            $table->string('district')->nullable();
            $table->string('ward')->nullable();
            $table->string('street_address')->nullable();
            $table->date('first_order_date')->nullable();
            $table->date('last_order_date')->nullable();
            $table->integer('total_orders_count')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->string('pancake_id')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
