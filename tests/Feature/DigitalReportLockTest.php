<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentReportController;
use App\Models\ClassRoom;
use App\Models\Setting;
use App\Models\StudentPoint;
use App\Models\StudentReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Kunci rapor per kelas per periode: isi rapor dibekukan, cetak memakai simpanan itu
 * walau data/pengaturan berubah; buka kunci khusus Super Admin.
 */
class DigitalReportLockTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private const YEAR = '2026/2027';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        Setting::set(StudentReportController::blpSettingKey(self::YEAR), json_encode([
            '1_asts' => '2026-10-10', '1_asas' => '2026-12-19', '2_asts' => null, '2_asat' => null,
        ]));
    }

    private function lock(int $term = 1, $user = null)
    {
        return $this->actingAs($user ?? $this->admin)->post(route('digital-reports.class-lock', $this->student->class_room_id), [
            'academic_year' => self::YEAR, 'term' => $term,
        ]);
    }

    private function printed(int $term = 1)
    {
        return $this->actingAs($this->admin)->get(route('digital-reports.print', [$this->student, 'academic_year' => self::YEAR, 'term' => $term]));
    }

    private function violation(int $points, string $date): void
    {
        StudentPoint::create([
            'student_id' => $this->student->id, 'type' => 'violation', 'points' => $points,
            'title' => 'Pelanggaran', 'date' => $date, 'logged_by' => $this->admin->id,
        ]);
    }

    #[Test]
    public function locked_report_keeps_its_content_when_data_and_settings_change(): void
    {
        Setting::set('report_school_name', 'SEKOLAH LAMA');
        StudentReport::create(['student_id' => $this->student->id, 'academic_year' => self::YEAR, 'semester' => 1, 'term' => 1, 'teacher_notes' => 'Catatan beku', 'status' => 'draft']);

        $this->lock()->assertRedirect()->assertSessionHas('success');

        $report = StudentReport::where(['student_id' => $this->student->id, 'academic_year' => self::YEAR, 'term' => 1])->first();
        $this->assertTrue($report->isLocked());
        $this->assertSame($this->student->name, $report->snapshot['student']['name']);

        Setting::set('report_school_name', 'SEKOLAH BARU');
        $this->violation(25, '2026-08-10');
        $report->forceFill(['teacher_notes' => 'Diubah langsung'])->save();

        $this->printed(1)->assertOk()
            ->assertSee('SEKOLAH LAMA')->assertDontSee('SEKOLAH BARU')
            ->assertSee('Catatan beku')
            ->assertSee('Terkunci');

        // Periode lain tidak ikut terkunci.
        $this->printed(2)->assertOk()->assertSee('SEKOLAH BARU')->assertDontSee('Terkunci');
    }

    #[Test]
    public function notes_cannot_be_edited_once_locked(): void
    {
        $this->lock();

        $this->actingAs($this->admin)->post(route('digital-reports.update', $this->student), [
            'academic_year' => self::YEAR, 'term' => 1, 'teacher_notes' => 'Baru', 'status' => 'draft',
        ])->assertSessionHas('error');

        $this->assertNull(StudentReport::where(['student_id' => $this->student->id, 'term' => 1])->value('teacher_notes'));
        $this->actingAs($this->admin)->get(route('digital-reports.show', [$this->student, 'academic_year' => self::YEAR, 'term' => 1]))
            ->assertOk()->assertSee('dikunci');
    }

    #[Test]
    public function class_print_uses_snapshot_even_after_student_changes_class(): void
    {
        $originalClass = $this->student->class_room_id;
        $this->lock();

        $newClass = ClassRoom::create(['name' => 'XI-Baru', 'level' => 'XI']);
        $this->student->update(['class_room_id' => $newClass->id]);

        $this->actingAs($this->admin)->get(route('digital-reports.class-print', [$originalClass, 'academic_year' => self::YEAR, 'term' => 1]))
            ->assertOk()->assertSee($this->student->name)->assertDontSee('XI-Baru');
    }

    #[Test]
    public function only_super_admin_can_unlock(): void
    {
        $this->lock();
        $unlock = fn ($user) => $this->actingAs($user)->delete(route('digital-reports.class-unlock', $this->student->class_room_id), [
            'academic_year' => self::YEAR, 'term' => 1,
        ]);

        $unlock($this->admin)->assertForbidden();
        $this->assertTrue(StudentReport::where('term', 1)->first()->isLocked());

        $unlock($this->superAdmin)->assertRedirect();
        $report = StudentReport::where('term', 1)->first();
        $this->assertFalse($report->isLocked());
        $this->assertNull($report->snapshot);
    }

    #[Test]
    public function relocking_does_not_overwrite_an_existing_snapshot(): void
    {
        Setting::set('report_school_name', 'SEKOLAH LAMA');
        $this->lock();
        Setting::set('report_school_name', 'SEKOLAH BARU');
        $this->lock();

        $this->printed(1)->assertSee('SEKOLAH LAMA');
    }

    #[Test]
    public function settings_page_shows_lock_status_for_chosen_period(): void
    {
        $this->lock(2);

        $this->actingAs($this->superAdmin)->get(route('digital-reports.settings', ['print_year' => self::YEAR, 'print_term' => 2]))
            ->assertOk()->assertSee('Buka Kunci');
        $this->actingAs($this->admin)->get(route('digital-reports.settings', ['print_year' => self::YEAR, 'print_term' => 1]))
            ->assertOk()->assertSee('Kunci Rapor Tengah Semester I')->assertDontSee('Buka Kunci');
    }

    #[Test]
    public function locking_requires_the_blp_date_of_that_period(): void
    {
        $this->lock(3)->assertSessionHas('error');
        $this->assertFalse(StudentReport::where('term', 3)->whereNotNull('locked_at')->exists());

        $this->lock(1)->assertSessionHas('success');
        $this->printed(1)->assertSee('Sukoharjo, 10 Oktober 2026');
    }
}
