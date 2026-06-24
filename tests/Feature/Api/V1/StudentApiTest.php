<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\ParentProfile;
use App\Models\ClassRoom;
use App\Models\Program;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\CoreDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentApiTest extends TestCase
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

    public function test_admin_can_list_all_students(): void
    {
        $user = User::where('username', 'admin')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'students',
                ],
            ]);

        $this->assertCount(1, $response['data']['students']);
    }

    public function test_teacher_can_list_only_guided_students(): void
    {
        // guru@hafizplus.test has Student SNT-001 guided.
        // Let's create another student not guided by this teacher to test filtering.
        $teacher2User = User::factory()->create([
            'role_id' => User::where('username', 'guru')->first()->role_id,
            'status' => 'active',
        ]);
        $teacher2Profile = TeacherProfile::create([
            'user_id' => $teacher2User->id,
            'employee_number' => 'GURU-002',
        ]);

        $student2User = User::factory()->create([
            'role_id' => User::where('username', 'santri')->first()->role_id,
            'status' => 'active',
        ]);
        
        $classRoom = ClassRoom::first();
        Student::create([
            'student_number' => 'SNT-002',
            'user_id' => $student2User->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $teacher2Profile->id,
            'name' => 'Santri 2 Not Guided',
            'gender' => 'male',
            'birth_date' => '2012-01-15',
            'status' => 'active',
        ]);

        $teacherUser = User::where('username', 'guru')->first();
        $token = $teacherUser->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/students');

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']['students']);
        $this->assertEquals('SNT-001', $response['data']['students'][0]['student_number']);
    }

    public function test_parent_can_list_only_connected_children(): void
    {
        // orangtua@hafizplus.test has Student SNT-001 connected.
        // Let's create another student not connected.
        $student2User = User::factory()->create([
            'role_id' => User::where('username', 'santri')->first()->role_id,
            'status' => 'active',
        ]);
        
        $classRoom = ClassRoom::first();
        $teacherProfile = TeacherProfile::first();
        Student::create([
            'student_number' => 'SNT-002',
            'user_id' => $student2User->id,
            'class_room_id' => $classRoom->id,
            'teacher_id' => $teacherProfile->id,
            'name' => 'Santri 2 Not Connected',
            'gender' => 'male',
            'birth_date' => '2012-01-15',
            'status' => 'active',
        ]);

        $parentUser = User::where('username', 'orangtua')->first();
        $token = $parentUser->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/students');

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']['students']);
        $this->assertEquals('SNT-001', $response['data']['students'][0]['student_number']);
    }

    public function test_student_can_list_only_themselves(): void
    {
        $studentUser = User::where('username', 'santri')->first();
        $token = $studentUser->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/students');

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']['students']);
        $this->assertEquals('SNT-001', $response['data']['students'][0]['student_number']);
    }

    public function test_user_can_get_student_detail(): void
    {
        $student = Student::first();
        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/students/' . $student->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'student' => [
                        'student_number' => 'SNT-001',
                    ],
                ],
            ]);
    }

    public function test_user_can_get_student_progress(): void
    {
        $student = Student::first();
        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/students/' . $student->id . '/progress');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'progress' => [
                        'student',
                        'quran' => [
                            'total_ayahs',
                            'memorized_ayahs',
                            'progress_percentage',
                        ],
                        'hafalan' => [
                            'total_records',
                            'passed_records',
                            'repeat_records',
                            'average_score',
                        ],
                        'murajaah' => [
                            'total_records',
                            'passed_records',
                            'repeat_records',
                            'average_score',
                        ],
                        'targets' => [
                            'total_targets',
                            'active_targets',
                            'completed_targets',
                            'missed_targets',
                        ],
                    ],
                ],
            ]);
    }
}
