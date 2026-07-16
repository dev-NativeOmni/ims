<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'superadmin@ims.test'],
            [
                'role_id' => $superAdminRole->id,
                'name' => 'Super Admin IMS (Integrated Management System)',
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}