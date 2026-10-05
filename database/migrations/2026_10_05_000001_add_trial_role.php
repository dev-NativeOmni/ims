<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

/**
 * Role "trial" untuk pengunjung dari luar: hanya melihat (App\Support\ReadOnlyAccess),
 * plus satu akun bawaan username `trial` / password `000000`. Akun yang sudah ada tidak
 * diubah (password yang sudah diganti Super Admin tetap).
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'trial'],
            ['display_name' => 'Trial (Lihat Saja)']
        );

        $user = User::withTrashed()->firstOrCreate(
            ['username' => 'trial'],
            [
                'name' => 'Pengunjung Trial',
                'role_id' => $role->id,
                'password' => Hash::make('000000'),
                'plain_password' => '000000',
                'status' => 'active',
            ]
        );

        if ($user->wasRecentlyCreated) {
            $user->roles()->sync([$role->id]);
        }
    }

    public function down(): void
    {
        $role = Role::where('name', 'trial')->first();
        if (! $role) {
            return;
        }

        User::withTrashed()->where('username', 'trial')->where('role_id', $role->id)->forceDelete();
        $role->delete();
    }
};
