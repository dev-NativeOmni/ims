<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\StudentReport;
use App\Support\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Tahun ajaran dipakai mulai 2026/2027: tahun sebelumnya tidak ditawarkan, diganti
 * tahun aktif bila diminta, dan datanya bisa dihapus lewat tad:hapus-tahun-ajaran-lama.
 */
class AcademicYearTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    #[Test]
    public function years_before_first_are_invalid_and_not_offered(): void
    {
        Carbon::setTestNow('2026-10-01');

        $this->assertFalse(AcademicYear::isValid('2025/2026'));
        $this->assertFalse(AcademicYear::isValid('2026/2028'));
        $this->assertTrue(AcademicYear::isValid('2026/2027'));
        $this->assertSame(['2026/2027'], AcademicYear::options());
        $this->assertSame('2026/2027', AcademicYear::forDate(Carbon::parse('2026-03-01')));

        Setting::set('academic_year', '2025/2026');
        $this->assertSame('2026/2027', AcademicYear::active());

        Carbon::setTestNow('2027-08-01');
        $this->assertSame(['2027/2028', '2026/2027'], AcademicYear::options());

        Carbon::setTestNow();
    }

    #[Test]
    public function old_year_requests_fall_back_to_active_year(): void
    {
        $this->actingAs($this->admin)->get(route('digital-reports.show', [$this->student, 'academic_year' => '2025/2026']))
            ->assertOk()->assertDontSee('2025/2026');
        $this->assertFalse(StudentReport::where('academic_year', '2025/2026')->exists());

        $this->actingAs($this->admin)->get(route('reports.quarterly', ['academic_year' => '2025/2026']))
            ->assertDontSee('2025/2026');
    }

    #[Test]
    public function settings_reject_years_before_first(): void
    {
        $this->actingAs($this->admin)->post(route('digital-reports.settings.update'), [
            'academic_year' => '2025/2026', 'report_main_title' => 'L', 'report_school_name' => 'S', 'report_city' => 'K',
        ])->assertSessionHasErrors('academic_year');
    }

    #[Test]
    public function command_deletes_old_year_data_only_when_not_dry_run(): void
    {
        StudentReport::create(['student_id' => $this->student->id, 'academic_year' => '2025/2026', 'semester' => 1, 'term' => 1, 'teacher_notes' => 'Lama', 'status' => 'draft']);
        StudentReport::create(['student_id' => $this->student->id, 'academic_year' => '2026/2027', 'semester' => 1, 'term' => 1, 'status' => 'draft']);
        Setting::set('report_blp_dates_2025-2026', '{"1_asts":"2025-10-10"}');
        Setting::set('report_blp_dates_2026-2027', '{"1_asts":"2026-10-10"}');
        Setting::set('academic_year', '2025/2026');

        $this->artisan('tad:hapus-tahun-ajaran-lama', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(2, StudentReport::count());

        $this->artisan('tad:hapus-tahun-ajaran-lama', ['--force' => true])->assertSuccessful();
        $this->assertSame(['2026/2027'], StudentReport::pluck('academic_year')->all());
        $this->assertNull(Setting::get('report_blp_dates_2025-2026'));
        $this->assertNotNull(Setting::get('report_blp_dates_2026-2027'));
        $this->assertSame('2026/2027', Setting::get('academic_year'));
    }
}
