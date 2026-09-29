<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Surah;
use App\Services\AutoHafalanTargetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Regresi untuk bug: kolom "Capaian Baris" murid Kelas 11 & 12 di Laporan Triwulan
 * (Term/Indeks) memakai cutoff akhir triwulan (termasuk sisa triwulan yang belum
 * berjalan), sedangkan Target Triwulan (AutoHafalanTargetService::termPlan) dan
 * rapor memakai cutoff hari ini -- membuat kedua halaman bisa menampilkan angka
 * berbeda untuk murid yang sama walau triwulan belum selesai. Sejak perbaikan,
 * keduanya harus selalu sama: setoran bertanggal SETELAH hari ini tidak ikut
 * dihitung di manapun.
 */
class QuarterlyReportTermCutoffTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-09-29');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function future_dated_setoran_within_the_term_is_excluded_from_capaian_baris_like_target_triwulan(): void
    {
        $program = Program::create(['name' => 'Program Tahfizh Test', 'status' => 'active']);
        $classRoom = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'XI F9',
            'level' => 'XI',
            'tahfizh_days' => [1, 2, 3, 4, 5],
        ]);
        $this->student->update([
            'class_room_id' => $classRoom->id,
            'teacher_id' => $this->teacherProfile->id,
            'tahfizh_level' => 'akselerasi',
        ]);

        $baqarah = Surah::firstOrCreate(
            ['number' => 2],
            ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286, 'juz_start' => 1, 'juz_end' => 3]
        );

        // Setoran pertama triwulan ini (titik awal) -- "hari ini", ikut dihitung.
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-07-06',
        ])->surahs()->create([
            'surah_id' => $baqarah->id,
            'ayah_start' => 1,
            'ayah_end' => 10,
            'submission_type' => 'new',
            'status' => 'passed',
            'baris' => 50,
        ]);

        // Setoran bertanggal SETELAH hari ini (akhir triwulan, belum benar-benar terjadi) --
        // tidak boleh ikut dihitung di manapun.
        HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-09-30',
        ])->surahs()->create([
            'surah_id' => $baqarah->id,
            'ayah_start' => 20,
            'ayah_end' => 25,
            'submission_type' => 'new',
            'status' => 'passed',
            'baris' => 4,
        ]);

        // Target Triwulan (sumber acuan): cutoff = hari ini.
        $plan = app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-07-01'));
        $this->assertSame(50.0, $plan['achieved_lines']);

        // Laporan Triwulan harus menampilkan angka yang SAMA, bukan 54.
        $response = $this->actingAs($this->admin)->get(route('reports.quarterly.export', [
            'class_room_id' => $classRoom->id,
            'academic_year' => '2026/2027',
            'term' => '1',
        ]));
        $response->assertStatus(200);

        $tmpPath = tempnam(sys_get_temp_dir(), 'qrtc').'.xlsx';
        file_put_contents($tmpPath, $response->streamedContent());
        $spreadsheet = IOFactory::load($tmpPath);
        unlink($tmpPath);

        $rows = $spreadsheet->getSheetByName('Term-Indeks')->toArray();
        $studentRow = collect($rows)->firstWhere(1, $this->student->name);
        $this->assertNotNull($studentRow);
        // [No, Nama, Level, Awal Triwulan, Target Surah, Target Ayat, Capaian Surah, Capaian Ayat, Capaian Baris, ...]
        $this->assertSame('Al-Baqarah : 1', $studentRow[3], 'Awal triwulan = setoran pertama triwulan ini.');
        $this->assertSame('50', (string) $studentRow[8], 'Capaian baris tidak boleh ikut menghitung setoran bertanggal setelah hari ini.');
    }
}
