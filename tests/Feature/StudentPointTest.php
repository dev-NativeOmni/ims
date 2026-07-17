<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPointTest extends TestCase
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

    public function test_student_points_route_requires_authentication(): void
    {
        $response = $this->get('/student-points');
        $response->assertRedirect('/login');
    }

    public function test_all_authenticated_roles_can_access_index_page(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $admin = User::where('username', 'admin')->first();
        $teacher = User::where('username', 'guru')->first();
        $parent = User::where('username', 'orangtua')->first();
        $student = User::where('username', 'santri')->first();

        // Create tanse role & user
        $tanseRole = Role::updateOrCreate(
            ['name' => 'tanse'],
            ['name' => 'tanse', 'display_name' => 'Ketahanan Sekolah']
        );
        $tanse = User::factory()->create([
            'username' => 'tanseuser',
            'role_id' => $tanseRole->id,
            'status' => 'active',
        ]);

        $responseSuper = $this->actingAs($superAdmin)->get('/student-points');
        $responseSuper->assertStatus(200);

        $responseAdmin = $this->actingAs($admin)->get('/student-points');
        $responseAdmin->assertStatus(200);

        $responseTanse = $this->actingAs($tanse)->get('/student-points');
        $responseTanse->assertStatus(200);

        $responseTeacher = $this->actingAs($teacher)->get('/student-points');
        $responseTeacher->assertStatus(200);

        $responseParent = $this->actingAs($parent)->get('/student-points');
        $responseParent->assertStatus(200);

        $responseStudent = $this->actingAs($student)->get('/student-points');
        $responseStudent->assertStatus(200);
    }

    public function test_only_super_admin_admin_and_tanse_can_create_student_points(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();
        $teacher = User::where('username', 'guru')->first();
        $student = User::where('username', 'santri')->first();

        // Create tanse
        $tanseRole = Role::updateOrCreate(['name' => 'tanse'], ['name' => 'tanse', 'display_name' => 'Ketahanan Sekolah']);
        $tanse = User::factory()->create([
            'username' => 'tanseuser',
            'role_id' => $tanseRole->id,
            'status' => 'active',
        ]);

        $this->actingAs($superAdmin)->get('/student-points/create')->assertStatus(200);
        $this->actingAs($tanse)->get('/student-points/create')->assertStatus(200);

        $this->actingAs($teacher)->get('/student-points/create')->assertStatus(403);
        $this->actingAs($student)->get('/student-points/create')->assertStatus(403);
    }

    public function test_tanse_can_store_and_manage_student_points(): void
    {
        // Setup data
        $tanseRole = Role::updateOrCreate(['name' => 'tanse'], ['name' => 'tanse', 'display_name' => 'Ketahanan Sekolah']);
        $tanse = User::factory()->create([
            'username' => 'tanseuser',
            'role_id' => $tanseRole->id,
            'status' => 'active',
        ]);

        $program = Program::create(['name' => 'Reguler', 'status' => 'active']);
        $classRoom = ClassRoom::create(['name' => 'Kelas A', 'program_id' => $program->id, 'level' => '1']);

        $studentUser = User::factory()->create([
            'username' => 'santribaru',
            'role_id' => User::where('username', 'santri')->first()->role_id,
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'class_room_id' => $classRoom->id,
            'name' => 'Santri Baru',
            'student_number' => 'SNT-001',
            'gender' => 'male',
            'birth_date' => '2012-05-10',
            'status' => 'active',
        ]);

        // 1. Store violation
        $responseStore = $this->actingAs($tanse)->post('/student-points', [
            'student_id' => $student->id,
            'type' => 'violation',
            'points' => 15,
            'title' => 'Terlambat Masuk Kelas',
            'description' => 'Terlambat 15 menit tanpa alasan jelas',
            'date' => '2026-07-02',
        ]);

        $responseStore->assertRedirect('/student-points');
        $responseStore->assertSessionHasNoErrors();

        $this->assertDatabaseHas('student_points', [
            'student_id' => $student->id,
            'type' => 'violation',
            'points' => 15,
            'title' => 'Terlambat Masuk Kelas',
            'logged_by' => $tanse->id,
        ]);

        $point = StudentPoint::first();

        // 2. Edit
        $responseEdit = $this->actingAs($tanse)->get("/student-points/{$point->id}/edit");
        $responseEdit->assertStatus(200);

        // 3. Update
        $responseUpdate = $this->actingAs($tanse)->put("/student-points/{$point->id}", [
            'student_id' => $student->id,
            'type' => 'violation',
            'points' => 10, // modified
            'title' => 'Terlambat Masuk Kelas',
            'description' => 'Diubah menjadi 10 poin setelah musyawarah',
            'date' => '2026-07-02',
        ]);

        $responseUpdate->assertRedirect('/student-points');
        $this->assertEquals(10, $point->fresh()->points);

        // 4. Delete
        $responseDelete = $this->actingAs($tanse)->delete("/student-points/{$point->id}");
        $responseDelete->assertRedirect('/student-points');
        $this->assertDatabaseMissing('student_points', ['id' => $point->id]);
    }

    public function test_student_and_parent_point_isolation(): void
    {
        // Setup students
        $program = Program::create(['name' => 'Reguler', 'status' => 'active']);
        $classRoom = ClassRoom::create(['name' => 'Kelas A', 'program_id' => $program->id, 'level' => '1']);

        // Student 1 (active)
        $studentUser1 = User::where('username', 'santri')->first();
        $student1 = Student::updateOrCreate(
            ['user_id' => $studentUser1->id],
            [
                'class_room_id' => $classRoom->id,
                'name' => 'Santri Kesatu',
                'student_number' => 'SNT-001',
                'gender' => 'male',
                'status' => 'active',
            ]
        );

        // Student 2
        $studentUser2 = User::factory()->create([
            'username' => 'santrikedua',
            'role_id' => $studentUser1->role_id,
        ]);
        $student2 = Student::create([
            'user_id' => $studentUser2->id,
            'class_room_id' => $classRoom->id,
            'name' => 'Santri Kedua',
            'student_number' => 'SNT-002',
            'gender' => 'male',
            'status' => 'active',
        ]);

        // Create point logs
        $admin = User::where('username', 'admin')->first();
        $point1 = StudentPoint::create([
            'student_id' => $student1->id,
            'type' => 'violation',
            'points' => 10,
            'title' => 'Melanggar Aturan 1',
            'date' => '2026-07-02',
            'logged_by' => $admin->id,
        ]);

        $point2 = StudentPoint::create([
            'student_id' => $student2->id,
            'type' => 'reward',
            'points' => 20,
            'title' => 'Prestasi Hafalan 2',
            'date' => '2026-07-02',
            'logged_by' => $admin->id,
        ]);

        // Test Student 1 isolation: can only see their own point
        $responseStudent1 = $this->actingAs($studentUser1)->get('/student-points');
        $responseStudent1->assertStatus(200);

        $viewPointsStudent1 = $responseStudent1->viewData('points');
        $this->assertTrue($viewPointsStudent1->contains('id', $point1->id));
        $this->assertFalse($viewPointsStudent1->contains('id', $point2->id));

        // Test Parent isolation
        $parentUser = User::where('username', 'orangtua')->first();
        $parentProfile = ParentProfile::updateOrCreate(
            ['user_id' => $parentUser->id],
            ['phone' => '08987654321']
        );
        // Connect parent to Student 1 only
        $parentProfile->students()->attach($student1->id, ['relation' => 'father']);

        $responseParent = $this->actingAs($parentUser)->get('/student-points');
        $responseParent->assertStatus(200);

        $viewPointsParent = $responseParent->viewData('points');
        $this->assertTrue($viewPointsParent->contains('id', $point1->id));
        $this->assertFalse($viewPointsParent->contains('id', $point2->id));
    }
}
