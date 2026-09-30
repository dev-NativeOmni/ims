<?php

namespace Tests\Feature;

use App\Http\Controllers\QuarterlyReportController;
use App\Models\HafalanRecord;
use App\Models\UmmiRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class DefaultHafalanScoreTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function hafalanRecord(): HafalanRecord
    {
        return HafalanRecord::create([
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'submitted_at' => '2026-09-10',
        ]);
    }

    private function ummiRecord(array $attributes = []): UmmiRecord
    {
        return UmmiRecord::create($attributes + [
            'student_id' => $this->student->id,
            'teacher_id' => $this->teacherProfile->id,
            'tatap_muka' => 1,
            'tanggal' => '2026-09-10',
        ]);
    }

    #[Test]
    public function empty_scores_default_to_b_when_saved(): void
    {
        $surah = $this->hafalanRecord()->surahs()->create([
            'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 3, 'submission_type' => 'new', 'status' => 'passed',
        ]);
        $this->assertEquals(85, $surah->fresh()->score);
        $this->assertSame('B', $surah->fresh()->score_letter);

        // Nilai yang diisi guru tidak ditimpa.
        $surah->update(['score' => 95]);
        $this->assertSame('A', $surah->fresh()->score_letter);

        $this->assertSame('B', $this->ummiRecord()->fresh()->nilai);
        $this->assertSame('A', $this->ummiRecord(['nilai' => 'A', 'tatap_muka' => 2])->fresh()->nilai);
    }

    #[Test]
    public function report_grade_uses_the_same_scale_as_teacher_input(): void
    {
        $this->assertSame('A', QuarterlyReportController::mapScoreToGrade(95));
        $this->assertSame('B', QuarterlyReportController::mapScoreToGrade(85));
        $this->assertSame('C', QuarterlyReportController::mapScoreToGrade(75));
        $this->assertSame('D', QuarterlyReportController::mapScoreToGrade(65));
        $this->assertSame('E', QuarterlyReportController::mapScoreToGrade(55));
        $this->assertSame('B', QuarterlyReportController::mapScoreToGrade(null));
    }

    #[Test]
    public function migration_fills_existing_empty_scores_with_b(): void
    {
        $record = $this->hafalanRecord();
        // Data lama tersimpan tanpa nilai (lewat query langsung, tanpa default model).
        DB::table('hafalan_record_surahs')->insert([
            ['hafalan_record_id' => $record->id, 'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 2, 'submission_type' => 'new', 'status' => 'passed', 'score' => null, 'sort_order' => 1],
            ['hafalan_record_id' => $record->id, 'surah_id' => $this->surah->id, 'ayah_start' => 3, 'ayah_end' => 4, 'submission_type' => 'new', 'status' => 'passed', 'score' => 75, 'sort_order' => 2],
        ]);
        $ummi = $this->ummiRecord(['nilai' => 'A']);
        DB::table('ummi_records')->where('id', $ummi->id)->update(['nilai' => null]);

        (require database_path('migrations/2026_09_30_000001_fill_empty_hafalan_scores_with_b.php'))->up();

        $this->assertEquals([85, 75], DB::table('hafalan_record_surahs')->orderBy('sort_order')->pluck('score')->map(fn ($s) => (float) $s)->all());
        $this->assertSame('B', $ummi->fresh()->nilai);
    }
}
