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
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id'); // Đảm bảo kiểu dữ liệu khớp với id() của Laravel
            $table->string('name')->comment('e.g., Large, Red, etc.');
            $table->string('sku')->unique()->comment('Stock Keeping Unit, will be used as variation_id in orders table');
            $table->decimal('price', 10, 2)->comment('Specific price for this variation');
            $table->integer('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            // $table->json('attributes')->nullable();
            $table->timestamps();

            // Định nghĩa khóa ngoại sau khi tất cả các cột đã được khai báo
            // Đảm bảo bảng 'products' đã tồn tại trước khi migration này chạy
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variations', function (Blueprint $table) {
            // Nên drop khóa ngoại trước khi drop bảng nếu có thể,
            // nhưng dropIfExists sẽ xử lý trong trường hợp này.
            // $table->dropForeign(['product_id']);
        });
        Schema::dropIfExists('product_variations');
    }
};
