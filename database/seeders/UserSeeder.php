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
                'username' => 'superadmin',
            ],
            [
                'role' => 'admin',
                'name' => 'Admin HafizPlus',
                'username' => 'admin',
            ],
            [
                'role' => 'teacher',
                'name' => 'Guru HafizPlus',
                'username' => 'guru',
            ],
            [
                'role' => 'parent',
                'name' => 'Orangtua HafizPlus',
                'username' => 'orangtua',
            ],
            [
                'role' => 'student',
                'name' => 'Santri HafizPlus',
                'username' => 'santri',
            ],
        ];

        foreach ($users as $userData) {
            $role = Role::where('name', $userData['role'])->firstOrFail();

            $user = User::updateOrCreate(
                ['username' => $userData['username']],
                [
                    'role_id' => $role->id,
                    'name' => $userData['name'],
                    'password' => Hash::make('password123'),
                    'plain_password' => 'password123',
                    'status' => 'active',
                ]
            );

            if ($userData['role'] === 'teacher') {
                \App\Models\TeacherProfile::updateOrCreate(['user_id' => $user->id]);
            } elseif ($userData['role'] === 'parent') {
                \App\Models\ParentProfile::updateOrCreate(['user_id' => $user->id]);
            } elseif ($userData['role'] === 'student') {
                \App\Models\Student::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'name' => $user->name,
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}