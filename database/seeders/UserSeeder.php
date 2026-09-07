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
                'name' => 'Super Admin TAD (Tahfizh, Adab, Disiplin)',
                'username' => 'superadmin',
            ],
            [
                'role' => 'admin',
                'name' => 'Admin TAD (Tahfizh, Adab, Disiplin)',
                'username' => 'admin',
            ],
            [
                'role' => 'teacher',
                'name' => 'Guru TAD (Tahfizh, Adab, Disiplin)',
                'username' => 'guru',
            ],
            [
                'role' => 'parent',
                'name' => 'Orangtua TAD (Tahfizh, Adab, Disiplin)',
                'username' => 'orangtua',
            ],
            [
                'role' => 'student',
                'name' => 'Santri TAD (Tahfizh, Adab, Disiplin)',
                'username' => 'santri',
            ],
            [
                'role' => 'headmaster',
                'name' => 'Kepala Sekolah TAD (Tahfizh, Adab, Disiplin)',
                'username' => 'kepsek',
            ],
            [
                'role' => 'tanse',
                'name' => 'Tanse TAD (Tahfizh, Adab, Disiplin)',
                'username' => 'tanse',
            ],
            [
                'role' => 'coordinator_tahfizh',
                'name' => 'Koordinator Tahfizh TAD (Tahfizh, Adab, Disiplin)',
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
