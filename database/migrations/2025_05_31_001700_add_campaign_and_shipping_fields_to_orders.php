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
            // Add campaign fields
            $table->string('campaign_id')->nullable()->after('ward_code');
            $table->string('campaign_name')->nullable()->after('campaign_id');

            // Add shipping related fields
            $table->decimal('cod_fee', 12, 2)->nullable()->after('shipping_fee');
            $table->decimal('insurance_fee', 12, 2)->nullable()->after('cod_fee');
            $table->decimal('discount_amount', 12, 2)->nullable()->after('insurance_fee');
            $table->string('shipping_provider')->nullable()->after('discount_amount');
            $table->string('shipping_service')->nullable()->after('shipping_provider');
            $table->text('shipping_note')->nullable()->after('shipping_service');
            $table->text('internal_notes')->nullable()->after('shipping_note');
            $table->unsignedBigInteger('updated_by')->nullable()->after('internal_notes');

            // Add foreign key for updated_by
            $table->foreign('updated_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['updated_by']);

            // Drop columns
            $table->dropColumn([
                'campaign_id',
                'campaign_name',
                'cod_fee',
                'insurance_fee',
                'discount_amount',
                'shipping_provider',
                'shipping_service',
                'shipping_note',
                'internal_notes',
                'updated_by'
            ]);
        });
    }
};
