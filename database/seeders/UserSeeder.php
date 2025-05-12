<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Remove User::query()->delete(); if RolesAndPermissionsSeeder handles user creation/role assignment
        // or if you want to preserve other users not defined here.
        // If UserSeeder is the SOLE seeder responsible for these specific users, delete is fine.
        // For now, we'll keep it to ensure a clean state for these specific users.
        // However, if RolesAndPermissionsSeeder creates these users first, this delete will remove them before updateOrCreate can find them.
        // Consider which seeder should be the source of truth for user creation.
        // A common pattern: RolesAndPermissionsSeeder creates roles/permissions, UserSeeder creates users and assigns roles.

        User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin User',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
            ]
        );

        // If you have a RolesAndPermissionsSeeder that assigns roles,
        // you might want to fetch these users and assign roles here, e.g.:
        // $superAdmin = User::where('email', 'superadmin@example.com')->first();
        // if ($superAdmin) {
        //     $superAdmin->assignRole('super-admin');
        // }
        // Similar for other users and roles.
    }
}
