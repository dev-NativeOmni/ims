<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Program;
use App\Models\Surah;
use App\Services\AutoHafalanTargetService;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Target otomatis mengikuti urutan hafalan sekolah dan halaman Target Triwulan.
 */
class TargetTriwulanTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        // Surah Juz 28-30 dengan jumlah ayat dari batas juz.
        foreach ([28, 29, 30] as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                Surah::updateOrCreate(['number' => $range['surah']], [
                    'name_ar' => "S{$range['surah']}", 'name_latin' => "Surah {$range['surah']}", 'total_ayah' => $range['end'],
                ]);
            }
        }

        $program = Program::create(['name' => 'Program Reguler', 'status' => 'active']);
        $this->classRoom = ClassRoom::create(['program_id' => $program->id, 'name' => 'XII F3', 'level' => 'XII', 'tahfizh_days' => [3]]);
        $this->student->update(['class_room_id' => $this->classRoom->id, 'tahfizh_level' => 'reguler']);
    }

    private function setoran(string $date, int $surahNumber, int $from, int $to): void
    {
        $record = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $record->surahs()->create([
            'surah_id' => Surah::where('number', $surahNumber)->value('id'),
            'ayah_start' => $from, 'ayah_end' => $to, 'submission_type' => 'new', 'status' => 'passed',
        ]);
    }

    /** Semua surah Juz 30 selain An-Naba sudah disetor sebelum triwulan. */
    private function finishJuz30ExceptAnNaba(): void
    {
        foreach (range(79, 114) as $number) {
            $this->setoran('2026-06-10', $number, 1, (int) Surah::where('number', $number)->value('total_ayah'));
        }
    }

    #[Test]
    public function monthly_targets_move_from_juz_30_into_the_start_of_juz_29_not_al_fatihah(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-01', 78, 1, 5); // setoran pertama triwulan

        app(AutoHafalanTargetService::class)->syncStudent($this->student->fresh(), Carbon::parse('2026-07-01'));

        $targets = HafalanTarget::where('student_id', $this->student->id)->whereNotNull('auto_month')->orderBy('auto_month')->with('surah')->get();
        $this->assertCount(3, $targets);
        $last = $targets->last();
        $this->assertTrue(
            $last->surah->number >= 67 && $last->surah->number <= 78,
            "Target akhir triwulan harus di An-Naba/Juz 29, bukan surah {$last->surah->number}."
        );
    }

    #[Test]
    public function term_plan_matches_the_last_monthly_checkpoint_and_tracks_progress(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-01', 78, 1, 5);
        $this->setoran('2026-07-08', 78, 6, 40);
        $this->setoran('2026-07-15', 67, 1, 10); // lanjut ke awal Juz 29

        $plan = app(AutoHafalanTargetService::class)->termPlan($this->student->fresh(), Carbon::parse('2026-08-15'));

        $this->assertSame(78, $plan['start']['surah']->number);
        $this->assertSame(14, $plan['term_meetings'], 'Rabu Jul-Sep 2026: 5 + 4 + 5 pertemuan.');
        $this->assertSame(70, $plan['target_lines'], '14 pertemuan x 5 baris.');
        $this->assertSame(end($plan['months'])['position']['surah']->number, $plan['target']['surah']->number);
        $this->assertSame(67, $plan['capaian']['surah']->number, 'Capaian terjauh = Al-Mulk, bukan An-Naba.');
        $this->assertGreaterThan(0, $plan['progress']);
    }

    #[Test]
    public function term_page_lists_students_of_grade_11_12_only(): void
    {
        $this->finishJuz30ExceptAnNaba();
        $this->setoran('2026-07-01', 78, 1, 5);

        $response = $this->actingAs($this->admin)->get(route('hafalan-targets.term', ['period' => '2026-07-01', 'class_room_id' => $this->classRoom->id]));

        $response->assertOk();
        $response->assertSee('Target Triwulan');
        $response->assertSee($this->student->name);
        $this->assertSame(1, $response->viewData('summary')['students']);
        $this->assertFalse($response->viewData('classRooms')->contains(fn ($c) => $c->isGradeTen()));
    }

    #[Test]
    public function staff_can_switch_a_students_direction_from_the_term_page(): void
    {
        $this->student->update(['teacher_id' => $this->teacherProfile->id]);

        $this->actingAs($this->teacherUser)
            ->patch(route('hafalan-targets.direction', $this->student), ['hafalan_direction' => 'forward', 'period' => '2026-07-01'])
            ->assertRedirect();

        $this->assertSame('forward', $this->student->fresh()->hafalan_direction);
    }

    #[Test]
    public function staff_cannot_switch_direction_for_students_they_cannot_see(): void
    {
        $this->student->update(['teacher_id' => null]);

        $this->actingAs($this->teacherUser)
            ->patch(route('hafalan-targets.direction', $this->student), ['hafalan_direction' => 'forward'])
            ->assertForbidden();
        $this->assertSame('backward', $this->student->fresh()->hafalan_direction);
    }
}
