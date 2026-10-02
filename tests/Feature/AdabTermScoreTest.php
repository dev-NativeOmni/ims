<?php

namespace Tests\Feature;

use App\Models\AdabMentorAssessment;
use App\Models\AdabRecord;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Nilai Adab rapor = akumulasi triwulan rapor (Setting::calculateAdabScoreForRange), bukan bulan
 * berjalan: kuisioner & nilai pendamping hanya dari triwulan itu.
 */
class AdabTermScoreTest extends TestCase
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

    private function fill(array $dates): void
    {
        foreach ($dates as $date) {
            AdabRecord::create(['student_id' => $this->student->id, 'assessment_date' => $date, 'student_score' => 100, 'total_score' => 100]);
        }
    }

    private function mentor(int $month, int $score): void
    {
        AdabMentorAssessment::create(['student_id' => $this->student->id, 'mentor_id' => $this->admin->id, 'year' => 2026, 'month' => $month, 'mentor_score' => $score]);
    }

    #[Test]
    public function term_score_uses_only_that_term(): void
    {
        $termDates = array_keys(Setting::getEffectiveDatesSet(2026, 7) + Setting::getEffectiveDatesSet(2026, 8) + Setting::getEffectiveDatesSet(2026, 9));
        $half = array_slice($termDates, 0, intdiv(count($termDates), 2));
        $this->fill($half);
        $this->fill(array_slice(array_keys(Setting::getEffectiveDatesSet(2026, 10, '2026-10-15')), 0, 3)); // Oktober: tidak ikut
        $this->mentor(7, 80);
        $this->mentor(8, 90);
        $this->mentor(10, 50); // Oktober: tidak ikut

        $score = Setting::calculateAdabScoreForRange($this->student->id, Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30'));

        $this->assertSame([count($half), count($termDates)], [$score['effective_days_filled'], $score['effective_days_total']]);
        $this->assertSame(85.0, $score['mentor_score'], 'Rata-rata Juli & Agustus.');
        $this->assertSame(Setting::adabCompositeScore($score['attendance_rate'], 85.0), $score['final_score']);

        // Rapor Tengah Semester I memakai nilai triwulan itu, bukan bulan berjalan (Oktober).
        $report = $this->actingAs($this->admin)->get(route('digital-reports.show', [$this->student, 'academic_year' => '2026/2027', 'term' => 1]));
        $report->assertOk()->assertSee('Akumulasi Triwulan 1 (Jul - Sep)');
        $this->assertEquals($score['final_score'], $report->viewData('avgTotal'));
        $this->assertEquals(85.0, $report->viewData('avgMentorScore'));
    }

    #[Test]
    public function running_term_counts_effective_days_until_today(): void
    {
        $octoberSoFar = array_keys(Setting::getEffectiveDatesSet(2026, 10, '2026-10-15'));
        $this->fill($octoberSoFar);

        $score = Setting::calculateAdabScoreForRange($this->student->id, Carbon::parse('2026-10-01'), Carbon::parse('2026-12-31'));

        $this->assertSame(count($octoberSoFar), $score['effective_days_total'], 'Hari setelah hari ini tidak dihitung.');
        $this->assertSame(100.0, $score['attendance_rate']);
        $this->assertNull($score['mentor_score']);
        $this->assertSame(100.0, $score['final_score'], 'Tanpa nilai pendamping = kerajinan saja.');
    }
}
