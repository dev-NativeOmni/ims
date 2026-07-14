<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Student;
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

    public function test_calculates_dynamic_target_correctly()
    {
        // 1. Create a daily program & weekly program
        $dailyProgram = Program::create([
            'name' => 'Program Tahfizh Daily',
            'meeting_frequency' => 'setiap hari',
            'status' => 'active',
        ]);
        $weeklyProgram = Program::create([
            'name' => 'Program Reguler Weekly',
            'meeting_frequency' => 'seminggu sekali',
            'status' => 'active',
        ]);

        // 2. Create Classrooms
        $dailyClass = ClassRoom::create(['name' => 'Class Daily', 'program_id' => $dailyProgram->id, 'level' => '10']);
        $weeklyClass = ClassRoom::create(['name' => 'Class Weekly', 'program_id' => $weeklyProgram->id, 'level' => '11']);

        // 3. Create Students
        $studentRole = Role::firstOrCreate(['name' => 'student'], ['display_name' => 'Student']);
        
        $dailyStudent = Student::create([
            'user_id' => User::factory()->create(['role_id' => $studentRole->id])->id,
            'class_room_id' => $dailyClass->id,
            'name' => 'Ahmad Daily Tahfizh',
            'tahfizh_level' => 'reguler', // 5 lines
            'status' => 'active',
        ]);
        
        $weeklyStudent = Student::create([
            'user_id' => User::factory()->create(['role_id' => $studentRole->id])->id,
            'class_room_id' => $weeklyClass->id,
            'name' => 'Budi Weekly Reguler',
            'tahfizh_level' => 'tahsin', // 3 lines
            'status' => 'active',
        ]);

        // 4. View report of daily student (5 lines * 20 meetings = 100 lines/month)
        $response1 = $this->actingAs($this->adminUser)->get(route('digital-reports.show', $dailyStudent));
        $response1->assertStatus(200);
        $response1->assertSee('Target: 5 baris/pertemuan x 20 pertemuan = 100 baris/bulan');

        // 5. View report of weekly student (3 lines * 4 meetings = 12 lines/month)
        $response2 = $this->actingAs($this->adminUser)->get(route('digital-reports.show', $weeklyStudent));
        $response2->assertStatus(200);
        $response2->assertSee('Target: 3 baris/pertemuan x 4 pertemuan = 12 baris/bulan');
    }
}
