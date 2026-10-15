<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Surah;
use App\Services\AcademicCalendarService;
use App\Services\HafalanProgressService;
use App\Services\LineSavingsService;
use App\Support\AcademicYear;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Tabungan baris (LineSavingsService): kelebihan triwulan lalu menutup kekurangan triwulan ini bila cukup.
 */
class LineSavingsTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private Surah $baqarah;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-12-20 09:00');
        $this->setUpHafizPlusData();
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $class = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI F2', 'level' => 'XI']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'reguler']);
        $this->baqarah = Surah::firstOrCreate(['number' => 2], ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286, 'juz_start' => 1, 'juz_end' => 3]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setor(string $date, int $start, int $end, float $baris): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => $this->baqarah->id, 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new', 'baris' => $baris]);
    }

    private function termTarget(int $term): float
    {
        [$start, $end] = AcademicYear::termRange('2026/2027', $term);
        $student = $this->student->fresh('classRoom.program');

        return (float) app(HafalanProgressService::class)->termBreakdown($student, collect(), app(AcademicCalendarService::class)->termMonths($start), $end->copy()->endOfDay(), null, false)['evaluation']['target_lines'];
    }

    private function printTerm2()
    {
        return $this->actingAs($this->admin)->get(route('digital-reports.print', [$this->student, 'academic_year' => '2026/2027', 'term' => 2]))->assertOk();
    }

    #[Test]
    public function apply_uses_savings_only_when_enough_and_caps_the_balance(): void
    {
        $this->assertSame(['before' => 0.0, 'used' => 0.0, 'deposit' => 15.0, 'after' => 15.0], LineSavingsService::apply(64, 49, 0));
        $this->assertSame(['before' => 15.0, 'used' => 5.0, 'deposit' => 0.0, 'after' => 10.0], LineSavingsService::apply(35, 40, 15));
        $this->assertSame(['before' => 3.0, 'used' => 0.0, 'deposit' => 0.0, 'after' => 3.0], LineSavingsService::apply(35, 40, 3), 'Tidak cukup: saldo utuh.');
        $this->assertSame(40.0, LineSavingsService::apply(500, 40, 10)['after'], 'Saldo maksimal satu target triwulan.');
    }

    #[Test]
    public function surplus_of_term_one_makes_a_short_term_two_tuntas(): void
    {
        $target1 = $this->termTarget(1);
        $target2 = $this->termTarget(2);
        $this->assertGreaterThan(5, $target2);
        $this->setor('2026-07-14', 1, 50, $target1 + 10);   // Triwulan 1: lebih 10
        $this->setor('2026-10-14', 51, 60, $target2 - 5);   // Triwulan 2: kurang 5

        $this->printTerm2()->assertSee('+5 dari tabungan · sisa 5')->assertSee('Tuntas')->assertDontSee('Tidak Tuntas');
    }

    #[Test]
    public function savings_smaller_than_the_shortfall_are_kept_and_term_stays_not_tuntas(): void
    {
        $target1 = $this->termTarget(1);
        $target2 = $this->termTarget(2);
        $this->setor('2026-07-14', 1, 50, $target1 + 2);    // lebih 2
        $this->setor('2026-10-14', 51, 60, $target2 - 5);   // kurang 5

        $this->printTerm2()->assertSee('saldo tabungan 2')->assertSee('Tidak Tuntas');
    }
}
