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
        // Optional: Clear the warehouses table before seeding
        // Warehouse::truncate();
        // Or if not using Eloquent for truncate:
        // DB::table('warehouses')->delete();

        $warehouses = [
            [
                'code' => 'e6733492-2955-4fd0-89e0-89c0d28a4c55',
                'name' => 'THIÊN Ý PHARMA',
                'description' => null, // Add description if needed
                'status' => true,    // Assuming default status is active
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => '61e18748-e6c1-4333-806d-ed0663baf68f',
                'name' => 'TÍN ĐỒ HÀNG HIỆU',
                'description' => null,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => '9c240040-2dbc-4137-81f5-3c99a0d691a9',
                'name' => '22 Thành Công',
                'description' => null,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert data into the warehouses table
        // Using DB::table for direct insertion
        DB::table('warehouses')->insert($warehouses);

        // Or, if you prefer using the Warehouse model and it's set up with fillable properties:
        // foreach ($warehouses as $warehouseData) {
        //     Warehouse::create($warehouseData);
        // }
    }
}
