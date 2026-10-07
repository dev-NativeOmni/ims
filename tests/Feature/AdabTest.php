<?php

namespace Tests\Feature;

use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
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
        if (! $supervisorRole) {
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

        // Submit form (15 "Ya" (1) and 5 "Tidak" (0) answers -> score should be 15 / 20 * 100 = 75)
        $data = [
            'notes' => 'Catatan harianku',
        ];
        $yesCount = 15;
        $currentYes = 0;
        for ($c = 0; $c < 4; $c++) {
            for ($q = 0; $q < 5; $q++) {
                $data["cat_{$c}_q{$q}"] = ($currentYes < $yesCount) ? 1 : 0;
                $currentYes++;
            }
        }

        $response = $this->actingAs($studentUser)
            ->post(route('adab.store', $student), $data);

        $response->assertRedirect(route('adab.show', $student));

        // Assert record exists in database with score 75
        $this->assertDatabaseHas('adab_records', [
            'student_id' => $student->id,
            'student_score' => 75,
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

        // Create mentor user
        $mentorRole = Role::where('name', 'pendamping_adab')->first();
        if (! $mentorRole) {
            $mentorRole = Role::create(['name' => 'pendamping_adab', 'display_name' => 'Pendamping Adab']);
        }
        $mentor = User::factory()->create([
            'role_id' => $mentorRole->id,
            'username' => 'testmentor',
            'status' => 'active',
        ]);

        // Post periodic mentor grade for July 2026
        $response = $this->actingAs($mentor)
            ->post(route('adab.store-mentor-score', $student), [
                'mentor_score' => 80,
                'year' => 2026,
                'month' => 7,
                'notes' => 'Observasi baik',
            ]);

        $response->assertRedirect(route('adab.show', $student));

        $this->assertDatabaseHas('adab_mentor_assessments', [
            'student_id' => $student->id,
            'mentor_score' => 80,
            'mentor_id' => $mentor->id,
            'year' => 2026,
            'month' => 7,
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
        if (! $supervisorRole) {
            $supervisorRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor']);
        }
        $supervisor = User::factory()->create([
            'role_id' => $supervisorRole->id,
            'username' => 'testsupervisor',
            'status' => 'active',
        ]);

        $response = $this->actingAs($supervisor)->get(route('supervisor.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Progres Pengisian Seluruh Murid');
        $response->assertSee('Status Pengisian Murid Hari Ini');
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
        if (! $supervisorRole) {
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
        $defaultCategories = Setting::getAdabQuestions();

        foreach ($defaultCategories as $catIdx => $category) {
            $postData['categories'][$catIdx] = [
                'title' => $category['title'].' MODIFIED',
                'desc' => $category['desc'].' MODIFIED',
                'questions' => [],
            ];
            foreach ($category['questions'] as $qIdx => $questionText) {
                $postData['categories'][$catIdx]['questions'][$qIdx] = $questionText.' MODIFIED';
            }
        }

        // Can submit update
        $response = $this->actingAs($supervisor)->post(route('settings.adab.update'), $postData);
        $response->assertRedirect(route('settings.adab'));

        // Assert updated in Setting
        $updatedQuestions = Setting::getAdabQuestions();
        $this->assertEquals('🕋 Adab Kepada Allah MODIFIED', $updatedQuestions[0]['title']);
        $this->assertEquals('Apakah Anda melaksanakan shalat fardhu tepat waktu hari ini? MODIFIED', $updatedQuestions[0]['questions'][0]);
    }

    public function test_authorized_user_can_fetch_class_data_for_fast_mentor_input(): void
    {
        $admin = User::where('username', 'superadmin')->first();
        $classRoom = ClassRoom::first();

        $response = $this->actingAs($admin)->getJson(route('adab.mentor-class-data', [
            'class_room_id' => $classRoom->id,
            'year' => 2026,
            'month' => 9,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'class_room_name',
            'year',
            'month',
            'students',
        ]);
    }

    public function test_authorized_user_can_batch_store_mentor_scores(): void
    {
        $admin = User::where('username', 'superadmin')->first();
        $classRoom = ClassRoom::first();

        $students = [
            Student::create(['name' => 'Murid Test 1', 'student_number' => 'M1', 'class_room_id' => $classRoom->id]),
            Student::create(['name' => 'Murid Test 2', 'student_number' => 'M2', 'class_room_id' => $classRoom->id]),
            Student::create(['name' => 'Murid Test 3', 'student_number' => 'M3', 'class_room_id' => $classRoom->id]),
        ];

        $entries = [];
        foreach ($students as $idx => $student) {
            $entries[] = [
                'student_id' => $student->id,
                'mentor_score' => 95 - ($idx * 5),
                'notes' => 'Catatan test '.$idx,
            ];
        }

        $response = $this->actingAs($admin)->postJson(route('adab.batch-mentor-score'), [
            'year' => 2026,
            'month' => 9,
            'entries' => $entries,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'saved_count' => 3,
        ]);

        foreach ($students as $idx => $student) {
            $this->assertDatabaseHas('adab_mentor_assessments', [
                'student_id' => $student->id,
                'year' => 2026,
                'month' => 9,
                'mentor_score' => 95 - ($idx * 5),
                'notes' => 'Catatan test '.$idx,
            ]);
        }
    }

    public function test_multiple_pendamping_adab_can_be_assigned_to_class_and_evaluate_students(): void
    {
        $mentorRole = Role::where('name', 'pendamping_adab')->first();
        $mentor1 = User::factory()->create(['role_id' => $mentorRole->id, 'status' => 'active']);
        $mentor2 = User::factory()->create(['role_id' => $mentorRole->id, 'status' => 'active']);

        $classRoom = ClassRoom::first();
        $classRoom->pendampingAdabList()->sync([$mentor1->id, $mentor2->id]);

        $student = Student::create([
            'name' => 'Murid Kelas Multi',
            'student_number' => 'KM1',
            'class_room_id' => $classRoom->id,
        ]);

        // Both mentors can fetch class data
        $this->actingAs($mentor1)->getJson(route('adab.mentor-class-data', [
            'class_room_id' => $classRoom->id,
            'year' => 2026,
            'month' => 9,
        ]))->assertStatus(200);

        $this->actingAs($mentor2)->getJson(route('adab.mentor-class-data', [
            'class_room_id' => $classRoom->id,
            'year' => 2026,
            'month' => 9,
        ]))->assertStatus(200);

        // Mentor 2 can grade student
        $response = $this->actingAs($mentor2)->post(route('adab.store-mentor-score', $student), [
            'mentor_score' => 90,
            'year' => 2026,
            'month' => 9,
            'notes' => 'Dinilai oleh pendamping kedua',
        ]);
        $response->assertRedirect(route('adab.show', $student));

        $this->assertDatabaseHas('adab_mentor_assessments', [
            'student_id' => $student->id,
            'mentor_id' => $mentor2->id,
            'mentor_score' => 90,
        ]);
    }

    public function test_attendance_matrix_data_returns_correct_daily_breakdown_and_missed_dates(): void
    {
        $roleWali = Role::firstOrCreate(['name' => 'wali_kelas'], ['display_name' => 'Wali Kelas']);
        $waliUser = User::factory()->create(['role_id' => $roleWali->id, 'status' => 'active']);

        $classRoom = ClassRoom::first();
        $classRoom->update(['wali_kelas_user_id' => $waliUser->id]);

        $student1 = Student::create([
            'name' => 'Santri Rajin',
            'student_number' => 'SR1',
            'class_room_id' => $classRoom->id,
            'status' => 'active',
        ]);
        $student2 = Student::create([
            'name' => 'Santri Ada Bolong',
            'student_number' => 'SB2',
            'class_room_id' => $classRoom->id,
            'status' => 'active',
        ]);

        // Effective dates set for September 2026
        $effectiveDates = Setting::getEffectiveDatesSet(2026, 9);
        $datesList = array_keys($effectiveDates);
        $firstEffectiveDate = $datesList[0] ?? '2026-09-01';
        $secondEffectiveDate = $datesList[1] ?? '2026-09-02';

        // Student 1 fills both days
        AdabRecord::create([
            'student_id' => $student1->id,
            'assessment_date' => $firstEffectiveDate,
            'student_score' => 100,
            'total_score' => 100,
        ]);
        AdabRecord::create([
            'student_id' => $student1->id,
            'assessment_date' => $secondEffectiveDate,
            'student_score' => 90,
            'total_score' => 90,
        ]);

        // Student 2 fills only first day
        AdabRecord::create([
            'student_id' => $student2->id,
            'assessment_date' => $firstEffectiveDate,
            'student_score' => 85,
            'total_score' => 85,
        ]);

        $response = $this->actingAs($waliUser)->getJson(route('adab.attendance-matrix-data', [
            'class_room_id' => $classRoom->id,
            'year' => 2026,
            'month' => 9,
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'class_room_id',
            'class_room_name',
            'month',
            'month_name',
            'year',
            'days_in_month',
            'total_effective_days',
            'days_metadata',
            'students' => [
                '*' => [
                    'student_id',
                    'student_name',
                    'student_number',
                    'daily_status',
                    'filled_count',
                    'missed_count',
                    'missed_dates',
                    'attendance_rate',
                    'mentor_score',
                    'final_score',
                    'grade',
                    'grade_label',
                    'has_mentor_scored',
                    'has_missed',
                ],
            ],
            'summary' => [
                'total_students',
                'class_avg_rate',
                'perfect_count',
                'students_with_missed',
                'mentor_scored_count',
            ],
        ]);

        $studentsData = collect($response->json('students'));
        $s1 = $studentsData->firstWhere('student_id', $student1->id);
        $s2 = $studentsData->firstWhere('student_id', $student2->id);

        $this->assertNotNull($s1);
        $this->assertNotNull($s2);
        $this->assertTrue($s1['filled_count'] >= 2);
        $this->assertTrue($s2['filled_count'] >= 1);
    }

    public function test_authorized_staff_can_assist_and_fill_missed_date_for_student(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->first()->id,
            'username' => 'testadmin_adab',
            'status' => 'active',
        ]);

        $student = Student::create([
            'name' => 'Santri Missed Date',
            'student_number' => 'ST_MISSED_01',
        ]);

        $pastDate = now()->subDays(2)->toDateString();

        // Admin opens create form with ?date=...
        $this->actingAs($admin)
            ->get(route('adab.create', ['student' => $student, 'date' => $pastDate]))
            ->assertStatus(200)
            ->assertSee($pastDate);

        // Admin submits questionnaire for that past date
        $data = [
            'assessment_date' => $pastDate,
            'notes' => 'Bantu isi tanggal bolong',
        ];
        for ($c = 0; $c < 4; $c++) {
            for ($q = 0; $q < 5; $q++) {
                $data["cat_{$c}_q{$q}"] = 1;
            }
        }

        $response = $this->actingAs($admin)
            ->post(route('adab.store', $student), $data);

        $response->assertRedirect(route('adab.show', $student));

        $record = AdabRecord::where('student_id', $student->id)
            ->whereDate('assessment_date', $pastDate)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals($admin->id, $record->evaluator_id);
        $this->assertEquals(100.0, $record->student_score);
        $this->assertEquals('Bantu isi tanggal bolong', $record->notes);
    }
}
