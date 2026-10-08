<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Laporan Triwulan: baris di jurnal pekanan = baris ayat baru (sama dengan Capaian Baris bulan),
 * dan setoran ulangan ditandai -- jadi bulan berisi setoran ulang tidak lagi tampak "0 padahal ada setoran".
 */
class QuarterlyReportNewLinesTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    private Surah $baqarah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->baqarah = Surah::firstOrCreate(['number' => 2], ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286]);
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI F3', 'level' => 'XI', 'tahfizh_days' => [3]]);
        $this->student->update(['class_room_id' => $this->classRoom->id, 'teacher_id' => $this->teacherProfile->id, 'tahfizh_level' => 'reguler', 'hafalan_direction' => 'front_29']);
    }

    private function setor(string $date, int $from, int $to): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => $this->baqarah->id, 'ayah_start' => $from, 'ayah_end' => $to, 'submission_type' => 'new', 'status' => 'passed']);
    }

    #[Test]
    public function journal_lines_count_only_new_ayat_and_mark_repeats(): void
    {
        $this->setor('2026-07-08', 1, 20);
        $this->setor('2026-09-02', 1, 20);   // ulang penuh
        $this->setor('2026-09-09', 15, 30);  // sebagian ulang: 21-30 baru

        $september = $this->actingAs($this->admin)->get(route('reports.quarterly', [
            'class_room_id' => $this->classRoom->id, 'academic_year' => '2026/2027', 'term' => '1',
        ]))->assertOk()->viewData('halaqahData')[0]['monthly']['09']['reguler_records'][0];

        $newLines = round(ReportController::calculateLines(2, 21, 30, 286), 1);
        $this->assertSame($newLines, round((float) $september['total_lines'], 1), 'Capaian bulan = ayat baru saja.');
        $this->assertSame($newLines, round(collect($september['pekan'])->sum('baris'), 1), 'Jumlah baris jurnal pekanan = capaian bulan.');

        $setoran = collect($september['pekan'])->pluck('setoran')->filter()->implode(' | ');
        $this->assertStringContainsString('Al-Baqarah 1-20 (ulang)', $setoran);
        $this->assertStringContainsString('Al-Baqarah 15-30 (sebagian ulang)', $setoran);
    }
}
