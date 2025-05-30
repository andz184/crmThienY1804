<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pancake_product_sources', function (Blueprint $table) {
            $table->id();
            $table->string('pancake_id')->unique()->comment('ID nguồn hàng từ Pancake');
            $table->string('name')->comment('Tên nguồn hàng');
            $table->string('type')->nullable()->comment('Loại nguồn hàng');
            $table->boolean('is_active')->default(true);
            $table->json('raw_data')->nullable()->comment('Dữ liệu gốc từ Pancake');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pancake_product_sources');
    }
};
