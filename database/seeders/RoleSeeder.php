<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
            ],
            [
                'name' => 'teacher',
                'display_name' => 'Guru/Musyrif',
            ],
            [
                'name' => 'parent',
                'display_name' => 'Orangtua',
            ],
            [
                'name' => 'student',
                'display_name' => 'Santri',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}