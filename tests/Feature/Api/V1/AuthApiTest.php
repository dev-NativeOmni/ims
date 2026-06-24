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
            'username' => 'superadmin',
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
                        'username',
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
            'username' => 'superadmin',
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
        $inactiveUser = User::where('username', 'superadmin')->first();
        $inactiveUser->update(['status' => 'inactive']);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'superadmin',
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
        $user = User::where('username', 'superadmin')->first();
        $token = $user->createToken('Test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'superadmin',
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
        $user = User::where('username', 'superadmin')->first();
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
        $user = User::where('username', 'superadmin')->first();
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

    public function test_user_can_list_active_tokens(): void
    {
        $user = User::where('username', 'superadmin')->first();
        $user->createToken('Device A');
        $token = $user->createToken('Device B')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/tokens');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'tokens' => [
                        '*' => [
                            'id',
                            'name',
                            'abilities',
                            'is_current',
                            'is_expired',
                            'last_used_at',
                            'expires_at',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(2, $response['data']['tokens']);
    }

    public function test_user_can_logout_other_devices(): void
    {
        $user = User::where('username', 'superadmin')->first();
        $user->createToken('Device A');
        $user->createToken('Device B');
        $token = $user->createToken('Device C')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout-other-devices');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Token device lain berhasil dicabut.',
                'data' => [
                    'revoked_tokens' => 2,
                ],
            ]);

        $this->assertCount(1, $user->fresh()->tokens);
    }

    public function test_user_can_revoke_specific_token(): void
    {
        $user = User::where('username', 'superadmin')->first();
        $targetToken = $user->createToken('Device target');
        $token = $user->createToken('Device active')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/auth/tokens/' . $targetToken->accessToken->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Token berhasil dicabut.',
            ]);

        $this->assertCount(1, $user->fresh()->tokens);
    }

    public function test_user_cannot_revoke_non_existing_token(): void
    {
        $user = User::where('username', 'superadmin')->first();
        $token = $user->createToken('Device active')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/auth/tokens/9999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Token tidak ditemukan.',
            ]);
    }
}
