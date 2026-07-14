<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);
    }

    public function test_can_view_programs_index()
    {
        $program = Program::create([
            'name' => 'Program Tahfizh Utama',
            'description' => 'Test desc',
            'meeting_frequency' => 'setiap hari',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('programs.index'));

        $response->assertStatus(200);
        $response->assertSee('Program Tahfizh Utama');
        $response->assertSee('setiap hari');
    }

    public function test_can_create_program_with_meeting_frequency()
    {
        $response = $this->actingAs($this->adminUser)->post(route('programs.store'), [
            'name' => 'Program Reguler Baru',
            'description' => 'Kelas reguler',
            'meeting_frequency' => 'seminggu sekali',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('programs.index'));
        $this->assertDatabaseHas('programs', [
            'name' => 'Program Reguler Baru',
            'meeting_frequency' => 'seminggu sekali',
        ]);
    }

    public function test_can_update_program_with_meeting_frequency()
    {
        $program = Program::create([
            'name' => 'Program Lama',
            'description' => 'Deskripsi lama',
            'meeting_frequency' => 'setiap hari',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('programs.update', $program), [
            'name' => 'Program Baru',
            'description' => 'Deskripsi baru',
            'meeting_frequency' => 'seminggu sekali',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('programs.index'));
        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'name' => 'Program Baru',
            'meeting_frequency' => 'seminggu sekali',
        ]);
    }

    public function test_can_export_programs()
    {
        Program::create([
            'name' => 'Program Tahfizh A',
            'description' => 'Desc A',
            'meeting_frequency' => 'setiap hari',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('programs.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
