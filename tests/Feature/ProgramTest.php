<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $dailyClass = ClassRoom::create(['name' => 'Class Daily XI', 'program_id' => $dailyProgram->id, 'level' => '11']);
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

        // 4. Target rapor = pertemuan aktif triwulan (kalender) x baris per level.
        $parse = function ($student) {
            $response = $this->actingAs($this->adminUser)->get(route('digital-reports.show', $student));
            $response->assertStatus(200);
            $this->assertMatchesRegularExpression('/^Target Triwulan \d \([^)]+\): (\d+) baris x (\d+) pertemuan = (\d+) baris/', $response->viewData('termTargetText'));
            preg_match('/: (\d+) baris x (\d+) pertemuan = (\d+) baris/', $response->viewData('termTargetText'), $m);
            $this->assertSame((int) $m[1] * (int) $m[2], (int) $m[3]);

            return [(int) $m[1], (int) $m[2]];
        };

        [$dailyLines, $dailyMeetings] = $parse($dailyStudent);
        $this->assertSame(5, $dailyLines);

        // 5. Program seminggu sekali: paling banyak satu pertemuan per pekan (<= 14 per triwulan).
        [$weeklyLines, $weeklyMeetings] = $parse($weeklyStudent);
        $this->assertSame(3, $weeklyLines);
        $this->assertLessThanOrEqual(14, $weeklyMeetings);
        $this->assertGreaterThan($weeklyMeetings, $dailyMeetings);
    }
}
