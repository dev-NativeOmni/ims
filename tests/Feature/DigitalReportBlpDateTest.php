<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentReportController;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Titimangsa rapor memakai Tanggal BLP dari Pengaturan Rapor: ASTS untuk triwulan
 * pertama semester, ASAS (sem. 1) / ASAT (sem. 2) untuk triwulan kedua.
 */
class DigitalReportBlpDateTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function saveBlp(array $dates): void
    {
        $this->actingAs($this->admin)->post(route('digital-reports.settings.update'), [
            'academic_year' => '2026/2027', 'blp_dates' => $dates,
            'report_main_title' => 'LAPORAN', 'report_school_name' => 'SMA', 'report_city' => 'Sukoharjo',
        ])->assertRedirect();
    }

    private function printed(int $semester, int $term)
    {
        return $this->actingAs($this->admin)->get(route('digital-reports.print', [
            $this->student, 'academic_year' => '2026/2027', 'semester' => $semester, 'term' => $term,
        ]));
    }

    #[Test]
    public function each_triwulan_uses_its_own_blp_date(): void
    {
        $this->saveBlp(['1_asts' => '2026-10-10', '1_asas' => '2026-12-19', '2_asts' => '2027-03-20', '2_asat' => '2027-06-19']);

        $this->printed(1, 1)->assertOk()->assertSee('Sukoharjo, 10 Oktober 2026');
        $this->printed(1, 2)->assertSee('Sukoharjo, 19 Desember 2026');
        $this->printed(2, 3)->assertSee('Sukoharjo, 20 Maret 2027');
        $this->printed(2, 4)->assertSee('Sukoharjo, 19 Juni 2027');
    }

    #[Test]
    public function unset_blp_date_prints_dots_instead_of_today(): void
    {
        Carbon::setTestNow('2026-11-05');

        $date = StudentReportController::reportDate('2026/2027', 1, 2);
        $this->assertNull($date['date']);
        $this->assertSame('ASAS', $date['exam']);
        $this->assertFalse($date['is_set']);
        $this->printed(1, 2)->assertOk()->assertSee('Sukoharjo, ....')->assertDontSee('05 November 2026');

        Carbon::setTestNow();
    }

    #[Test]
    public function blp_dates_are_stored_per_academic_year(): void
    {
        $this->saveBlp(['1_asts' => '2026-10-10']);

        $this->assertSame('2026-10-10', StudentReportController::blpDates('2026/2027')['1_asts']);
        $this->assertNull(StudentReportController::blpDates('2027/2028')['1_asts']);
        $this->actingAs($this->admin)->get(route('digital-reports.settings'))->assertOk()->assertSee('value="2026-10-10"', false);
    }

    #[Test]
    public function active_period_follows_blp_dates(): void
    {
        $this->saveBlp(['1_asts' => '2026-10-10', '1_asas' => '2026-12-19', '2_asts' => '2027-03-20', '2_asat' => '2027-06-19']);

        $activeOn = function (string $date) {
            Carbon::setTestNow($date);

            return StudentReportController::activePeriod();
        };

        $this->assertSame(1, $activeOn('2026-10-01'), 'Belum sampai ASTS: masih Tengah Semester I.');
        $this->assertSame(1, $activeOn('2026-10-10'), 'Hari BLP masih periode itu.');
        $this->assertSame(2, $activeOn('2026-10-11'));
        $this->assertSame(3, $activeOn('2026-12-20'));
        $this->assertSame(4, $activeOn('2027-03-21'));
        $this->assertSame(4, $activeOn('2027-07-05'), 'Lewat ASAT: tetap Semester II sampai tahun ajaran diganti.');

        Carbon::setTestNow('2026-10-11');
        $this->actingAs($this->admin)->get(route('digital-reports.settings'))->assertOk()
            ->assertSee('Otomatis dari tanggal BLP')
            ->assertSee('19 Desember 2026')
            ->assertSee('term=2', false);
        $this->actingAs($this->admin)->get(route('digital-reports.print', $this->student))->assertSee('Sukoharjo, 19 Desember 2026');
        $this->assertSame('1', Setting::get('semester'));

        Carbon::setTestNow();
    }

    #[Test]
    public function unset_blp_uses_end_of_triwulan_as_period_boundary(): void
    {
        $this->saveBlp([]);

        Carbon::setTestNow('2026-09-30');
        $this->assertSame(1, StudentReportController::activePeriod());
        Carbon::setTestNow('2026-10-01');
        $this->assertSame(2, StudentReportController::activePeriod());

        Carbon::setTestNow();
    }

    #[Test]
    public function print_header_shows_report_period(): void
    {
        $this->printed(1, 1)->assertSee('Tengah Semester I')->assertDontSee('1 (SATU)');
        $this->printed(1, 2)->assertSee('Semester I')->assertDontSee('Tengah Semester');
        $this->printed(2, 4)->assertSee('Semester II');
    }

    #[Test]
    public function identity_term_row_shows_roman_term_not_program(): void
    {
        $program = $this->student->classRoom?->program?->name;
        foreach ([1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'] as $term => $roman) {
            $response = $this->printed($term >= 3 ? 2 : 1, $term);
            $this->assertMatchesRegularExpression('#Term</td>\s*<td>:</td>\s*<td>'.$roman.'</td>#', $response->getContent());
            if ($program) {
                $response->assertDontSee('<td>'.$program.'</td>', false);
            }
        }
    }
}
