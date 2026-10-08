<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\UmmiRecord;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Rapor cetak Kelas 10 Program Tahfizh (mis. X E1): A. Metode Ummi, B. Hafalan Mandiri per bulan,
 * C. Nilai Tahfizh. Kelas 10 Program Reguler tetap satu tabel Ummi + nilai.
 */
class DigitalReportMandiriTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 09:00');
        $this->setUpHafizPlusData();
        UmmiRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'tanggal' => '2026-09-10', 'tatap_muka' => 1, 'ummi_jilid' => 'Jilid 2', 'ummi_halaman' => '25']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function moveTo(string $program, string $class): void
    {
        $program = Program::create(['name' => $program, 'status' => 'active']);
        $class = ClassRoom::create(['program_id' => $program->id, 'name' => $class, 'level' => 'X']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'ummi']);
    }

    private function setor(string $date, int $start, int $end): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new', 'baris' => $end - $start + 1]);
    }

    private function print()
    {
        return $this->actingAs($this->admin)->get(route('digital-reports.print', [$this->student, 'academic_year' => '2026/2027', 'term' => 1]))->assertOk();
    }

    #[Test]
    public function tahfizh_program_grade_ten_shows_mandiri_table_per_month_and_score_below(): void
    {
        $this->moveTo('Program Tahfizh', 'X E1');
        $this->setor('2026-07-14', 1, 3);
        $this->setor('2026-07-21', 4, 5);
        $this->setor('2026-08-04', 1, 5); // ulangan: setoran tercatat, baris tidak bertambah
        $this->setor('2026-08-11', 6, 7);

        $this->print()->assertSeeInOrder([
            'A. Metode Ummi', 'Jilid 2', 'Tuntas',
            'B. Hafalan Mandiri (Ziyadah)',
            'Juli', 'Al-Fatihah 1-5', '2 kali', '5',
            'Agustus', 'Al-Fatihah 1-7', '2 kali', '2',
            'September', '-',
            'Jumlah', '4 kali', '7',
            'C. Nilai Tahfizh', 'NILAI', 'DESKRIPSI',
        ]);
    }

    #[Test]
    public function regular_program_grade_ten_keeps_the_single_ummi_table(): void
    {
        $this->moveTo('Program Reguler', 'X E2');
        $this->setor('2026-07-14', 1, 3);

        $this->print()->assertSee('NILAI')->assertDontSee('Hafalan Mandiri')->assertDontSee('A. Metode Ummi');
    }
}
