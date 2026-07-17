<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\CoreDataSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
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

    public function test_admin_dashboard_can_be_accessed_by_admin(): void
    {
        $user = User::where('username', 'admin')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/dashboard/admin');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'dashboard' => [
                        'dashboard' => 'admin',
                    ],
                ],
            ]);
    }

    public function test_admin_dashboard_cannot_be_accessed_by_teacher(): void
    {
        $user = User::where('username', 'guru')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/dashboard/admin');

        $response->assertStatus(403);
    }

    public function test_teacher_dashboard_can_be_accessed_by_teacher(): void
    {
        $user = User::where('username', 'guru')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/dashboard/teacher');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'dashboard' => [
                        'dashboard' => 'teacher',
                    ],
                ],
            ]);
    }

    public function test_teacher_dashboard_cannot_be_accessed_by_parent(): void
    {
        $user = User::where('username', 'orangtua')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/dashboard/teacher');

        $response->assertStatus(403);
    }

    public function test_parent_dashboard_can_be_accessed_by_parent(): void
    {
        $user = User::where('username', 'orangtua')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/dashboard/parent');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'dashboard' => [
                        'dashboard' => 'parent',
                    ],
                ],
            ]);
    }

    public function test_parent_dashboard_cannot_be_accessed_by_student(): void
    {
        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/dashboard/parent');

        $response->assertStatus(403);
    }

    public function test_student_dashboard_can_be_accessed_by_student(): void
    {
        $user = User::where('username', 'santri')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/dashboard/student');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'dashboard' => [
                        'dashboard' => 'student',
                    ],
                ],
            ]);
    }

    public function test_student_dashboard_cannot_be_accessed_by_admin(): void
    {
        $user = User::where('username', 'admin')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/dashboard/student');

        $response->assertStatus(403);
    }
}
