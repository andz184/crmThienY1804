<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            // Social media fields
            if (!Schema::hasColumn('customers', 'facebook_id')) {
                $table->string('facebook_id')->nullable()->after('street_address');
            }
            if (!Schema::hasColumn('customers', 'facebook_url')) {
                $table->string('facebook_url')->nullable()->after('facebook_id');
            }
            if (!Schema::hasColumn('customers', 'zalo_id')) {
                $table->string('zalo_id')->nullable()->after('facebook_url');
            }
            if (!Schema::hasColumn('customers', 'telegram_id')) {
                $table->string('telegram_id')->nullable()->after('zalo_id');
            }

            // Customer source and tags
            if (!Schema::hasColumn('customers', 'source')) {
                $table->string('source')->nullable()->after('telegram_id');
            }

            // Customer status and classification
            if (!Schema::hasColumn('customers', 'status')) {
                $table->string('status')->default('active')->after('source');
            }
            if (!Schema::hasColumn('customers', 'customer_type')) {
                $table->string('customer_type')->nullable()->after('status');
            }
            if (!Schema::hasColumn('customers', 'customer_group')) {
                $table->string('customer_group')->nullable()->after('customer_type');
            }

            // Additional contact preferences
            if (!Schema::hasColumn('customers', 'can_contact')) {
                $table->boolean('can_contact')->default(true)->after('last_order_date');
            }
            if (!Schema::hasColumn('customers', 'contact_preferences')) {
                $table->json('contact_preferences')->nullable()->after('can_contact');
            }

            // External IDs
            if (!Schema::hasColumn('customers', 'pancake_customer_id')) {
                $table->string('pancake_customer_id')->nullable()->unique()->after('deleted_at');
            }
            if (!Schema::hasColumn('customers', 'external_ids')) {
                $table->json('external_ids')->nullable()->after('pancake_customer_id');
            }
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_id',
                'facebook_url',
                'zalo_id',
                'telegram_id',
                'source',
                'status',
                'customer_type',
                'customer_group',
                'can_contact',
                'contact_preferences',
                'pancake_customer_id',
                'external_ids',
            ]);
        });
    }
};
