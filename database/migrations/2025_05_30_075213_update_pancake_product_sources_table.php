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
        Schema::table('pancake_product_sources', function (Blueprint $table) {
            // Drop existing columns that we'll modify
            $table->dropColumn(['type', 'is_active']);

            // Add new columns
            $table->string('custom_id')->nullable()->after('pancake_id');
            $table->unsignedBigInteger('parent_id')->nullable()->after('custom_id');
            $table->string('project_id')->nullable()->after('parent_id');
            $table->string('shop_id')->nullable()->after('project_id');
            $table->string('link_source_id')->nullable()->after('shop_id');
            $table->boolean('is_removed')->default(false)->after('link_source_id');
            $table->timestamp('inserted_at')->nullable()->after('is_removed');

            // Re-add modified columns
            $table->enum('type', ['internal', 'external'])->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('type');

            // Modify raw_data column to ensure it can handle large JSON data
            $table->json('raw_data')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pancake_product_sources', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn([
                'custom_id',
                'parent_id',
                'project_id',
                'shop_id',
                'link_source_id',
                'is_removed',
                'inserted_at'
            ]);

            // Restore original columns
            $table->string('type')->nullable()->change();
            $table->boolean('is_active')->default(true)->change();
            $table->text('raw_data')->change();
        });
    }
};
