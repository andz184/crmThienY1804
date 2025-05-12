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
        Schema::table('customers', function (Blueprint $table) {
            // Basic info
            if (!Schema::hasColumn('customers', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('name');
            }
            if (!Schema::hasColumn('customers', 'gender')) {
                $table->string('gender')->nullable()->after('date_of_birth');
            }

            // Social media and identifiers
            if (!Schema::hasColumn('customers', 'fb_id')) {
                $table->string('fb_id')->nullable()->after('email');
            }
            if (!Schema::hasColumn('customers', 'referral_code')) {
                $table->string('referral_code')->nullable()->after('fb_id');
            }

            // Order related
            if (!Schema::hasColumn('customers', 'reward_point')) {
                $table->decimal('reward_point', 10, 2)->default(0)->after('total_spent');
            }
            if (!Schema::hasColumn('customers', 'succeed_order_count')) {
                $table->integer('succeed_order_count')->default(0)->after('total_orders_count');
            }
            if (!Schema::hasColumn('customers', 'last_order_at')) {
                $table->timestamp('last_order_at')->nullable()->after('last_order_date');
            }

            // Additional fields
            if (!Schema::hasColumn('customers', 'tags')) {
                $table->json('tags')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('customers', 'addresses')) {
                $table->json('addresses')->nullable()->after('tags');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $columns = [
                'date_of_birth',
                'gender',
                'fb_id',
                'referral_code',
                'reward_point',
                'succeed_order_count',
                'last_order_at',
                'tags',
                'addresses'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
