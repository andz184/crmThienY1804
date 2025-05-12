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
            $table->string('facebook_id')->nullable()->after('street_address');
            $table->string('facebook_url')->nullable()->after('facebook_id');
            $table->string('zalo_id')->nullable()->after('facebook_url');
            $table->string('telegram_id')->nullable()->after('zalo_id');

            // Customer source and tags
            $table->string('source')->nullable()->after('telegram_id');

            // Customer status and classification
            $table->string('status')->default('active')->after('source');
            $table->string('customer_type')->nullable()->after('status');
            $table->string('customer_group')->nullable()->after('customer_type');

            // Additional contact preferences
            $table->boolean('can_contact')->default(true)->after('last_order_date');
            $table->json('contact_preferences')->nullable()->after('can_contact');

            // External IDs
            $table->string('pancake_customer_id')->nullable()->unique()->after('deleted_at');
            $table->json('external_ids')->nullable()->after('pancake_customer_id');
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
