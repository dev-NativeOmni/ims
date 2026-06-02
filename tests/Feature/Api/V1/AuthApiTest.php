<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
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

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'superadmin@hafizplus.test',
            'password' => 'password123',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_at',
                    'expires_in_days',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'status',
                        'role' => [
                            'id',
                            'name',
                            'display_name',
                        ],
                    ],
                ],
                'status_code',
            ]);

        $this->assertTrue($response['success']);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'superadmin@hafizplus.test',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'status_code' => 401,
            ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $inactiveUser = User::where('email', 'superadmin@hafizplus.test')->first();
        $inactiveUser->update(['status' => 'inactive']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'superadmin@hafizplus.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'status_code' => 403,
            ]);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::where('email', 'superadmin@hafizplus.test')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'superadmin@hafizplus.test',
                    ],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('email', 'superadmin@hafizplus.test')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout berhasil.',
            ]);

        $this->assertCount(0, $user->tokens);
    }

    public function test_user_can_logout_all_devices(): void
    {
        $user = User::where('email', 'superadmin@hafizplus.test')->first();
        $user->createToken('Device 1');
        $user->createToken('Device 2');
        $token = $user->createToken('Device 3')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout semua device berhasil.',
            ]);

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
