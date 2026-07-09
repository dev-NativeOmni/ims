<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Student;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodicReportTest extends TestCase
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

    public function test_periodic_report_requires_authentication(): void
    {
        $response = $this->get('/reports/periodic');
        $response->assertRedirect('/login');
    }

    public function test_periodic_report_accessible_by_allowed_roles(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $admin = User::where('username', 'admin')->first();
        $teacher = User::where('username', 'guru')->first();

        // Create other roles
        $roles = [
            'supervisor' => 'Koordinator',
            'coordinator_tahfizh' => 'Koordinator Tahfizh',
            'headmaster' => 'Kepala Sekolah'
        ];

        foreach ($roles as $name => $displayName) {
            $role = Role::updateOrCreate(['name' => $name], ['display_name' => $displayName]);
            $user = User::factory()->create([
                'role_id' => $role->id,
                'status' => 'active',
            ]);

            $response = $this->actingAs($user)->get('/reports/periodic');
            $response->assertStatus(200);

            $responsePrint = $this->actingAs($user)->get('/reports/periodic/print');
            $responsePrint->assertStatus(200);
        }

        // Test super_admin, admin, teacher
        $this->actingAs($superAdmin)->get('/reports/periodic')->assertStatus(200);
        $this->actingAs($admin)->get('/reports/periodic')->assertStatus(200);
        $this->actingAs($teacher)->get('/reports/periodic')->assertStatus(200);
    }

    public function test_periodic_report_forbidden_for_student_and_parent(): void
    {
        $student = User::where('username', 'santri')->first();
        
        $parentRole = Role::where('name', 'parent')->first();
        $parent = User::factory()->create([
            'role_id' => $parentRole->id,
            'status' => 'active',
        ]);

        $this->actingAs($student)->get('/reports/periodic')->assertStatus(403);
        $this->actingAs($parent)->get('/reports/periodic')->assertStatus(403);

        $this->actingAs($student)->get('/reports/periodic/print')->assertStatus(403);
        $this->actingAs($parent)->get('/reports/periodic/print')->assertStatus(403);
    }
}
