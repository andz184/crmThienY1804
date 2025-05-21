<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Table for campaign reports
        if (!Schema::hasTable('campaign_reports')) {
            Schema::create('campaign_reports', function (Blueprint $table) {
                $table->id();
                $table->string('campaign_name');
                $table->string('post_id')->nullable();
                $table->decimal('total_revenue', 15, 2);
                $table->integer('total_orders');
                $table->integer('conversion_rate');
                $table->timestamps();
            });
        }

        // Table for product group reports
        if (!Schema::hasTable('product_group_reports')) {
            Schema::create('product_group_reports', function (Blueprint $table) {
                $table->id();
                $table->string('group_name');
                $table->decimal('total_revenue', 15, 2);
                $table->integer('total_orders');
                $table->integer('total_quantity');
                $table->timestamps();
            });
        }

        // Table for live session reports
        if (!Schema::hasTable('live_session_reports')) {
            Schema::create('live_session_reports', function (Blueprint $table) {
                $table->id();
                $table->string('session_name');
                $table->dateTime('start_time');
                $table->dateTime('end_time');
                $table->decimal('total_revenue', 15, 2);
                $table->integer('total_orders');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Table for customer order reports
        if (!Schema::hasTable('customer_order_reports')) {
            Schema::create('customer_order_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id');
                $table->boolean('is_first_order');
                $table->decimal('order_value', 15, 2);
                $table->dateTime('order_date');
                $table->timestamps();
                
                // Remove foreign key constraint as 'customers' table may not exist
                // $table->foreign('customer_id')->references('id')->on('customers');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('customer_order_reports');
        Schema::dropIfExists('live_session_reports');
        Schema::dropIfExists('product_group_reports');
        Schema::dropIfExists('campaign_reports');
    }
};
