<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ClassRoom;
use App\Models\Program;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Models\User;
use App\Services\UmmiTatapMukaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * TM (tatap muka) UMMI = urutan pertemuan Ummi yang benar-benar terjadi di triwulan, per halaqoh
 * (UmmiTatapMukaService). Test backfill di bawah menguji migrasi lama berbasis kalender.
 */
class UmmiTatapMukaTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function grade10ClassRoom(): ClassRoom
    {
        $program = Program::create(['name' => 'Tahfizh Kelas 10', 'status' => 'active']);

        return ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X UMMI',
            'level' => 'X',
            // Hari aktif kelas Senin-Jumat (Jumat dipakai tahfizh mandiri,
            // bukan UMMI) -- endpoint & backfill harus tetap mengecualikan
            // Jumat khusus untuk hitungan TM UMMI.
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
    }

    private function ummiSession(string $date, int $tatapMuka = 1, ?int $studentId = null): UmmiRecord
    {
        return UmmiRecord::create([
            'student_id' => $studentId ?? $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => $date,
            'tatap_muka' => $tatapMuka,
        ]);
    }

    #[Test]
    public function suggestion_endpoint_returns_the_next_ummi_meeting_of_the_term(): void
    {
        $classRoom = $this->grade10ClassRoom();
        $this->student->update(['class_room_id' => $classRoom->id, 'teacher_id' => $this->teacherProfile->id]);
        $suggest = fn (string $date) => $this->actingAs($this->teacherUser)->get(route('ummi-records.tatap-muka-suggestion', [
            'class_room_id' => $classRoom->id,
            'date' => $date,
        ]))->assertOk()->json('tatap_muka');

        $this->assertSame(1, $suggest('2026-07-06'), 'Belum ada pertemuan Ummi di triwulan ini.');

        // Pertemuan Ummi yang benar-benar terjadi: 1 & 2 Juli. Hari efektif tanpa setoran tidak dihitung.
        $this->ummiSession('2026-07-01');
        $this->ummiSession('2026-07-02');
        $this->assertSame(3, $suggest('2026-07-06'));
        $this->assertSame(2, $suggest('2026-07-02'), 'Tanggal yang sudah ada pertemuannya memakai nomornya sendiri.');

        // Triwulan baru mulai lagi dari TM 1.
        $this->assertSame(1, $suggest('2026-10-01'));
    }

    #[Test]
    public function suggestion_endpoint_requires_valid_class_and_date(): void
    {
        $response = $this->actingAs($this->teacherUser)->getJson(route('ummi-records.tatap-muka-suggestion', [
            'class_room_id' => 999999,
            'date' => '2026-07-06',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('class_room_id');
    }

    #[Test]
    public function backfill_migration_recalculates_existing_tatap_muka_from_calendar(): void
    {
        $classRoom = $this->grade10ClassRoom();
        $this->student->update(['class_room_id' => $classRoom->id]);

        $record = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => '2026-07-06',
            'tatap_muka' => 999, // Nilai lama yang salah (hasil hitungan manual lama).
        ]);

        $migration = require database_path('migrations/2026_09_16_000001_recalculate_ummi_tatap_muka_from_academic_calendar.php');
        $migration->up();

        $this->assertSame(3, $record->fresh()->tatap_muka);
    }

    #[Test]
    public function saving_a_backdated_meeting_renumbers_later_meetings_per_halaqoh(): void
    {
        $classRoom = $this->grade10ClassRoom();
        $this->student->update(['class_room_id' => $classRoom->id, 'teacher_id' => $this->teacherProfile->id, 'tahfizh_level' => 'ummi']);

        // Halaqoh lain di kelas yang sama punya jadwal sendiri -- tidak ikut menggeser.
        $otherTeacher = TeacherProfile::create(['user_id' => User::factory()->create(['name' => 'Ustadzah Lain'])->id]);
        $other = Student::create([
            'class_room_id' => $classRoom->id, 'teacher_id' => $otherTeacher->id, 'name' => 'Murid Halaqoh Lain',
            'student_number' => 'TEST-TM-002', 'gender' => 'female', 'birth_date' => '2010-01-01', 'status' => 'active', 'tahfizh_level' => 'ummi',
        ]);
        $otherSession = $this->ummiSession('2026-07-01', 1, $other->id);

        $second = $this->ummiSession('2026-07-08', 1);
        $third = $this->ummiSession('2026-07-15', 1);
        app(UmmiTatapMukaService::class)->renumber([[$this->student->id, '2026-07-08']]);
        $this->assertSame([1, 2], [$second->fresh()->tatap_muka, $third->fresh()->tatap_muka]);

        // Pertemuan susulan 6 Juli diinput belakangan lewat form -> jadi TM 1, sesudahnya bergeser.
        $this->actingAs($this->teacherUser)->post(route('ummi-records.store'), [
            'class_room_id' => $classRoom->id,
            'student_ids' => [$this->student->id],
            'tanggal' => '2026-07-06',
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Ya',
            'redirect_to' => 'hafalan',
        ])->assertSessionHasNoErrors();

        $first = UmmiRecord::where('student_id', $this->student->id)->whereDate('tanggal', '2026-07-06')->firstOrFail();
        $this->assertSame([1, 2, 3], [$first->tatap_muka, $second->fresh()->tatap_muka, $third->fresh()->tatap_muka]);
        $this->assertSame(1, $otherSession->fresh()->tatap_muka);

        // Hapus pertemuan pertama -> urutan kembali rapat.
        $this->actingAs($this->teacherUser)->delete(route('ummi-records.destroy', $first));
        $this->assertSame([1, 2], [$second->fresh()->tatap_muka, $third->fresh()->tatap_muka]);
    }

    #[Test]
    public function command_renumbers_the_active_term_and_logs_old_values(): void
    {
        $classRoom = $this->grade10ClassRoom();
        $this->student->update(['class_room_id' => $classRoom->id, 'teacher_id' => $this->teacherProfile->id]);
        $a = $this->ummiSession('2026-09-01', 1);
        $b = $this->ummiSession('2026-09-02', 1); // rusak: harusnya TM 2
        $lastTerm = $this->ummiSession('2026-06-30', 7); // triwulan lalu tidak disentuh

        $this->artisan('tad:urutkan-tm-ummi', ['--tanggal' => '2026-09-15', '--dry-run' => true])->assertSuccessful();
        $this->assertSame(1, $b->fresh()->tatap_muka);

        $this->artisan('tad:urutkan-tm-ummi', ['--tanggal' => '2026-09-15', '--force' => true])->assertSuccessful();
        $this->assertSame([1, 2], [$a->fresh()->tatap_muka, $b->fresh()->tatap_muka]);
        $this->assertSame(7, $lastTerm->fresh()->tatap_muka);

        $log = AuditLog::query()->where('auditable_type', UmmiRecord::class)->where('auditable_id', $b->id)->firstOrFail();
        $this->assertSame(['tatap_muka' => 1], $log->old_values);
        $this->assertSame(['tatap_muka' => 2], $log->new_values);
    }
}
