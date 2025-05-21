<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear the warehouses table before seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('warehouses')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $warehouses = [
            [
                'code' => 'e6733492-2955-4fd0-89e0-89c0d28a4c55',
                'name' => 'THIÊN Ý PHARMA',
                'description' => null, // Add description if needed
                'status' => true,    // Assuming default status is active
                'pancake_id' => 'e6733492-2955-4fd0-89e0-89c0d28a4c55',  // Same as code as these appear to be UUID values
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => '61e18748-e6c1-4333-806d-ed0663baf68f',
                'name' => 'TÍN ĐỒ HÀNG HIỆU',
                'description' => null,
                'status' => true,
                'pancake_id' => '61e18748-e6c1-4333-806d-ed0663baf68f',  // Same as code
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => '9c240040-2dbc-4137-81f5-3c99a0d691a9',
                'name' => '22 Thành Công',
                'description' => null,
                'status' => true,
                'pancake_id' => '9c240040-2dbc-4137-81f5-3c99a0d691a9',  // Same as code
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert data into the warehouses table
        DB::table('warehouses')->insert($warehouses);

        // Or, if you prefer using the Warehouse model and it's set up with fillable properties:
        // foreach ($warehouses as $warehouseData) {
        //     Warehouse::create($warehouseData);
        // }
    }
}
