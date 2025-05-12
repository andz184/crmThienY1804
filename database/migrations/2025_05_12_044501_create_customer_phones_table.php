<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('phone_number')->unique();
            $table->boolean('is_primary')->default(false);
            $table->string('type')->nullable()->comment('Type of phone number: mobile, landline, etc.');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Copy existing phone numbers to the new table
        DB::table('customers')->whereNotNull('phone')->orderBy('id')->chunk(100, function ($customers) {
            foreach ($customers as $customer) {
                DB::table('customer_phones')->insert([
                    'customer_id' => $customer->id,
                    'phone_number' => $customer->phone,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        });

        // Remove the phone column from customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the phone column to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('name');
            $table->unique('phone');
        });

        // Copy primary phone numbers back to customers table
        DB::table('customer_phones')
            ->where('is_primary', true)
            ->orderBy('customer_id')
            ->chunk(100, function ($phones) {
                foreach ($phones as $phone) {
                    DB::table('customers')
                        ->where('id', $phone->customer_id)
                        ->update(['phone' => $phone->phone_number]);
                }
            });

        Schema::dropIfExists('customer_phones');
    }
};
