<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Laporan periodik memakai target tersimpan (diisi guru) untuk menilai tuntas.
 */
class PeriodicReportTargetTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $class12;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        Surah::firstOrCreate(
            ['number' => 2],
            ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286, 'juz_start' => 1, 'juz_end' => 3]
        );

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        // Rabu saja: Juli 5, Agustus 4, September 5 pertemuan (default libur nasional tidak jatuh di Rabu).
        $this->class12 = ClassRoom::create([
            'program_id' => $program->id,
            'name' => 'Kelas XII F3',
            'level' => 'XII',
            'tahfizh_days' => [3],
        ]);
        $this->student->update(['class_room_id' => $this->class12->id, 'tahfizh_level' => 'reguler']);
    }

    private function setoran(string $date, int $ayahStart = 1, int $ayahEnd = 3): void
    {
        $record = HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => $date,
        ]);
        $record->surahs()->create([
            'surah_id' => $this->surah->id,
            'ayah_start' => $ayahStart,
            'ayah_end' => $ayahEnd,
            'submission_type' => 'new',
            'status' => 'passed',
        ]);
    }

    #[Test]
    public function periodic_report_target_lines_are_meetings_times_level(): void
    {
        $this->setoran('2026-09-09', 1, 7); // Al-Fatihah sampai ayat 7 -> hanya 7 baris

        HafalanTarget::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id,
            'ayah' => 5,
            'target_date' => '2026-09-15',
            'status' => 'active',
        ]);

        $query = ['class_room_id' => $this->class12->id, 'period_type' => 'monthly', 'month' => 9, 'year' => 2026];
        $report = fn () => collect($this->actingAs($this->admin)->get(route('reports.periodic', $query))->viewData('studentReports'))
            ->first(fn ($r) => $r['student']->id === $this->student->id);

        // Target baris = 5 pertemuan (Rabu September) x 5 baris = 25; capaian 7 baris walau
        // surah target (Al-Fatihah 5) sudah terlewati -> belum tuntas.
        $row = $report();
        $this->assertSame(25, $row['target_baris']);
        $this->assertFalse($row['is_tuntas']);

        // Tambah setoran lulus dengan baris yang cukup -> tuntas.
        $record = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => '2026-09-16']);
        $record->surahs()->create(['surah_id' => Surah::where('number', 2)->value('id'), 'ayah_start' => 1, 'ayah_end' => 10, 'submission_type' => 'new', 'status' => 'passed', 'baris' => 20]);
        $this->assertTrue($report()['is_tuntas']);
    }
}
