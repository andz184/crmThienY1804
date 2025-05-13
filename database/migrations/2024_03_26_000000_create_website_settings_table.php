<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, json, integer, etc.
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default order distribution settings
        DB::table('website_settings')->insert([
            [
                'key' => 'order_distribution_type',
                'value' => 'sequential', // sequential or batch
                'type' => 'string',
                'group' => 'orders',
                'label' => 'Kiểu phân phối đơn hàng',
                'description' => 'Chọn kiểu phân phối đơn hàng: sequential (1,2,3) hoặc batch (33,1,33,1)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'order_distribution_pattern',
                'value' => '1,1,1', // Default pattern
                'type' => 'string',
                'group' => 'orders',
                'label' => 'Mẫu phân phối đơn hàng',
                'description' => 'Mẫu phân phối đơn hàng (VD: 1,2,3 hoặc 33,1,33,1)',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('website_settings');
    }
};
