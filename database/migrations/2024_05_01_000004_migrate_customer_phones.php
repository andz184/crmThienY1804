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
        // First, create the customer_phones table
        Schema::create('customer_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('phone_number');
            $table->boolean('is_primary')->default(false);
            $table->string('type')->nullable()->comment('mobile, landline, etc.');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'phone_number']);
            $table->index('phone_number');
        });

        // Then migrate existing phone numbers to the new table
        $customers = DB::table('customers')->whereNotNull('phone')->get();
        foreach ($customers as $customer) {
            DB::table('customer_phones')->insert([
                'customer_id' => $customer->id,
                'phone_number' => $customer->phone,
                'is_primary' => true,
                'type' => 'mobile',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Finally remove the phone column from customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First add back the phone column
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('name');
        });

        // Then migrate phone numbers back
        $customerPhones = DB::table('customer_phones')
            ->where('is_primary', true)
            ->get();

        foreach ($customerPhones as $phone) {
            DB::table('customers')
                ->where('id', $phone->customer_id)
                ->update(['phone' => $phone->phone_number]);
        }

        // Finally drop the customer_phones table
        Schema::dropIfExists('customer_phones');
    }
};
