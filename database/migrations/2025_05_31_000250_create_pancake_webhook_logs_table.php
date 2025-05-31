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
        Schema::create('pancake_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->nullable(); // Type of webhook event
            $table->string('source_ip')->nullable(); // IP address of the sender
            $table->json('request_data')->nullable(); // Full webhook payload
            $table->json('processed_data')->nullable(); // Processed data (what we actually used)
            $table->string('status')->default('success'); // success, error
            $table->text('error_message')->nullable(); // Any error messages
            $table->string('order_id')->nullable(); // Related order ID if applicable
            $table->string('customer_id')->nullable(); // Related customer ID if applicable
            $table->timestamps();

            // Indexes for better performance
            $table->index('event_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pancake_webhook_logs');
    }
};
