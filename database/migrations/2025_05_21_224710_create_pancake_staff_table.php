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
        Schema::create('pancake_staff', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('pancake_id')->unique()->index(); // Pancake staff ID
            $table->string('user_id_pancake')->nullable(); // user_id from Pancake
            $table->string('profile_id')->nullable();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('fb_id')->nullable();
            $table->string('avatar_url')->nullable();
            $table->unsignedInteger('role')->nullable(); // The role number in Pancake
            $table->unsignedInteger('shop_id')->nullable(); // Pancake shop ID
            $table->boolean('is_assigned')->default(false);
            $table->boolean('is_assigned_break_time')->nullable();
            $table->boolean('enable_api')->nullable();
            $table->string('api_key')->nullable();
            $table->string('note_api_key')->nullable();
            $table->string('app_warehouse')->nullable();
            $table->string('department')->nullable();
            $table->string('department_id')->nullable();
            $table->string('preferred_shop')->nullable();
            $table->string('profile')->nullable();
            $table->unsignedInteger('pending_order_count')->default(0);
            $table->string('permission_in_sale_group')->nullable();
            $table->json('transaction_tags')->nullable();
            $table->string('work_time')->nullable();
            $table->string('creator')->nullable();
            $table->timestamp('pancake_inserted_at')->nullable(); // Keep the original timestamp
            $table->timestamps();
            
            // Foreign key relationship to users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
        
        // Add assigning_seller_id to orders table if it doesn't exist
        if (!Schema::hasColumn('orders', 'assigning_seller_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('assigning_seller_id')->nullable()->after('created_by');
                $table->string('assigning_seller_name')->nullable()->after('assigning_seller_id');
                $table->timestamp('pancake_inserted_at')->nullable()->after('notes');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First remove foreign key columns from orders table
        if (Schema::hasColumn('orders', 'assigning_seller_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('assigning_seller_id');
                $table->dropColumn('assigning_seller_name');
                $table->dropColumn('pancake_inserted_at');
            });
        }
        
        Schema::dropIfExists('pancake_staff');
    }
};
