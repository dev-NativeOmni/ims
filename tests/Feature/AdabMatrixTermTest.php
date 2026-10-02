<?php

namespace Tests\Feature;

use App\Models\AdabMentorAssessment;
use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Matriks Presensi Harian Adab periode triwulan: semua tanggal 3 bulan dalam satu matriks (sel per
 * tanggal, bisa diklik untuk diisi), nilai pendamping = rata-rata bulan yang dinilai, murid sesuai
 * riwayat kelas; kartu hijau mengikuti Pengaturan Adab.
 */
class AdabMatrixTermTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 09:00');
        $this->setUpHafizPlusData();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function matrix(array $query)
    {
        return $this->actingAs($this->admin)->getJson(route('adab.attendance-matrix-data', ['class_room_id' => $this->student->class_room_id] + $query));
    }

    #[Test]
    public function term_period_covers_three_months_keyed_by_date(): void
    {
        $firstEffective = array_key_first(Setting::getEffectiveDatesSet(2026, 8));
        AdabRecord::create(['student_id' => $this->student->id, 'assessment_date' => $firstEffective, 'student_score' => 100, 'total_score' => 100]);
        foreach ([[7, 80], [8, 90]] as [$month, $score]) {
            AdabMentorAssessment::create(['student_id' => $this->student->id, 'mentor_id' => $this->admin->id, 'year' => 2026, 'month' => $month, 'mentor_score' => $score]);
        }

        $data = $this->matrix(['period' => 'term', 'term' => 1, 'academic_year' => '2026/2027'])->assertOk()->json();

        $this->assertSame('term', $data['period']);
        $this->assertStringContainsString('Triwulan 1 · 2026/2027', $data['period_label']);
        $this->assertCount(92, $data['days_metadata'], '1 Juli s.d. 30 September.');
        $this->assertSame(['2026-07-01', '2026-09-30'], [$data['days_metadata'][0]['date'], end($data['days_metadata'])['date']]);

        $row = collect($data['students'])->firstWhere('student_id', $this->student->id);
        $this->assertSame('filled', $row['daily_status'][$firstEffective]['status']);
        $this->assertSame(85, $row['mentor_score'], 'Rata-rata Juli (80) & Agustus (90).');
        $this->assertFalse($row['has_mentor_scored'], 'September belum dinilai.');
        $this->assertArrayHasKey('date', $row['missed_dates'][0]);
        $this->assertStringContainsString('Jul', $row['missed_dates'][0]['label']);
    }

    #[Test]
    public function monthly_period_still_works_and_term_follows_class_history(): void
    {
        $data = $this->matrix(['year' => 2026, 'month' => 9])->assertOk()->json();
        $this->assertSame('month', $data['period']);
        $this->assertCount(30, $data['days_metadata']);

        // Pindah kelas 5 Oktober: triwulan 1 kelas lama masih memuat murid ini.
        $oldClass = $this->student->class_room_id;
        $newClass = ClassRoom::create(['program_id' => $this->student->classRoom->program_id, 'name' => 'Kelas Baru', 'level' => 'Pemula']);
        Carbon::setTestNow('2026-10-05 09:00');
        $this->student->update(['class_room_id' => $newClass->id]);
        Carbon::setTestNow('2026-10-20 09:00');

        $term1 = $this->actingAs($this->admin)->getJson(route('adab.attendance-matrix-data', ['class_room_id' => $oldClass, 'period' => 'term', 'term' => 1, 'academic_year' => '2026/2027']))->json('students');
        $this->assertContains($this->student->id, collect($term1)->pluck('student_id')->all());
    }

    #[Test]
    public function hero_card_follows_adab_settings(): void
    {
        Setting::set('adab_scoring', json_encode(['attendance_weight' => 30]));

        $this->actingAs($this->admin)->get(route('adab.index'))->assertOk()
            ->assertSee('dengan bobot 30% dan penilaian pendamping adab dengan bobot 70%')
            ->assertSee(count(Setting::getAdabQuestions()).' modul kuisioner mandiri murid')
            ->assertDontSee('bobot 50%');
    }
}
