<?php

namespace Tests\Feature;

use App\Http\Controllers\UmmiFlowController;
use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\UmmiProsemRow;
use App\Models\User;
use App\Services\UmmiProsemService;
use Carbon\Carbon;
use Database\Seeders\QuranDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Prosem Ummi Kelas 10: impor dari sheet, target per pertemuan, target bulanan otomatis, halaman Prosem
 * & Alur Pembelajaran (docs/prosem-ummi.md).
 */
class UmmiProsemTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $class;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-21 08:00'); // Selasa, pekan ke-4 Juli
        $this->seed(QuranDataSeeder::class);
        $this->setUpHafizPlusData();
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->class = ClassRoom::create(['program_id' => $program->id, 'name' => 'X E2', 'level' => 'X']);
        $this->student->update(['class_room_id' => $this->class->id, 'tahfizh_level' => 'ummi', 'teacher_id' => $this->teacherProfile->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function import(): void
    {
        $this->artisan('tad:prosem-import', ['sumber' => base_path('tests/Fixtures/prosem-sem1.csv'), '--tahun' => '2026/2027', '--simpan' => true])
            ->expectsOutputToContain('16 baris, 62 TM')
            ->expectsOutputToContain('Baris 9: Hlm. Buku 9-1 (akhir < awal)')
            ->assertSuccessful();
    }

    #[Test]
    public function import_builds_the_meeting_plan_and_monthly_targets(): void
    {
        $this->import();
        $prosem = app(UmmiProsemService::class);
        $plan = $prosem->plan('2026/2027', 1);

        $this->assertCount(62, $plan);
        $this->assertSame([7, 4, 'Jilid 2', [1, 2], [3, 4]], [$plan[0]['month'], $plan[0]['week'], $plan[0]['jilid'], $plan[0]['buku'], $plan[1]['buku']]);
        $this->assertSame(["An-Nas – Al-'Asr", 'Al-Fatihah'], [UmmiProsemService::rangeLabel($plan[0]['hafalan']), UmmiProsemService::rangeLabel($plan[0]['drill'])]);
        $this->assertNull($plan[20]['jilid'], 'Baris tanpa materi = pengulangan.');

        $july = $prosem->monthTarget('2026/2027', 1, 7);
        $this->assertSame(['Jilid 2', '16', 'At-Tin'], [$july['ummi_jilid'], $july['halaman_buku'], Surah::find($july['surah_id'])->name_latin]);

        $meeting = $prosem->meetingFor($this->class, Carbon::today());
        $this->assertSame(4, $meeting['week_count']);
        $this->assertContains($meeting['entry']['no'], [1, 2, 3, 4]);
    }

    #[Test]
    public function monthly_targets_are_filled_automatically_without_overwriting_teacher_targets(): void
    {
        HafalanTarget::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'ummi_jilid' => 'Jilid 1', 'halaman_buku' => '30',
            'target_date' => '2026-07-31', 'status' => 'active']);
        $this->import();

        $august = HafalanTarget::where('student_id', $this->student->id)->whereMonth('target_date', 8)->first();
        $this->assertSame(['Jilid 2', '40', '2026-08'], [$august->ummi_jilid, (string) $august->halaman_buku, $august->auto_month]);
        $this->assertSame('Jilid 1', HafalanTarget::where('student_id', $this->student->id)->whereMonth('target_date', 7)->value('ummi_jilid'), 'Target guru Juli tetap.');
        $this->assertSame(6 - 1, HafalanTarget::where('student_id', $this->student->id)->whereNotNull('auto_month')->count());
    }

    #[Test]
    public function prosem_page_is_editable_only_by_coordinators_and_admins(): void
    {
        $this->import();
        $this->actingAs($this->teacherUser)->get(route('prosem-ummi.index'))->assertOk()
            ->assertSee('Target per Pertemuan')->assertDontSee('Simpan Prosem');
        $this->actingAs($this->teacherUser)->post(route('prosem-ummi.update'), ['rows' => []])->assertForbidden();

        $this->actingAs($this->admin)->post(route('prosem-ummi.update', ['academic_year' => '2026/2027', 'semester' => 1]), ['rows' => [
            ['jilid' => 'Jilid 1', 'buku_start' => 1, 'buku_end' => 6, 'weeks' => [7 => [4 => 3]]],
        ]])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(1, UmmiProsemRow::count());
        $this->assertSame([[1, 2], [3, 4], [5, 6]], array_column(app(UmmiProsemService::class)->plan('2026/2027', 1), 'buku'));
    }

    #[Test]
    public function flow_page_shows_sessions_and_todays_target_and_durations_are_configurable(): void
    {
        $this->import();
        $this->actingAs($this->teacherUser)->get(route('ummi-flow.index'))->assertOk()
            ->assertSee('Alur Pembelajaran Ummi')->assertSee('Target pertemuan hari ini')
            ->assertSee('Klasikal peraga &amp; baca simak', false)->assertDontSee('Atur durasi sesi');
        $this->actingAs($this->teacherUser)->post(route('ummi-flow.settings'), [])->assertForbidden();

        $minutes = ['pembukaan' => 5, 'murojaah' => 15, 'ziyadah' => 10, 'klasikal' => 35, 'penutup' => 5];
        $this->actingAs($this->admin)->post(route('ummi-flow.settings'), ['minutes' => $minutes])->assertSessionHas('success');
        $this->assertSame(15, collect(UmmiFlowController::sessions())->firstWhere('key', 'murojaah')['minutes']);
    }

    #[Test]
    public function each_halaqoh_uses_its_own_prosem_and_weekly_classes_get_the_whole_week(): void
    {
        $this->class->program->update(['meeting_frequency' => 'seminggu sekali']);
        $otherUser = User::factory()->create(['role_id' => $this->teacherUser->role_id, 'name' => 'Ust Aiji', 'status' => 'active']);
        $other = TeacherProfile::create(['user_id' => $otherUser->id, 'employee_number' => 'TEST-GURU-077']);
        $otherStudent = Student::create(['class_room_id' => $this->class->id, 'teacher_id' => $other->id, 'name' => 'Murid Halaqoh Aiji',
            'student_number' => 'TEST-SNT-077', 'gender' => 'male', 'birth_date' => '2010-01-01', 'status' => 'active', 'tahfizh_level' => 'ummi']);

        // Prosem kelas (semua halaqoh) + Prosem khusus halaqoh Ust Aiji.
        $this->artisan('tad:prosem-import', ['sumber' => base_path('tests/Fixtures/prosem-sem1.csv'), '--tahun' => '2026/2027', '--kelas' => 'X E2', '--simpan' => true])->assertSuccessful();
        $this->artisan('tad:prosem-import', ['sumber' => base_path('tests/Fixtures/prosem-e2-pekanan.csv'), '--tahun' => '2026/2027', '--kelas' => 'X E2', '--musyrif' => 'Aiji', '--simpan' => true])
            ->assertSuccessful();
        $this->assertSame(16, UmmiProsemRow::where('teacher_id', $other->id)->count(), '--musyrif=Aiji cocok dengan "Ust A\'iji" (tanda baca diabaikan).');

        $august = fn ($student) => HafalanTarget::where('student_id', $student->id)->whereMonth('target_date', 8)->first();
        $this->assertSame('Jilid 2', $august($this->student)->ummi_jilid, 'Halaqoh lain memakai Prosem kelas.');
        $this->assertSame('Jilid 1', $august($otherStudent)->ummi_jilid, 'Halaqoh Ust Aiji memakai Prosem-nya sendiri.');

        // Kelas pekanan: target hari ini = semua TM pekan itu (Prosem Ust Aiji: 2 TM/pekan).
        $meeting = app(UmmiProsemService::class)->meetingFor($this->class->fresh('program'), Carbon::today(), $other->id);
        $this->assertTrue($meeting['weekly']);
        $this->assertSame([1, 2, [1, 4]], [$meeting['entry']['no'], $meeting['entry']['no_end'], $meeting['entry']['buku']]);

        $this->actingAs($this->admin)->get(route('prosem-ummi.index', ['class_room_id' => $this->class->id, 'teacher_id' => $other->id]))
            ->assertOk()->assertSee("X E2 · Ust A'iji", false)->assertSee('Ujian');
    }
}
