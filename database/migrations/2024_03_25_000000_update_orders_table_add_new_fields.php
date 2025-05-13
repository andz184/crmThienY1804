<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add new fields if they don't exist
            if (!Schema::hasColumn('orders', 'transfer_money')) {
                $table->decimal('transfer_money', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('orders', 'additional_notes')) {
                $table->text('additional_notes')->nullable();
            }
            if (!Schema::hasColumn('orders', 'province_code')) {
                $table->string('province_code')->nullable();
            }
            if (!Schema::hasColumn('orders', 'district_code')) {
                $table->string('district_code')->nullable();
            }
            if (!Schema::hasColumn('orders', 'ward_code')) {
                $table->string('ward_code')->nullable();
            }
            if (!Schema::hasColumn('orders', 'pancake_page_id')) {
                $table->string('pancake_page_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'warehouse_id')) {
                $table->uuid('warehouse_id')->nullable();
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'transfer_money',
                'additional_notes',
                'province_code',
                'district_code',
                'ward_code',
                'pancake_page_id',
                'warehouse_id'
            ]);
        });
    }
};
