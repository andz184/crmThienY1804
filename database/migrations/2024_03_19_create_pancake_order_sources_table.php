<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pancake_order_sources', function (Blueprint $table) {
            $table->id();
            $table->string('pancake_id')->unique()->comment('ID nguồn đơn từ Pancake');
            $table->string('name')->comment('Tên nguồn đơn');
            $table->string('platform')->nullable()->comment('Nền tảng (facebook, shopee, etc)');
            $table->boolean('is_active')->default(true);
            $table->json('raw_data')->nullable()->comment('Dữ liệu gốc từ Pancake');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pancake_order_sources');
    }
};
