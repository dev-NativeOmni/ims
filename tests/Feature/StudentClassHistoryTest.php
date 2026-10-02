<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\StudentClassHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Riwayat kelas santri (docs/riwayat-kelas.md): perpindahan kelas dicatat otomatis, dan laporan
 * per kelas untuk periode lalu memakai kelas santri pada periode itu, bukan kelas saat ini.
 *
 * Skenario: santri di "Kelas A Test" sejak awal tahun ajaran, pindah ke "Kelas B Test" 5 Oktober 2026.
 */
class StudentClassHistoryTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classA;

    private ClassRoom $classB;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-01 08:00');
        $this->setUpHafizPlusData();
        $this->classA = $this->student->classRoom;
        $this->classB = ClassRoom::create(['program_id' => $this->classA->program_id, 'name' => 'Kelas B Test', 'level' => 'Pemula']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function moveToClassB(): void
    {
        Carbon::setTestNow('2026-10-05 09:00');
        $this->student->update(['class_room_id' => $this->classB->id]);
        Carbon::setTestNow('2026-10-20 09:00');
    }

    private function histories(): array
    {
        return StudentClassHistory::where('student_id', $this->student->id)->orderBy('start_date')->get()
            ->map(fn ($h) => [$h->class_room_id, $h->start_date->toDateString(), $h->end_date?->toDateString()])
            ->all();
    }

    #[Test]
    public function new_student_starts_at_academic_year_start_and_moves_are_recorded(): void
    {
        $this->assertSame([[$this->classA->id, '2026-07-01', null]], $this->histories(), 'Santri baru: sejak awal tahun ajaran.');

        $this->moveToClassB();

        $this->assertSame([
            [$this->classA->id, '2026-07-01', '2026-10-04'],
            [$this->classB->id, '2026-10-05', null],
        ], $this->histories());
        $this->assertSame($this->classA->id, $this->student->fresh()->classRoomOn('2026-08-15')->id);
        $this->assertSame($this->classB->id, $this->student->fresh()->classRoomOn('2026-10-20')->id);
    }

    #[Test]
    public function same_day_change_is_a_correction_not_a_move(): void
    {
        $this->moveToClassB();
        Carbon::setTestNow('2026-10-21 09:00');
        $this->student->update(['class_room_id' => $this->classA->id]);
        $this->student->update(['class_room_id' => $this->classB->id]);  // salah pilih, langsung dikoreksi hari itu

        $this->assertSame([
            [$this->classA->id, '2026-07-01', '2026-10-04'],
            [$this->classB->id, '2026-10-05', '2026-10-20'],
            [$this->classB->id, '2026-10-21', null],
        ], $this->histories());
    }

    #[Test]
    public function quarterly_report_lists_the_student_in_the_class_of_that_term(): void
    {
        $this->moveToClassB();
        $names = fn (ClassRoom $class, int $term) => collect($this->actingAs($this->admin)
            ->get(route('reports.quarterly', ['class_room_id' => $class->id, 'academic_year' => '2026/2027', 'term' => $term]))
            ->assertOk()->viewData('halaqahData'))->flatMap(fn ($section) => $section['students'])->pluck('name')->all();

        $this->assertContains('Santri Test', $names($this->classA, 1), 'Triwulan 1: masih di kelas lama.');
        $this->assertNotContains('Santri Test', $names($this->classB, 1));
        $this->assertContains('Santri Test', $names($this->classB, 2), 'Triwulan berjalan: kelas baru.');
        $this->assertNotContains('Santri Test', $names($this->classA, 2));
    }

    #[Test]
    public function rapor_spreadsheet_target_and_chart_follow_the_class_of_the_period(): void
    {
        $this->moveToClassB();

        // Rapor Tengah Semester I (Jul-Sep) mencetak kelas lama; Semester I (Okt-Des) kelas baru.
        $this->actingAs($this->admin)->get(route('digital-reports.print', [$this->student, 'academic_year' => '2026/2027', 'term' => 1]))
            ->assertOk()->assertSee('Kelas A Test')->assertDontSee('Kelas B Test');
        $this->actingAs($this->admin)->get(route('digital-reports.print', [$this->student, 'academic_year' => '2026/2027', 'term' => 2]))
            ->assertSee('Kelas B Test');
        $this->actingAs($this->admin)->get(route('digital-reports.class-print', [$this->classA, 'academic_year' => '2026/2027', 'term' => 1]))
            ->assertOk()->assertSee('Santri Test');

        // Input Spreadsheet Agustus: santri di lembar kelas lama.
        $august = $this->actingAs($this->admin)->get(route('spreadsheet-input.index', ['class_room_id' => $this->classA->id, 'month' => '2026-08']));
        $this->assertContains('Santri Test', $august->assertOk()->viewData('students')->pluck('name')->all());
        $october = $this->actingAs($this->admin)->get(route('spreadsheet-input.index', ['class_room_id' => $this->classA->id, 'month' => '2026-10']));
        // Oktober: Kelas A tidak lagi punya murid ini (satu-satunya), jadi pilihan kelas pindah ke Kelas B.
        $this->assertSame($this->classB->id, (int) $october->viewData('selectedClassId'));
        $this->assertSame(['Kelas B Test'], $october->viewData('classRooms')->pluck('name')->all());

        // Target Triwulan 1 & Grafik Tahfizh term 1: kelas lama.
        $rows = $this->actingAs($this->admin)->get(route('hafalan-targets.term', ['period' => '2026-07-01', 'class_room_id' => $this->classA->id]))->assertOk()->viewData('rows');
        $this->assertContains('Santri Test', collect($rows)->map(fn ($row) => $row['student']->name)->all());

        $chart = $this->actingAs($this->admin)->get(route('reports.periodic', ['class_room_id' => $this->classA->id, 'period_type' => 'quarterly', 'quarter' => 1, 'year' => 2026]));
        $this->assertContains('Santri Test', collect($chart->assertOk()->viewData('studentReports'))->map(fn ($r) => $r['student']->name)->all());
    }
}
