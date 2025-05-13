<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Order Distribution Permissions
        Permission::create(['name' => 'view_order_distribution']);
        Permission::create(['name' => 'manage_order_distribution']);
        Permission::create(['name' => 'configure_distribution_settings']);

        // Assign permissions to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'view_order_distribution',
                'manage_order_distribution',
                'configure_distribution_settings'
            ]);
        }

        // Assign view permission to staff role
        $staffRole = Role::where('name', 'staff')->first();
        if ($staffRole) {
            $staffRole->givePermissionTo([
                'view_order_distribution'
            ]);
        }
    }
}
