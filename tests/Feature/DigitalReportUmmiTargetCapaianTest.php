<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Surah;
use App\Models\UmmiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Tabel Tahfizh rapor cetak = satu baris untuk triwulan rapor, sama dengan Target Triwulan /
 * Capaian Akhir di Laporan Triwulan; ayat & halaman cukup angka terakhirnya.
 * - Kelas 10/Ummi: Jilid | Hal. | Surah | Ayat, Status, lalu Nilai di atas Deskripsi.
 * - Kelas 11/12: Surah | Ayat, Baris capaian/target, Status, Deskripsi -- tanpa nilai.
 */
class DigitalReportUmmiTargetCapaianTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-11-10'); // Semester I (triwulan Okt-Des 2026/2027)
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function printTerm2()
    {
        return $this->actingAs($this->admin)->get(route('digital-reports.print', [$this->student, 'academic_year' => '2026/2027', 'term' => 2]));
    }

    #[Test]
    public function ummi_rapor_shows_one_term_row_with_book_position_and_score(): void
    {
        $program = Program::create(['name' => 'Tahfizh Kelas 10', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas X E1',
            'level' => 'X',
        ]);
        $this->student->update([
            'class_room_id' => $classRoom->id,
            'tahfizh_level' => 'ummi',
        ]);

        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'ummi_jilid' => 'Jilid 2',
            'halaman_buku' => '24-25',
            'surah_id' => $this->surah->id,
            'ayah' => null,
            'target_date' => now(),
            'status' => 'active',
        ]);

        // Hafalan yang dicatat di dalam sesi UMMI itu sendiri ("Tahfizh Ummi").
        $anNaba = Surah::firstOrCreate(
            ['number' => 78],
            ['name_ar' => 'النبأ', 'name_latin' => 'An-Naba', 'total_ayah' => 40, 'juz_start' => 30, 'juz_end' => 30]
        );
        $ummiRecord = UmmiRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tanggal' => now(),
            'tatap_muka' => 1,
            'ummi_jilid' => 'Jilid 2',
            'ummi_halaman' => '24-25',
            'nilai' => 'B',
        ]);
        $ummiRecord->surahs()->create([
            'surah_id' => $anNaba->id,
            'hafalan_ayah' => '1-5',
        ]);

        // Setoran hafalan mandiri/terpisah dari sesi UMMI ("Tahfizh Mandiri").
        $anNas = Surah::firstOrCreate(
            ['number' => 114],
            ['name_ar' => 'الناس', 'name_latin' => 'An-Nas', 'total_ayah' => 6, 'juz_start' => 30, 'juz_end' => 30]
        );
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => now(),
        ]);
        $record->surahs()->create([
            'surah_id' => $anNas->id,
            'ayah_start' => 1,
            'ayah_end' => 6,
            'submission_type' => 'new',
            'status' => 'passed',
        ]);

        // Target triwulan lain tidak ikut tampil (dulu 5 target sekaligus).
        HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'ummi_jilid' => 'Jilid 1', 'halaman_buku' => '8', 'target_date' => '2026-08-10', 'status' => 'active',
        ]);

        $response = $this->printTerm2()->assertOk();

        $response->assertSee('TARGET TRIWULAN')->assertSee('CAPAIAN AKHIR')->assertSee('NILAI &amp; DESKRIPSI', false);
        $response->assertDontSee('Jilid 1');
        // Target: Jilid 2, halaman rentang "24-25" -> 25, surah target; Capaian: Jilid 2 hal 25,
        // hafalan sesi Ummi An-Naba "1-5" -> ayat 5; posisi buku sampai target -> Tuntas.
        $response->assertSeeInOrder(['Jilid 2', '25', $this->surah->name_latin, 'Jilid 2', '25', 'An-Naba', '5', 'Tuntas', '/ 100']);
        $response->assertDontSee('1-5');
        $response->assertDontSee('Nilai Akhir Tahfizh');
    }

    #[Test]
    public function tahfizh_score_predicate_follows_the_adab_scale(): void
    {
        $cases = [95 => 'Mumtaz (Sangat Baik)', 85 => 'Jayyid Jiddan (Baik Sekali)', 75 => 'Jayyid (Baik)', 65 => 'Maqbul (Cukup)', 50 => "Dha'if (Kurang)"];
        foreach ($cases as $score => $predicate) {
            $this->assertSame($predicate, Setting::getAdabGradeLabel(Setting::getAdabGrade($score)));
        }

        $this->student->update(['tahfizh_level' => 'ummi']);
        $score = (float) Setting::calculateTahfizhScore($this->student->fresh())['final_score'];

        $this->printTerm2()->assertOk()->assertSeeInOrder(['/ 100', Setting::getAdabGradeLabel(Setting::getAdabGrade($score))]);
    }

    #[Test]
    public function reguler_rapor_shows_lines_and_no_score(): void
    {
        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 10,
            'target_date' => now(),
            'status' => 'active',
        ]);

        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => now(),
        ]);
        $record->surahs()->create([
            'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 7,
            'submission_type' => 'new', 'status' => 'passed',
        ]);

        $response = $this->printTerm2()->assertOk();

        $response->assertSee('BARIS')->assertDontSee('NILAI &amp; DESKRIPSI', false)->assertDontSee('/ 100');
        $response->assertSeeInOrder(['TARGET TRIWULAN', 'CAPAIAN AKHIR', $this->surah->name_latin, '10', $this->surah->name_latin, '7']);
        $response->assertDontSee('1-7');
        $response->assertDontSee('Jilid');
    }
}
