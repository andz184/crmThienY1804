<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Start transaction
        DB::beginTransaction();

        try {
            // Create permissions
            $permissions = [
                // Dashboard permissions
                'dashboard.view',
                
                // Product permissions
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
                'products.sync', // New permission for Pancake sync

                // Category permissions
                'categories.view',
                'categories.create',
                'categories.edit',
                'categories.delete',
                'categories.sync', // New permission for Pancake sync

        // Order permissions
                'orders.view',
                'orders.create',
                'orders.edit',
                'orders.delete',

                // Customer permissions
                'customers.view',
                'customers.create',
                'customers.edit',
                'customers.delete',

                // User permissions
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',

                // Role permissions
                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',

                // Settings permissions
                'settings.view',
                'settings.edit',
            ];

            // Create or update permissions
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            // Define roles and their permissions
            $roles = [
                'Super Admin' => $permissions,
                'Admin' => array_diff($permissions, ['roles.delete', 'settings.edit']),
                'Manager' => [
                    'dashboard.view',
                    'products.view',
                    'products.create',
                    'products.edit',
                    'products.sync',
                    'categories.view',
                    'categories.create',
                    'categories.edit',
                    'categories.sync',
                    'orders.view',
                    'orders.create',
                    'orders.edit',
                    'customers.view',
                    'customers.create',
                    'customers.edit',
                ],
                'Staff' => [
                    'products.view',
                    'categories.view',
                    'orders.view',
                    'orders.create',
                    'customers.view',
                    'customers.create',
                ],
            ];

            // Create or update roles and sync permissions
            foreach ($roles as $roleName => $rolePermissions) {
                $role = Role::firstOrCreate(['name' => $roleName]);
                $permissionsToGive = Permission::whereIn('name', $rolePermissions)->get();
                $role->givePermissionTo($permissionsToGive);
            }

            DB::commit();

            $this->command->info('Permissions and roles seeded successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding permissions and roles: ' . $e->getMessage());
            throw $e;
        }
    }
}
