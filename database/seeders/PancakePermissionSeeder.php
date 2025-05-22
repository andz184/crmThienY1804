<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PancakePermissionSeeder extends Seeder
{
    public function run()
    {
        // Tạo permissions
        $permissions = [
            'sync-pancake' => 'Đồng bộ dữ liệu từ Pancake',
            'view-sync-status' => 'Xem trạng thái đồng bộ Pancake',
            'sync-employees' => 'Đồng bộ nhân viên từ Pancake',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name], [
                'description' => $description
            ]);
        }

        // Gán permission cho các role
        $roles = ['super-admin', 'admin', 'manager'];
        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo(array_keys($permissions));
                $this->command->info("Assigned Pancake permissions to {$roleName} role");
            }
        }

        // Đảm bảo quyền được thêm vào staff nếu cần
        $staffRole = Role::where('name', 'staff')->first();
        if ($staffRole) {
            $staffRole->givePermissionTo('view-sync-status');
            $this->command->info("Assigned view-sync-status to staff role");
        }
    }
}
