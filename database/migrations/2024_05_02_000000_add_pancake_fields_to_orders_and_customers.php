<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPancakeFieldsToOrdersAndCustomers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add new fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('orders', 'cod_amount')) {
                $table->decimal('cod_amount', 15, 2)->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'money_to_collect')) {
                $table->decimal('money_to_collect', 15, 2)->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'conversation_id')) {
                $table->string('conversation_id')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'post_id')) {
                $table->string('post_id')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'system_id')) {
                $table->string('system_id')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'tags')) {
                $table->json('tags')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'shipping_address_info')) {
                $table->json('shipping_address_info')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'partner_info')) {
                $table->json('partner_info')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'warehouse_info')) {
                $table->json('warehouse_info')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'status_history')) {
                $table->json('status_history')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'order_history')) {
                $table->json('order_history')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'creator_info')) {
                $table->json('creator_info')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'assigning_seller_info')) {
                $table->json('assigning_seller_info')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'is_exchange_order')) {
                $table->boolean('is_exchange_order')->default(false);
            }
        });
        
        // Add new fields to customers table
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'phone_numbers')) {
                $table->json('phone_numbers')->nullable();
            }
            
            if (!Schema::hasColumn('customers', 'emails')) {
                $table->json('emails')->nullable();
            }
            
            if (!Schema::hasColumn('customers', 'fb_id')) {
                $table->string('fb_id')->nullable();
            }
            
            if (!Schema::hasColumn('customers', 'order_count')) {
                $table->integer('order_count')->default(0);
            }
            
            if (!Schema::hasColumn('customers', 'succeeded_order_count')) {
                $table->integer('succeeded_order_count')->default(0);
            }
            
            if (!Schema::hasColumn('customers', 'returned_order_count')) {
                $table->integer('returned_order_count')->default(0);
            }
            
            if (!Schema::hasColumn('customers', 'purchased_amount')) {
                $table->decimal('purchased_amount', 15, 2)->default(0);
            }
            
            if (!Schema::hasColumn('customers', 'customer_level')) {
                $table->string('customer_level')->nullable();
            }
            
            if (!Schema::hasColumn('customers', 'tags')) {
                $table->json('tags')->nullable();
            }
            
            if (!Schema::hasColumn('customers', 'conversation_tags')) {
                $table->json('conversation_tags')->nullable();
            }
            
            if (!Schema::hasColumn('customers', 'reward_points')) {
                $table->integer('reward_points')->default(0);
            }
            
            if (!Schema::hasColumn('customers', 'addresses')) {
                $table->json('addresses')->nullable();
            }
        });
        
        // Add new fields to order_items table
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'variation_info')) {
                $table->json('variation_info')->nullable();
            }
            
            if (!Schema::hasColumn('order_items', 'category_ids')) {
                $table->json('category_ids')->nullable();
            }
            
            if (!Schema::hasColumn('order_items', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0);
            }
            
            if (!Schema::hasColumn('order_items', 'total_discount')) {
                $table->decimal('total_discount', 15, 2)->default(0);
            }
            
            if (!Schema::hasColumn('order_items', 'is_discount_percent')) {
                $table->boolean('is_discount_percent')->default(false);
            }
            
            if (!Schema::hasColumn('order_items', 'is_deleted')) {
                $table->boolean('is_deleted')->default(false);
            }
        });
        
        // Make sure PancakePage table has username column
        if (Schema::hasTable('pancake_pages')) {
            Schema::table('pancake_pages', function (Blueprint $table) {
                if (!Schema::hasColumn('pancake_pages', 'username')) {
                    $table->string('username')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove added fields from orders table
        Schema::table('orders', function (Blueprint $table) {
            $columns = [
                'cod_amount', 'money_to_collect', 'conversation_id', 'post_id', 
                'system_id', 'tags', 'shipping_address_info', 'partner_info',
                'warehouse_info', 'status_history', 'order_history',
                'creator_info', 'assigning_seller_info', 'is_exchange_order'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        
        // Remove added fields from customers table
        Schema::table('customers', function (Blueprint $table) {
            $columns = [
                'phone_numbers', 'emails', 'fb_id', 'order_count',
                'succeeded_order_count', 'returned_order_count', 'purchased_amount',
                'customer_level', 'tags', 'conversation_tags', 'reward_points', 'addresses'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        
        // Remove added fields from order_items table
        Schema::table('order_items', function (Blueprint $table) {
            $columns = [
                'variation_info', 'category_ids', 'discount', 
                'total_discount', 'is_discount_percent', 'is_deleted'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        
        // Remove username column from pancake_pages table
        if (Schema::hasTable('pancake_pages')) {
            Schema::table('pancake_pages', function (Blueprint $table) {
                if (Schema::hasColumn('pancake_pages', 'username')) {
                    $table->dropColumn('username');
                }
            });
        }
    }
} 