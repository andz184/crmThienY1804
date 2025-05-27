<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add missing columns first
            if (!Schema::hasColumn('orders', 'full_address')) {
                $table->string('full_address')->nullable();
            }

            // Then change column types
            $table->text('full_address')->nullable()->change();
            $table->text('street_address')->nullable()->change();
            $table->text('notes')->nullable()->change();
            $table->text('additional_notes')->nullable()->change();
            $table->string('province_code', 50)->nullable()->change();
            $table->string('district_code', 50)->nullable()->change();
            $table->string('ward_code', 50)->nullable()->change();
            $table->string('order_code', 100)->change();
            $table->string('customer_name', 255)->change();
            $table->string('customer_phone', 50)->change();
            $table->string('pancake_page_id', 100)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('full_address')->nullable()->change();
            $table->string('street_address')->nullable()->change();
            $table->string('notes')->nullable()->change();
            $table->string('additional_notes')->nullable()->change();
            $table->string('province_code')->nullable()->change();
            $table->string('district_code')->nullable()->change();
            $table->string('ward_code')->nullable()->change();
            $table->string('order_code')->change();
            $table->string('customer_name')->change();
            $table->string('customer_phone')->change();
            $table->string('pancake_page_id')->nullable()->change();
        });
    }
};
