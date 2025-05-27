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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipping_province')) {
                $table->string('shipping_province')->nullable();
            }
            if (!Schema::hasColumn('orders', 'shipping_district')) {
                $table->string('shipping_district')->nullable();
            }
            if (!Schema::hasColumn('orders', 'shipping_ward')) {
                $table->string('shipping_ward')->nullable();
            }
            if (!Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable();
            }
            if (!Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone')->nullable();
            }
            if (!Schema::hasColumn('orders', 'customer_email')) {
                $table->string('customer_email')->nullable();
            }
            if (!Schema::hasColumn('orders', 'live_session_info')) {
                $table->json('live_session_info')->nullable();
            }
            if (!Schema::hasColumn('orders', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_province',
                'shipping_district',
                'shipping_ward',
                'customer_name',
                'customer_phone',
                'customer_email',
                'live_session_info',
                'customer_id'
            ]);
        });
    }
};
