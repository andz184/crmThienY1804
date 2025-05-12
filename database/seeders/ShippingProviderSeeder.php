<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShippingProvider;
use Illuminate\Support\Facades\DB;

class ShippingProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Disable FK checks
        DB::table('shipping_providers')->truncate(); // Optional: Clear the table before seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Re-enable FK checks

        ShippingProvider::create([
            'pancake_id' => '3',
            'name' => 'viettelpos'
        ]);

        ShippingProvider::create([
            'pancake_id' => 'ddc4a56b-9a87-43bf-ad48-4c205879273e',
            'name' => 'Nội thành'
        ]);

        // Add any other default shipping providers here
    }
}
