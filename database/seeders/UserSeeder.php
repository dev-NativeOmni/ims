<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'role' => 'super_admin',
                'name' => 'Super Admin HafizPlus',
                'email' => 'superadmin@hafizplus.test',
            ],
            [
                'role' => 'admin',
                'name' => 'Admin HafizPlus',
                'email' => 'admin@hafizplus.test',
            ],
            [
                'role' => 'teacher',
                'name' => 'Guru HafizPlus',
                'email' => 'guru@hafizplus.test',
            ],
            [
                'role' => 'parent',
                'name' => 'Orangtua HafizPlus',
                'email' => 'orangtua@hafizplus.test',
            ],
            [
                'role' => 'student',
                'name' => 'Santri HafizPlus',
                'email' => 'santri@hafizplus.test',
            ],
        ];

        foreach ($users as $userData) {
            $role = Role::where('name', $userData['role'])->firstOrFail();

            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'role_id' => $role->id,
                    'name' => $userData['name'],
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}