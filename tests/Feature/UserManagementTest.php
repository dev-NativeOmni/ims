<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_guest_and_non_super_admin_cannot_access_user_management(): void
    {
        // Guest
        $this->get(route('users.index'))->assertRedirect(route('login'));

        // Admin (non-super-admin)
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'username' => 'testadmin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertStatus(403);
    }

    public function test_super_admin_can_view_users_list_and_plain_passwords(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'username' => 'testsuperadmin',
            'status' => 'active',
            'plain_password' => 'supersecret123',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertSee('Manajemen Akun User');
        $response->assertSee('supersecret123'); // Assert plain-text password is visible
    }

    public function test_super_admin_can_update_user_credentials(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'username' => 'testsuperadmin',
            'status' => 'active',
        ]);

        $studentRole = Role::where('name', 'student')->first();
        $studentUser = User::factory()->create([
            'role_id' => $studentRole->id,
            'username' => 'oldusername',
            'status' => 'active',
            'plain_password' => 'oldpassword',
        ]);

        // Update to new credentials
        $response = $this->actingAs($superAdmin)
            ->patch(route('users.update', $studentUser), [
                'name' => 'New Student Name',
                'username' => 'newusername',
                'role_id' => $studentRole->id,
                'password' => 'newawesomepassword123',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('users.index'));

        // Assert DB updated
        $studentUser->refresh();
        $this->assertEquals('newusername', $studentUser->username);
        $this->assertEquals('New Student Name', $studentUser->name);
        $this->assertEquals('newawesomepassword123', $studentUser->plain_password);
        $this->assertTrue(Hash::check('newawesomepassword123', $studentUser->password));
    }

    public function test_super_admin_can_create_new_user(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'username' => 'testsuperadmin',
            'status' => 'active',
        ]);

        $teacherRole = Role::where('name', 'teacher')->first();

        // Access create form
        $this->actingAs($superAdmin)
            ->get(route('users.create'))
            ->assertStatus(200);

        // Store new user
        $response = $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'Guru Baru Tahfidz',
                'username' => 'gurubaru',
                'role_id' => $teacherRole->id,
                'password' => 'secretpwd123',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Guru Baru Tahfidz',
            'username' => 'gurubaru',
            'role_id' => $teacherRole->id,
            'plain_password' => 'secretpwd123',
            'status' => 'active',
        ]);
    }

    public function test_non_super_admin_cannot_create_new_user(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'username' => 'testadmin',
            'status' => 'active',
        ]);

        $teacherRole = Role::where('name', 'teacher')->first();

        // Try to access create form
        $this->actingAs($admin)
            ->get(route('users.create'))
            ->assertStatus(403);

        // Try to store new user
        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Guru Baru Tahfidz',
                'username' => 'gurubaru',
                'role_id' => $teacherRole->id,
                'password' => 'secretpwd123',
                'status' => 'active',
            ])
            ->assertStatus(403);
    }
}
