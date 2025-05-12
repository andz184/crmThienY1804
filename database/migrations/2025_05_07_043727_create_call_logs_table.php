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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // User (employee) who made/is associated
            $table->string('voip_call_id')->unique()->nullable(); // Unique ID from Voip24h
            $table->string('sip_extension')->nullable(); // Caller's extension (src from voip24h)
            $table->string('caller_number')->nullable(); // 'src' from Voip24h - can be same as sip_extension
            $table->string('destination_number')->nullable(); // 'did' or customer's number
            $table->string('call_status')->nullable(); // e.g., ANSWERED, NO ANSWER, FAILED
            $table->string('call_type')->nullable(); // e.g., outbound, inbound
            $table->timestamp('start_time')->nullable(); // Precise start time
            $table->integer('duration_seconds')->default(0); // Call duration in seconds (billsec from voip24h)
            $table->string('recording_url')->nullable();
            $table->text('notes')->nullable(); // For manual notes if any (though not directly from Voip24h history)
            $table->json('raw_voip_data')->nullable(); // To store the full JSON response from Voip24h for this call
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
