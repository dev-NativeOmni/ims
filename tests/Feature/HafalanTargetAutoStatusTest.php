<?php

namespace Tests\Feature;

use App\Models\HafalanTarget;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * Status target otomatis: Selesai langsung saat setoran disimpan (tercapai/terlampaui),
 * Terlewat bila deadline lewat dan belum tercapai.
 */
class HafalanTargetAutoStatusTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        Carbon::setTestNow('2026-09-10 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function target(string $date, int $ayah = 5, array $extra = []): HafalanTarget
    {
        return HafalanTarget::create($extra + [
            'student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id,
            'surah_id' => $this->surah->id, 'ayah' => $ayah, 'target_date' => $date, 'status' => 'active',
        ]);
    }

    private function setorViaSpreadsheet(int $from, int $to): void
    {
        $this->actingAs($this->teacherUser)->post(route('spreadsheet-input.save'), [
            'class_room_id' => $this->student->class_room_id, 'month' => '2026-09', 'type' => 'hafalan',
            'records' => [$this->student->id => ['dates' => ['2026-09-09' => [
                'attendance' => 'hadir',
                'hafalans' => [['id' => null, 'surah_id' => $this->surah->id, 'ayah_start' => $from, 'ayah_end' => $to, 'score' => '95', 'status' => 'passed', 'submission_type' => 'new']],
            ]]]],
        ])->assertSessionHas('success');
    }

    #[Test]
    public function target_is_completed_as_soon_as_the_setoran_reaching_it_is_saved(): void
    {
        $reached = $this->target('2026-09-30', 5);
        $exceeded = $this->target('2026-09-30', 3);
        $notYet = $this->target('2026-09-30', 7);

        $this->setorViaSpreadsheet(1, 5);

        $this->assertSame('completed', $reached->fresh()->status, 'Sama dengan capaian.');
        $this->assertNotNull($reached->fresh()->completed_at);
        $this->assertSame('completed', $exceeded->fresh()->status, 'Capaian melampaui target.');
        $this->assertSame('active', $notYet->fresh()->status, 'Deadline belum lewat.');
    }

    #[Test]
    public function nightly_job_marks_past_deadline_targets_as_missed(): void
    {
        $past = $this->target('2026-09-05', 7);
        $today = $this->target('2026-09-10', 7);
        $ummi = $this->target('2026-09-05', 7, ['ummi_jilid' => 'Jilid 2', 'halaman_buku' => '10']);

        $this->artisan('tad:sync-completed-targets')->assertSuccessful();

        $this->assertSame('missed', $past->fresh()->status);
        $this->assertSame('active', $today->fresh()->status, 'Deadline hari ini belum terlewat.');
        $this->assertSame(['missed', 'missed'], [$ummi->fresh()->status, $ummi->fresh()->book_status], 'Target Ummi ikut diotomasi (dinilai per bagian).');
    }

    #[Test]
    public function reached_targets_are_completed_even_after_the_deadline(): void
    {
        $past = $this->target('2026-09-05', 5);
        $this->setorViaSpreadsheet(1, 7);

        $this->assertSame('completed', $past->fresh()->status);
    }
}
