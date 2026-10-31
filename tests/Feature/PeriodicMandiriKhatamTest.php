<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\StudentPriorHafalan;
use App\Models\Surah;
use App\Services\HafalanProgressService;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Database\Seeders\QuranDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Grafik Tahfizh: kartu Ziyadah Mandiri (Kelas 10 E1) & tanda khatam 30 juz.
 */
class PeriodicMandiriKhatamTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-15 09:00');
        $this->seed(QuranDataSeeder::class);
        $this->setUpHafizPlusData();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function inClass(string $name): ClassRoom
    {
        $class = ClassRoom::create(['program_id' => Program::create(['name' => 'Program Tahfizh', 'status' => 'active'])->id, 'name' => $name, 'level' => 'X']);
        $this->student->update(['class_room_id' => $class->id, 'tahfizh_level' => 'ummi']);

        return $class;
    }

    private function setor(string $date, int $surah, int $start, int $end, float $baris): void
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => Surah::where('number', $surah)->value('id'), 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new', 'baris' => $baris]);
    }

    private function periodic(ClassRoom $class)
    {
        return $this->actingAs($this->admin)->get(route('reports.periodic', ['class_room_id' => $class->id, 'period_type' => 'quarterly', 'quarter' => 1, 'year' => 2026]))->assertOk();
    }

    #[Test]
    public function e1_gets_a_separate_ziyadah_mandiri_card_with_new_ayat_lines(): void
    {
        $class = $this->inClass('X E1');
        $this->setor('2026-07-14', 67, 1, 10, 12);
        $this->setor('2026-08-04', 67, 6, 15, 12); // ayat 6-10 ulangan: hanya 11-15 dihitung

        $response = $this->periodic($class)->assertSee('ZIYADAH MANDIRI')->assertSee('Laporan Ziyadah Mandiri');
        $row = collect($response->viewData('studentReports'))->first();
        $this->assertSame(2, $row['mandiri_setoran']);
        $this->assertGreaterThan(12, $row['mandiri_baris']);
        $this->assertLessThan(24, $row['mandiri_baris']);
    }

    #[Test]
    public function e2_keeps_the_single_ummi_card(): void
    {
        $this->periodic($this->inClass('X E2'))->assertDontSee('ZIYADAH MANDIRI');
    }

    #[Test]
    public function students_who_completed_30_juz_are_marked(): void
    {
        $class = $this->inClass('X E1');
        $ids = Surah::query()->pluck('id', 'number');
        foreach (range(1, 30) as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                StudentPriorHafalan::create(['student_id' => $this->student->id, 'surah_id' => $ids[$range['surah']], 'ayah_start' => $range['start'], 'ayah_end' => $range['end']]);
            }
        }

        $response = $this->periodic($class)->assertSee('Khatam 30 Juz');
        $this->assertTrue(collect($response->viewData('studentReports'))->first()['khatam']);
        $this->assertTrue($response->viewData('ummiChart')['rows'][0]['khatam']);
    }

    #[Test]
    public function setoran_of_students_who_completed_30_juz_still_count_their_lines(): void
    {
        $this->inClass('X E1');
        $ids = Surah::query()->pluck('id', 'number');
        foreach (range(1, 30) as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                StudentPriorHafalan::create(['student_id' => $this->student->id, 'surah_id' => $ids[$range['surah']], 'ayah_start' => $range['start'], 'ayah_end' => $range['end']]);
            }
        }
        $this->setor('2026-07-14', 67, 1, 10, 12);
        $this->setor('2026-08-04', 67, 6, 15, 12);

        $progress = app(HafalanProgressService::class);
        $details = collect($progress->passedLineDetails($progress->records($this->student), Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30')));

        $this->assertSame(['khatam', 'khatam'], $details->pluck('kind')->all());
        $this->assertEquals(24.0, $details->sum('new_lines'), 'Ayat sudah hafal semua, baris tetap dihitung penuh.');
        $this->assertSame([], $progress->passedHistory([$this->student->id])[$this->student->id], 'Tanpa peringatan "setoran ulangan".');
    }

    #[Test]
    public function repeated_ayat_of_students_not_yet_khatam_still_count_zero(): void
    {
        $this->inClass('X E1');
        $this->setor('2026-07-14', 67, 1, 10, 12);
        $this->setor('2026-08-04', 67, 1, 10, 12);

        $progress = app(HafalanProgressService::class);
        $details = collect($progress->passedLineDetails($progress->records($this->student), Carbon::parse('2026-07-01'), Carbon::parse('2026-09-30')));

        $this->assertSame(['new', 'repeat'], $details->pluck('kind')->all());
    }
}
