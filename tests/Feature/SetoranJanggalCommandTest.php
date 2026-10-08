<?php

namespace Tests\Feature;

use App\Models\HafalanRecord;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\PendingCommand;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;

/**
 * tad:setoran-janggal: setoran yang ayatnya diulang beberapa setoran sesudahnya (pola salah tanggal/ayat).
 */
class SetoranJanggalCommandTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();
        $this->surah->update(['total_ayah' => 286]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setor(string $date, int $start, int $end, string $enteredAt): void
    {
        Carbon::setTestNow($enteredAt);
        $header = HafalanRecord::create(['student_id' => $this->student->id, 'teacher_id' => $this->teacherProfile->id, 'submitted_at' => $date]);
        $header->surahs()->create(['surah_id' => $this->surah->id, 'ayah_start' => $start, 'ayah_end' => $end, 'status' => 'passed', 'submission_type' => 'new', 'baris' => $end - $start + 1]);
    }

    private function runCheck(): PendingCommand
    {
        Carbon::setTestNow('2026-10-08 10:00');

        return $this->artisan('tad:setoran-janggal', ['--tahun' => '2026/2027', '--term' => 1]);
    }

    #[Test]
    public function large_setoran_repeated_by_later_ones_is_flagged_with_its_likely_cause(): void
    {
        $this->setor('2026-07-27', 253, 256, '2026-07-27 10:00');
        $this->setor('2026-07-28', 253, 269, '2026-09-10 10:00'); // diinput belakangan & terlalu panjang
        $this->setor('2026-08-04', 253, 256, '2026-08-04 10:00');
        $this->setor('2026-08-12', 257, 259, '2026-08-12 10:00');
        $this->setor('2026-09-07', 260, 264, '2026-09-07 10:00');

        $this->runCheck()
            ->expectsOutputToContain('salah tanggal? diinput sesudah setoran pengulangnya; salah ayat? 17 ayat, biasanya 4')
            ->expectsOutputToContain('1 setoran dari 1 murid')
            ->assertSuccessful();
    }

    #[Test]
    public function steady_new_setoran_are_not_flagged(): void
    {
        $this->setor('2026-07-14', 1, 5, '2026-07-14 10:00');
        $this->setor('2026-07-21', 6, 10, '2026-07-21 10:00');
        $this->setor('2026-07-28', 6, 10, '2026-07-28 10:00'); // satu kali ulang: di bawah --min-ulang

        $this->runCheck()->expectsOutputToContain('Tidak ada setoran yang diulang 2x')->assertSuccessful();
    }
}
