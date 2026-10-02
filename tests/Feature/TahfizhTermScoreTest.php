<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Setting;
use App\Models\TahfizhExam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Tahfizh rapor per triwulan: nilai dari target & ujian triwulan rapor (status tuntas = ketuntasan
 * triwulan), ringkasan setoran triwulan di halaman rapor santri.
 */
class TahfizhTermScoreTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 09:00');
        $this->setUpHafizPlusData();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setoran(string $date, int $score): void
    {
        HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date])
            ->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 7, 'submission_type' => 'new', 'status' => 'passed', 'score' => $score]);
    }

    #[Test]
    public function score_and_stats_use_only_the_report_term(): void
    {
        $config = Setting::getTahfizhScoringConfig();
        HafalanTarget::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'surah_id' => $this->surah->id, 'ayah' => 7, 'target_date' => '2026-08-31', 'status' => 'completed']);
        HafalanTarget::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'surah_id' => $this->surah->id, 'ayah' => 7, 'target_date' => '2026-10-10', 'status' => 'active']);
        TahfizhExam::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'total_score' => 30, 'exam_date' => '2026-09-20']);
        TahfizhExam::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'total_score' => 45, 'exam_date' => '2026-10-12']);

        $term1 = Setting::calculateTahfizhScore($this->student, Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30'), false);
        $this->assertSame((float) ($config['target_incomplete_score'] + 30), $term1['final_score'], 'Target Agustus (status triwulan tidak tuntas) + ujian September.');
        $this->assertSame('Belum Tuntas', $term1['target_label']);

        $term1Done = Setting::calculateTahfizhScore($this->student, Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30'), true);
        $this->assertSame((float) ($config['target_weight'] + 30), $term1Done['final_score']);

        $this->setoran('2026-08-05', 80);
        $this->setoran('2026-09-05', 90);
        $this->setoran('2026-10-05', 60);  // triwulan 2: tidak ikut

        $stats = $this->actingAs($this->admin)->get(route('digital-reports.show', [$this->student, 'academic_year' => '2026/2027', 'term' => 1]))
            ->assertOk()->assertSee('Akumulasi Triwulan 1 (Jul - Sep)')->viewData('tahfizhTermStats');
        $this->assertSame(2, $stats['setoran']);
        $this->assertEquals(85, $stats['average_score']);
    }
}
