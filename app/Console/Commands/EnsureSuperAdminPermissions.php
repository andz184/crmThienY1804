<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class EnsureSuperAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:ensure-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensures that the super-admin role has all permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking super-admin permissions...');

        // Get the super-admin role
        $superAdmin = Role::where('name', 'super-admin')->first();

        if (!$superAdmin) {
            $this->error('Super-admin role not found!');
            return 1;
        }

        // Get all available permissions
        $allPermissions = Permission::all();
        $totalPermissions = $allPermissions->count();

        // Get permissions the role currently has
        $currentPermissions = $superAdmin->permissions;
        $currentCount = $currentPermissions->count();

        $this->info("Super-admin currently has {$currentCount} of {$totalPermissions} available permissions.");

        if ($currentCount < $totalPermissions) {
            $this->info('Adding missing permissions...');

            // Get all permission IDs
            $allPermissionIds = $allPermissions->pluck('id')->toArray();

            // Assign all permissions to super-admin
            $superAdmin->syncPermissions($allPermissionIds);

            $this->info('Super-admin role now has all permissions!');
        } else {
            $this->info('Super-admin already has all available permissions.');
        }

        return 0;
    }
}
