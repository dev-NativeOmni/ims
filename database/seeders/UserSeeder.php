<?php

namespace Database\Seeders;

use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\TeacherProfile;
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
                'name' => 'Super Admin IMS (Integrated Management System)',
                'username' => 'superadmin',
            ],
            [
                'role' => 'admin',
                'name' => 'Admin IMS (Integrated Management System)',
                'username' => 'admin',
            ],
            [
                'role' => 'teacher',
                'name' => 'Guru IMS (Integrated Management System)',
                'username' => 'guru',
            ],
            [
                'role' => 'parent',
                'name' => 'Orangtua IMS (Integrated Management System)',
                'username' => 'orangtua',
            ],
            [
                'role' => 'student',
                'name' => 'Santri IMS (Integrated Management System)',
                'username' => 'santri',
            ],
            [
                'role' => 'headmaster',
                'name' => 'Kepala Sekolah IMS (Integrated Management System)',
                'username' => 'kepsek',
            ],
            [
                'role' => 'tanse',
                'name' => 'Tanse IMS (Integrated Management System)',
                'username' => 'tanse',
            ],
            [
                'role' => 'coordinator_tahfizh',
                'name' => 'Koordinator Tahfizh IMS (Integrated Management System)',
                'username' => 'koordinator',
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
                TeacherProfile::updateOrCreate(['user_id' => $user->id]);
            } elseif ($userData['role'] === 'parent') {
                ParentProfile::updateOrCreate(['user_id' => $user->id]);
            } elseif ($userData['role'] === 'student') {
                Student::updateOrCreate(
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
