<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\AdabRecord;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            UserSeeder::class,
            CoreDataSeeder::class,
        ]);
    }

    public function test_guest_cannot_access_adab(): void
    {
        $this->get(route('adab.index'))->assertRedirect(route('login'));
    }

    public function test_supervisor_can_access_adab_index(): void
    {
        $supervisorRole = Role::where('name', 'supervisor')->first();
        if (!$supervisorRole) {
            $supervisorRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor']);
        }
        $supervisor = User::factory()->create([
            'role_id' => $supervisorRole->id,
            'username' => 'testsupervisor',
            'status' => 'active',
        ]);

        $response = $this->actingAs($supervisor)->get(route('adab.index'));
        $response->assertStatus(200);
        $response->assertSee('Evaluasi Akhlak');
    }

    public function test_student_can_fill_own_adab_questionnaire(): void
    {
        $studentRole = Role::where('name', 'student')->first();
        $studentUser = User::factory()->create([
            'role_id' => $studentRole->id,
            'username' => 'teststudent',
            'status' => 'active',
        ]);
        $student = Student::create([
            'name' => 'Test Student',
            'student_number' => 'ST123',
            'user_id' => $studentUser->id,
        ]);

        // Access create form
        $this->actingAs($studentUser)
            ->get(route('adab.create', $student))
            ->assertStatus(200);

        // Submit form (18 "Ya" (1) and 2 "Tidak" (0) answers -> score should be 18 * 5 = 90)
        $data = [
            'notes' => 'Catatan harianku',
        ];
        for ($i = 1; $i <= 18; $i++) {
            $data["q{$i}"] = 1;
        }
        for ($i = 19; $i <= 20; $i++) {
            $data["q{$i}"] = 0;
        }

        $response = $this->actingAs($studentUser)
            ->post(route('adab.store', $student), $data);

        $response->assertRedirect(route('adab.show', $student));
        
        // Assert record exists in database with score 90
        $this->assertDatabaseHas('adab_records', [
            'student_id' => $student->id,
            'total_score' => 90,
            'notes' => 'Catatan harianku',
        ]);
    }

    public function test_student_can_only_see_their_own_adab_details(): void
    {
        $studentUser1 = User::factory()->create([
            'username' => 'student1',
            'role_id' => Role::where('name', 'student')->first()->id,
            'status' => 'active',
        ]);
        $student1 = Student::create([
            'name' => 'Student One',
            'student_number' => 'S1',
            'user_id' => $studentUser1->id,
        ]);

        $studentUser2 = User::factory()->create([
            'username' => 'student2',
            'role_id' => Role::where('name', 'student')->first()->id,
            'status' => 'active',
        ]);
        $student2 = Student::create([
            'name' => 'Student Two',
            'student_number' => 'S2',
            'user_id' => $studentUser2->id,
        ]);

        // Student 1 can see own
        $this->actingAs($studentUser1)
            ->get(route('adab.show', $student1))
            ->assertStatus(200);

        // Student 1 cannot see student 2
        $this->actingAs($studentUser1)
            ->get(route('adab.show', $student2))
            ->assertStatus(403);
    }

    public function test_supervisor_dashboard_shows_filling_progress(): void
    {
        $supervisorRole = Role::where('name', 'supervisor')->first();
        if (!$supervisorRole) {
            $supervisorRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor']);
        }
        $supervisor = User::factory()->create([
            'role_id' => $supervisorRole->id,
            'username' => 'testsupervisor',
            'status' => 'active',
        ]);

        $response = $this->actingAs($supervisor)->get(route('supervisor.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Progres Pengisian Seluruh Santri');
        $response->assertSee('Status Pengisian Santri Hari Ini');
    }

    public function test_superadmin_dashboard_shows_adab_monitoring(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'username' => 'testsuperadmin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin)->get(route('super-admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Adab Hari Ini');
        $response->assertSee('Monitoring Adab');
    }
}
