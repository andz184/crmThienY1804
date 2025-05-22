<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'pancake_order_id')) {
                $table->string('pancake_order_id')->nullable()->after('id')->index();
            }

            if (!Schema::hasColumn('orders', 'post_id')) {
                $table->string('post_id')->nullable()->after('pancake_page_id')->comment('For campaign tracking');
            }

            // Make sure we have the pancake_push_status field
            if (!Schema::hasColumn('orders', 'pancake_push_status')) {
                $table->string('pancake_push_status')->nullable()->after('internal_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'pancake_order_id')) {
                $table->dropColumn('pancake_order_id');
            }

            if (Schema::hasColumn('orders', 'post_id')) {
                $table->dropColumn('post_id');
            }

            if (Schema::hasColumn('orders', 'pancake_push_status')) {
                $table->dropColumn('pancake_push_status');
            }
        });
    }
};
