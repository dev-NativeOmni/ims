<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Surah;
use App\Services\AcademicCalendarService;
use App\Services\AutoHafalanTargetService;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

class FillTermTargetsTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        foreach ([28, 29, 30] as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                Surah::updateOrCreate(['number' => $range['surah']], [
                    'name_ar' => "S{$range['surah']}", 'name_latin' => "Surah {$range['surah']}", 'total_ayah' => $range['end'],
                ]);
            }
        }

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XI F3', 'level' => 'XI', 'tahfizh_days' => [2]]);
        $this->student->update(['class_room_id' => $this->classRoom->id, 'tahfizh_level' => 'reguler', 'teacher_id' => $this->teacherProfile->id]);

        // Setoran pertama triwulan: An-Naba 1-5 (8 Juli 2026).
        HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => '2026-07-08'])
            ->surahs()->create(['surah_id' => Surah::where('number', 78)->value('id'), 'ayah_start' => 1, 'ayah_end' => 5, 'submission_type' => 'new', 'status' => 'passed']);
    }

    private function fillTargets(array $options = []): void
    {
        $this->artisan('tad:isi-target-triwulan', ['--guru' => 'Guru Test', '--kelas' => ['XI F3'], '--periode' => '2026-09-15', '--force' => true] + $options)
            ->assertSuccessful();
    }

    #[Test]
    public function fills_monthly_targets_from_meetings_times_level_lines_starting_at_the_first_setoran(): void
    {
        $this->fillTargets(['--dry-run' => true]);
        $this->assertSame(0, HafalanTarget::count());

        $this->fillTargets();

        $service = app(AutoHafalanTargetService::class);
        $months = $service->termMonths($this->classRoom, Carbon::parse('2026-09-15'));
        $calendar = app(AcademicCalendarService::class);
        $targets = HafalanTarget::with('surah')->where('student_id', $this->student->id)->orderBy('target_date')->get();
        $this->assertCount(3, $targets);

        $previous = 0;
        foreach (array_values($months) as $i => $month) {
            $target = $targets[$i];
            // Deadline & penyimpanan sama dengan menu Target Triwulan (target guru, bukan otomatis).
            $this->assertSame($month['deadline']->toDateString(), $target->target_date->toDateString());
            $this->assertNull($target->auto_month);
            $this->assertSame($this->teacherProfile->id, (int) $target->teacher_id);

            // Target baris kumulatif = pertemuan aktif sejak awal triwulan x 5 baris (Reguler).
            $lines = 5 * $calendar->scheduledMeetings($this->classRoom, reset($months)['start'], $month['end']);
            $this->assertGreaterThan($previous, $lines);
            $previous = $lines;
        }

        // Surah & ayat = hasil kalkulator baris dari setoran pertama triwulan (An-Naba 1).
        $suggested = $service->suggestedTermTargets($this->student->fresh(), $months);
        $this->assertSame(['surah' => 78, 'ayah' => 1], array_intersect_key($suggested['start'], ['surah' => 0, 'ayah' => 0]));
        foreach (array_keys($months) as $i => $monthKey) {
            $this->assertSame(
                [$suggested['months'][$monthKey]['surah']->id, $suggested['months'][$monthKey]['ayah']],
                [(int) $targets[$i]->surah_id, (int) $targets[$i]->ayah]
            );
        }
    }

    #[Test]
    public function existing_teacher_targets_are_kept_unless_timpa(): void
    {
        $september = app(AutoHafalanTargetService::class)->termMonths($this->classRoom, Carbon::parse('2026-09-15'))['2026-09'];
        $manual = HafalanTarget::create([
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'status' => 'active',
            'surah_id' => Surah::where('number', 78)->value('id'), 'ayah' => 40, 'target_date' => $september['deadline']->toDateString(),
        ]);

        $this->fillTargets();
        $this->assertSame(40, (int) $manual->fresh()->ayah, 'Target guru tidak ditimpa tanpa --timpa.');
        $this->assertSame(3, HafalanTarget::where('student_id', $this->student->id)->count());

        $this->fillTargets(['--timpa' => true]);
        $this->assertNotSame(
            [78, 40],
            [(int) $manual->fresh()->surah->number, (int) $manual->fresh()->ayah],
            'Dengan --timpa diganti hasil hitungan.'
        );
    }
}
