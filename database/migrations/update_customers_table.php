<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('customers', 'pancake_id')) {
                $table->string('pancake_id')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('customers', 'total_orders_count')) {
                $table->integer('total_orders_count')->default(0)->after('street_address');
            }
            if (!Schema::hasColumn('customers', 'total_spent')) {
                $table->decimal('total_spent', 12, 2)->default(0)->after('total_orders_count');
            }
            if (!Schema::hasColumn('customers', 'first_order_date')) {
                $table->date('first_order_date')->nullable()->after('total_spent');
            }
            if (!Schema::hasColumn('customers', 'last_order_date')) {
                $table->date('last_order_date')->nullable()->after('first_order_date');
            }
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'pancake_id',
                'total_orders_count',
                'total_spent',
                'first_order_date',
                'last_order_date'
            ]);
        });
    }
};
