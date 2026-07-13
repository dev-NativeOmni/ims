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

        // Submit form (12 "Ya" (1) and 3 "Tidak" (0) answers -> score should be 12 / 15 * 50 = 40)
        $data = [
            'notes' => 'Catatan harianku',
        ];
        for ($i = 1; $i <= 12; $i++) {
            $data["q{$i}"] = 1;
        }
        for ($i = 13; $i <= 15; $i++) {
            $data["q{$i}"] = 0;
        }

        $response = $this->actingAs($studentUser)
            ->post(route('adab.store', $student), $data);

        $response->assertRedirect(route('adab.show', $student));
        
        // Assert record exists in database with score 40
        $this->assertDatabaseHas('adab_records', [
            'student_id' => $student->id,
            'total_score' => 40,
            'notes' => 'Catatan harianku',
        ]);
    }

    public function test_mentor_can_grade_student_adab(): void
    {
        $studentRole = Role::where('name', 'student')->first();
        $studentUser = User::factory()->create([
            'role_id' => $studentRole->id,
            'status' => 'active',
        ]);
        $student = Student::create([
            'name' => 'Test Student 2',
            'student_number' => 'ST124',
            'user_id' => $studentUser->id,
        ]);

        // Create an existing daily record for student
        $record = AdabRecord::create([
            'student_id' => $student->id,
            'evaluator_id' => $studentUser->id,
            'assessment_date' => now()->toDateString(),
            'q1' => 1, 'q2' => 1, 'q3' => 1, 'q4' => 1, 'q5' => 1,
            'q6' => 1, 'q7' => 1, 'q8' => 1, 'q9' => 1, 'q10' => 1,
            'q11' => 1, 'q12' => 1, 'q13' => 0, 'q14' => 0, 'q15' => 0,
            'total_score' => 40,
        ]);

        // Create mentor user
        $mentorRole = Role::where('name', 'pendamping_adab')->first();
        if (!$mentorRole) {
            $mentorRole = Role::create(['name' => 'pendamping_adab', 'display_name' => 'Pendamping Adab']);
        }
        $mentor = User::factory()->create([
            'role_id' => $mentorRole->id,
            'username' => 'testmentor',
            'status' => 'active',
        ]);

        // Post mentor grade (80 out of 100 -> weighted score 40)
        // Total score should become: 40 (student score) + 40 (mentor weighted) = 80
        $response = $this->actingAs($mentor)
            ->post(route('adab.store-mentor-score', [$student, $record]), [
                'mentor_score' => 80,
            ]);

        $response->assertRedirect(route('adab.show', $student));

        $this->assertDatabaseHas('adab_records', [
            'id' => $record->id,
            'mentor_score' => 80,
            'mentor_id' => $mentor->id,
            'total_score' => 80,
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

    public function test_unauthorized_user_cannot_access_adab_settings(): void
    {
        // Guest
        $this->get(route('settings.adab'))->assertRedirect(route('login'));

        // Teacher
        $teacherRole = Role::where('name', 'teacher')->first();
        $teacher = User::factory()->create(['role_id' => $teacherRole->id, 'status' => 'active']);
        $this->actingAs($teacher)->get(route('settings.adab'))->assertStatus(403);

        // Student
        $studentRole = Role::where('name', 'student')->first();
        $student = User::factory()->create(['role_id' => $studentRole->id, 'status' => 'active']);
        $this->actingAs($student)->get(route('settings.adab'))->assertStatus(403);
    }

    public function test_authorized_user_can_access_and_update_adab_settings(): void
    {
        $supervisorRole = Role::where('name', 'supervisor')->first();
        if (!$supervisorRole) {
            $supervisorRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor']);
        }
        $supervisor = User::factory()->create([
            'role_id' => $supervisorRole->id,
            'username' => 'testsupervisor2',
            'status' => 'active',
        ]);

        // Can access page
        $this->actingAs($supervisor)->get(route('settings.adab'))->assertStatus(200);

        // Prepare test data
        $postData = ['categories' => []];
        $defaultCategories = \App\Models\Setting::getAdabQuestions();
        
        foreach ($defaultCategories as $catIdx => $category) {
            $postData['categories'][$catIdx] = [
                'title' => $category['title'] . ' MODIFIED',
                'desc' => $category['desc'] . ' MODIFIED',
                'questions' => [],
            ];
            foreach ($category['questions'] as $qKey => $questionText) {
                $postData['categories'][$catIdx]['questions'][$qKey] = $questionText . ' MODIFIED';
            }
        }

        // Can submit update
        $response = $this->actingAs($supervisor)->post(route('settings.adab.update'), $postData);
        $response->assertRedirect(route('settings.adab'));

        // Assert updated in Setting
        $updatedQuestions = \App\Models\Setting::getAdabQuestions();
        $this->assertEquals('🕋 Adab Kepada Allah MODIFIED', $updatedQuestions[0]['title']);
        $this->assertEquals('Apakah Anda melaksanakan shalat fardhu tepat waktu hari ini? MODIFIED', $updatedQuestions[0]['questions']['q1']);
    }
}
