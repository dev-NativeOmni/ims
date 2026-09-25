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
    public function periodic_report_marks_tuntas_when_capaian_reaches_the_target_position(): void
    {
        $this->setoran('2026-09-09', 1, 7); // Al-Fatihah sampai ayat 7 -> hanya 7 baris

        $target = HafalanTarget::create([
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

        // Baris belum memenuhi target (5 pertemuan x 5 = 25), tapi ayat 7 >= target ayat 5.
        $row = $report();
        $this->assertLessThan($row['target_baris'], $row['capaian_baris']);
        $this->assertTrue($row['is_tuntas']);

        // Target di surah yang lebih jauh (Al-Baqarah) belum tercapai.
        $baqarah = Surah::where('number', 2)->first();
        $target->update(['surah_id' => $baqarah->id, 'ayah' => 10]);
        $this->assertFalse($report()['is_tuntas']);
    }
}
