<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AcademicCalendarService;
use App\Services\HafalanProgressService;
use App\Support\AcademicYear;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Halaman Tabungan & Hutang Baris: per bulan & triwulan, filter kelas & halaqoh, guru hanya muridnya.
 */
class LineSavingsPageTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $class;

    private Surah $baqarah;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-12-20 09:00');
        $this->setUpHafizPlusData();
        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->class = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI F2', 'level' => 'XI']);
        $this->student->update(['class_room_id' => $this->class->id, 'tahfizh_level' => 'reguler']);
        $this->baqarah = Surah::firstOrCreate(['number' => 2], ['name_ar' => 'البقرة', 'name_latin' => 'Al-Baqarah', 'total_ayah' => 286, 'juz_start' => 1, 'juz_end' => 3]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setor(Student $student, string $date, int $start, int $end, float $baris): void
    {
        $header = HafalanRecord::create(['student_id' => $student->id, 'teacher_id' => $student->teacher_id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => $this->baqarah->id, 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new', 'baris' => $baris]);
    }

    private function termTarget(int $term): float
    {
        [$start, $end] = AcademicYear::termRange('2026/2027', $term);

        return (float) app(HafalanProgressService::class)->termBreakdown($this->student->fresh('classRoom.program'), collect(), app(AcademicCalendarService::class)->termMonths($start), $end->copy()->endOfDay(), null, false)['evaluation']['target_lines'];
    }

    private function otherHalaqohStudent(): Student
    {
        $teacherUser = User::factory()->create(['role_id' => $this->teacherUser->role_id, 'name' => 'Ust Lain', 'status' => 'active']);
        $teacher = TeacherProfile::create(['user_id' => $teacherUser->id, 'employee_number' => 'TEST-GURU-002']);

        return Student::create(['class_room_id' => $this->class->id, 'teacher_id' => $teacher->id, 'name' => 'Murid Halaqoh Lain', 'student_number' => 'TEST-SNT-777', 'gender' => 'male', 'birth_date' => '2010-01-01', 'status' => 'active', 'tahfizh_level' => 'reguler']);
    }

    #[Test]
    public function it_shows_monthly_differences_and_term_savings_used_and_debt(): void
    {
        $this->setor($this->student, '2026-07-14', 1, 50, $this->termTarget(1) + 10);   // T1: lebih 10
        $this->setor($this->student, '2026-10-14', 51, 60, $this->termTarget(2) - 5);   // T2: kurang 5 -> pakai tabungan
        $other = $this->otherHalaqohStudent();
        $this->setor($other, '2026-10-14', 1, 3, 3);                                     // T2: kurang banyak -> hutang

        $response = $this->actingAs($this->admin)->get(route('line-savings.index', ['academic_year' => '2026/2027', 'term' => 2, 'class_room_id' => $this->class->id]))
            ->assertOk()->assertSee('Tabungan &amp; Hutang Baris', false);
        $rows = collect($response->viewData('rows'))->keyBy('name');

        $mine = $rows[$this->student->name];
        $this->assertSame([10.0, 5.0, 0.0, 0.0, 5.0, true], [$mine['balance_before'], $mine['used'], $mine['deposit'], $mine['debt'], $mine['balance_after'], $mine['reached']]);
        $this->assertCount(3, $mine['months']);
        $this->assertGreaterThan(0, $rows[$other->name]['debt']);
        $this->assertSame(1, $response->viewData('summary')['helped']);

        // Filter halaqoh: hanya murid musyrif itu.
        $filtered = $this->actingAs($this->admin)->get(route('line-savings.index', ['academic_year' => '2026/2027', 'term' => 2, 'class_room_id' => $this->class->id, 'teacher_id' => $other->teacher_id]))
            ->assertOk()->viewData('rows');
        $this->assertSame(['Murid Halaqoh Lain'], array_column($filtered, 'name'));
    }

    #[Test]
    public function teacher_sees_only_own_halaqoh_and_parents_cannot_open_it(): void
    {
        $other = $this->otherHalaqohStudent();

        $rows = $this->actingAs($this->teacherUser)->get(route('line-savings.index', ['academic_year' => '2026/2027', 'term' => 2]))
            ->assertOk()->viewData('rows');
        $this->assertSame([$this->student->name], array_column($rows, 'name'));
        $this->assertNotContains($other->name, array_column($rows, 'name'));

        $this->actingAs($this->parentUser)->get(route('line-savings.index'))->assertForbidden();
    }
}
