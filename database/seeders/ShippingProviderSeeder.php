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
            'pancake_partner_id' => '3',
            'name' => 'Viettel Post',
            'code' => 'viettelpost',
            'description' => 'Dịch vụ chuyển phát Viettel Post',
            'is_active' => true
        ]);

        ShippingProvider::create([
            'pancake_id' => 'ddc4a56b-9a87-43bf-ad48-4c205879273e',
            'pancake_partner_id' => 'ddc4a56b-9a87-43bf-ad48-4c205879273e',
            'name' => 'Nội thành',
            'code' => 'noi_thanh',
            'description' => 'Giao hàng nội thành',
            'is_active' => true
        ]);

        // Add GHTK shipping provider
        ShippingProvider::create([
            'pancake_id' => '5',
            'pancake_partner_id' => '5',
            'name' => 'Giao Hàng Tiết Kiệm',
            'code' => 'ghtk',
            'description' => 'Dịch vụ Giao Hàng Tiết Kiệm',
            'is_active' => true
        ]);

        // Add GHN shipping provider
        ShippingProvider::create([
            'pancake_id' => '1',
            'pancake_partner_id' => '1',
            'name' => 'Giao Hàng Nhanh',
            'code' => 'ghn',
            'description' => 'Dịch vụ Giao Hàng Nhanh',
            'is_active' => true
        ]);
    }
}
