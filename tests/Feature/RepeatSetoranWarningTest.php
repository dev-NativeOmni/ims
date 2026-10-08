<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use App\Models\StudentPriorHafalan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Peringatan setoran ulangan di form Input/Edit Setoran & Spreadsheet: data riwayat ayat lulus
 * (HafalanProgressService::passedHistory) untuk resources/js/repeat-check.js (tes logika: tests/js).
 */
class RepeatSetoranWarningTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
    }

    private function setor(string $date, int $start, int $end, string $status = 'passed'): int
    {
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);

        return $header->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => $start, 'ayah_end' => $end, 'status' => $status, 'submission_type' => 'new'])->id;
    }

    #[Test]
    public function history_endpoint_lists_prior_hafalan_and_passed_setoran_only(): void
    {
        StudentPriorHafalan::create(['student_id' => $this->student->id, 'surah_id' => $this->surah->id, 'ayah_start' => 1, 'ayah_end' => 2]);
        $passed = $this->setor('2026-07-10', 3, 5);
        $this->setor('2026-07-11', 6, 7, 'repeat');

        $this->actingAs($this->teacherUser)->getJson(route('hafalan-records.repeat-history', $this->student))
            ->assertOk()
            ->assertExactJson([
                [$this->surah->id, 1, 2, null, null],
                [$this->surah->id, 3, 5, '2026-07-10', $passed],
            ]);
    }

    #[Test]
    public function spreadsheet_gets_saved_history_only_outside_the_shown_columns(): void
    {
        $july = $this->setor('2026-07-10', 1, 3);
        $this->setor('2026-08-03', 4, 5); // tampil sebagai kolom: diambil dari isian layar

        $history = $this->actingAs($this->teacherUser)
            ->get(route('spreadsheet-input.index', ['class_room_id' => $this->student->class_room_id, 'month' => '2026-08']))
            ->assertOk()->assertSee('repeatWarning(student.id', false)
            ->viewData('repeatHistory');

        $this->assertSame([[$this->surah->id, 1, 3, '2026-07-10', $july]], $history[$this->student->id]);
    }

    #[Test]
    public function input_and_edit_forms_show_the_warning(): void
    {
        $this->actingAs($this->teacherUser)->get(route('hafalan-records.create'))->assertOk()->assertSee('repeatWarningFor(item)', false);

        $this->setor('2026-07-10', 1, 3);
        $record = HafalanRecord::query()->latest('id')->first();
        $this->actingAs($this->teacherUser)->get(route('hafalan-records.edit', $record))->assertOk()->assertSee('repeatWarningFor(item)', false);
    }
}
