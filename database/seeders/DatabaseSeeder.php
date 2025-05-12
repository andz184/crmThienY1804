<?php

namespace Database\Seeders;

// use App\Models\User; // Không cần User ở đây nữa nếu seeder kia tạo
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Added for DB facade

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid issues with deletion order
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear relevant tables before seeding
        DB::table('wards')->delete();
        DB::table('districts')->delete();
        DB::table('provinces')->delete();
        // Clear other tables as needed, e.g.:
        // DB::table('roles')->delete();
        // DB::table('permissions')->delete();
        // DB::table('role_has_permissions')->delete();
        // DB::table('model_has_roles')->delete();
        // DB::table('model_has_permissions')->delete();
        // DB::table('categories')->delete();
        // DB::table('products')->delete();
        // DB::table('product_variations')->delete();
        // DB::table('orders')->delete();
        // DB::table('order_items')->delete();
        // DB::table('daily_revenue_aggregates')->delete();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // User::factory(10)->create(); // Xóa hoặc comment lại

        // User::factory()->create([ // Xóa hoặc comment lại
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            // CategorySeeder::class,
            // ProductSeeder::class,
            // ProductVariationSeeder::class,
            WarehouseSeeder::class,         // Prerequisite for Orders
            ProvinceSeeder::class,          // Prerequisite for Orders (if addresses use them)
            DistrictSeeder::class,          // Prerequisite for Orders
            WardSeeder::class,              // Prerequisite for Orders
            ShippingProviderSeeder::class,  // Prerequisite for Orders
            OrderSeeder::class,             // Now runs after its dependencies
            DailyRevenueAggregateSeeder::class,
        ]);

        // Remove the separate WardSeeder call section
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('wards')->delete();
        // $this->call([ WardSeeder::class ]);
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
