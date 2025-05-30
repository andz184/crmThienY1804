<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear existing roles, permissions and users (optional, but good for clean seed)
        Schema::disableForeignKeyConstraints();
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('users')->truncate(); // Clear users as well
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        Schema::enableForeignKeyConstraints();

        // --- Define Core Permissions ---
        $permissions = [
            // User Permissions
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.view_trashed', 'users.restore', 'users.force_delete',
            // Role Permissions
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            // Order Permissions
            'orders.view', 'orders.create', 'orders.edit', 'orders.delete', 'orders.push_to_pancake',
            // Product Permissions
            'products.view', 'products.create', 'products.edit', 'products.delete',
            // Category Permissions
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            // Customer Permissions
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.view_trashed', 'customers.restore', 'customers.force_delete', 'customers.orders', 'customers.latest', 'customers.sync',
            // Order Filter Permissions
            'orders.filter_by_manager', 'orders.filter_by_staff', 'orders.filter_by_self',
            // Team Permissions
            'teams.view', 'teams.assign',
            // Call Permissions
            'calls.manage',
            // Dashboard Permissions
            'dashboard.view', 'dashboard.view_revenue',
            // Settings Permissions
            'settings.view', 'settings.update', 'settings.manage_favicon', 'settings.manage_seo', 'settings.clear_cache',
            // Add any other core permissions your app needs
            'settings.manage',
            // Logs Permissions
            'logs.view', 'logs.details',
            // New logs permissions
            'logs.view_all', 'logs.view_own',
            // Pancake sync permission
            'sync-pancake',
            // Product Sources Permissions
            'product-sources.view',
            'product-sources.sync',

            // Report Permissions
            'reports.view', // Tổng quan báo cáo
            'reports.total_revenue', // Báo cáo tổng doanh thu
            'reports.detailed', // Báo cáo chi tiết
            'reports.product_groups', // Báo cáo theo nhóm hàng hóa
            'reports.campaigns', // Báo cáo theo chiến dịch (bài post)
            'reports.live-sessions', // Báo cáo phiên live
            'reports.conversion_rates', // Báo cáo tỉ lệ chốt đơn
            'reports.customer_new', // Báo cáo khách hàng mới (đơn đầu tiên)
            'reports.customer_returning', // Báo cáo khách hàng cũ (đơn thứ 2+)
            'reports.view_all', // Xem báo cáo của tất cả người dùng
            'reports.view_team', // Xem báo cáo của team
            'reports.view_own', // Chỉ xem báo cáo của bản thân
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Get all permissions
        $allPermissions = Permission::all();

        // --- Create Core Roles ---
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        // --- Assign Permissions to Core Roles ---
        // Super admin gets all permissions including any that might be added later
        // Use a direct DB query to make sure ALL permissions are assigned to super-admin
        $allPermissionIds = Permission::all()->pluck('id')->toArray();
        $superAdminRole->syncPermissions($allPermissionIds);

        // Manager permissions
        $managerPermissions = [
            'users.view',
            'orders.view', 'orders.create', 'orders.edit', 'orders.push_to_pancake',
            'orders.filter_by_staff',
            'teams.view', 'teams.assign',
            'dashboard.view', 'dashboard.view_revenue',
            'logs.view', 'logs.details', 'logs.view_all',
            'customers.view', 'customers.create', 'customers.edit', 'customers.sync',
            'sync-pancake',
            'product-sources.view',
            'product-sources.sync',
            // Thêm quyền báo cáo cho manager
            'reports.view',
            'reports.total_revenue',
            'reports.detailed',
            'reports.product_groups',
            'reports.campaigns',
            'reports.live-sessions',
            'reports.conversion_rates',
            'reports.customer_new',
            'reports.customer_returning',
            'reports.view_team',
        ];
        $managerRole->syncPermissions(Permission::whereIn('name', $managerPermissions)->get());

        // Staff permissions
        $staffPermissions = [
            'orders.view', 'orders.create', 'orders.edit', 'orders.push_to_pancake',
            'orders.filter_by_self',
            'calls.manage',
            'dashboard.view',
            'customers.view',
            'logs.view', 'logs.view_own',
            // Thêm quyền báo cáo cho staff
            'reports.view',
            'reports.total_revenue',
            'reports.detailed',
            'reports.product_groups',
            'reports.campaigns',
            'reports.live-sessions',
            'reports.conversion_rates',
            'reports.customer_new',
            'reports.customer_returning',
            'reports.view_own',
        ];
        $staffRole->syncPermissions(Permission::whereIn('name', $staffPermissions)->get());

        // --- Create Core Users ---
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ])->assignRole($superAdminRole);

        // // --- Create Sample Managers ---
        // $managers = collect();
        // $numberOfManagers = 3; // Reduced for simplicity, adjust as needed
        // for ($i = 1; $i <= $numberOfManagers; $i++) {
        //     $managerUser = User::create([
        //         'name' => 'Manager ' . Str::ucfirst($faker->word),
        //         'email' => $faker->unique()->safeEmail(),
        //         'password' => Hash::make('password'),
        //         'manages_team_id' => $i + 100,
        //     ]);
        //     $managerUser->assignRole($managerRole);
        //     $managers->push($managerUser);
        // }
        // $managerTeamIds = $managers->pluck('manages_team_id')->toArray();

        // // --- Create Sample Staff Users ---
        // $numberOfStaff = 10; // Reduced for simplicity, adjust as needed
        // if (!empty($managerTeamIds)) { // Ensure there are managers to assign staff to
        //     for ($i = 1; $i <= $numberOfStaff; $i++) {
        //         User::create([
        //             'name' => $faker->name(),
        //             'email' => $faker->unique()->safeEmail(),
        //             'password' => Hash::make('password'),
        //             'team_id' => $managerTeamIds[array_rand($managerTeamIds)],
        //         ])->assignRole($staffRole);
        //     }
        // }

        echo "Seed: Roles (super-admin, manager, staff), Permissions, and sample Users created successfully.\n";
    }
}
