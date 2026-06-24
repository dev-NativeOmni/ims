<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevRoutesTest extends TestCase
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

    public function test_dev_routes_require_authentication(): void
    {
        $responseDocs = $this->get('/dev/api-docs');
        $responseDocs->assertRedirect('/login');

        $responseYaml = $this->get('/dev/openapi.yaml');
        $responseYaml->assertRedirect('/login');
    }

    public function test_dev_routes_can_be_accessed_by_super_admin(): void
    {
        $superAdmin = User::where('username', 'superadmin')->first();

        $responseDocs = $this->actingAs($superAdmin)->get('/dev/api-docs');
        $responseDocs->assertStatus(200);

        $responseYaml = $this->actingAs($superAdmin)->get('/dev/openapi.yaml');
        $responseYaml->assertStatus(200)
            ->assertHeader('Content-Type', 'text/yaml; charset=UTF-8');
    }

    public function test_dev_routes_cannot_be_accessed_by_student(): void
    {
        $student = User::where('username', 'santri')->first();

        $responseDocs = $this->actingAs($student)->get('/dev/api-docs');
        $responseDocs->assertStatus(403);

        $responseYaml = $this->actingAs($student)->get('/dev/openapi.yaml');
        $responseYaml->assertStatus(403);
    }
}
