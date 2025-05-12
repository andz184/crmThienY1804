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
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name], [
                'description' => $description
            ]);
        }

        // Gán permission cho role admin
        $adminRole = Role::where('name', 'super-admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($permissions));
        }
    }
}
