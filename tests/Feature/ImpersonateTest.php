<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonateTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_impersonate_another_user(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['display_name' => 'Guru Tahfizh']);

        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'name' => 'Super Admin Utama',
            'username' => 'superadmin_test',
        ]);

        $teacher = User::factory()->create([
            'role_id' => $teacherRole->id,
            'name' => 'Ustadz Ahmad',
            'username' => 'ust_ahmad',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('impersonate.start', $teacher));

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals($teacher->id, auth()->id());
        $this->assertEquals($superAdmin->id, session('impersonated_by'));
    }

    public function test_impersonating_user_can_stop_and_return_to_super_admin(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $parentRole = Role::firstOrCreate(['name' => 'parent'], ['display_name' => 'Orang Tua']);

        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'name' => 'Super Admin Utama',
            'username' => 'superadmin_test2',
        ]);

        $parentUser = User::factory()->create([
            'role_id' => $parentRole->id,
            'name' => 'Bapak Budi',
            'username' => 'pak_budi',
        ]);

        // Start impersonating
        $this->actingAs($superAdmin)->post(route('impersonate.start', $parentUser));

        // Stop impersonating while logged in as parentUser
        $response = $this->actingAs($parentUser)
            ->withSession(['impersonated_by' => $superAdmin->id])
            ->post(route('impersonate.stop'));

        $response->assertRedirect(route('users.index'));
        $this->assertEquals($superAdmin->id, auth()->id());
        $this->assertFalse(session()->has('impersonated_by'));
    }
}
