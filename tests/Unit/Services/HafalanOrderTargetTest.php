<?php

namespace Tests\Unit\Services;

use App\Http\Controllers\ReportController;
use App\Models\Surah;
use App\Services\QuranLineTargetService;
use App\Support\HafalanOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Urutan hafalan sekolah: Juz 30 (fleksibel) -> Juz 29 -> Juz 28 ..., tiap juz selain
 * 30 dimulai dari awal juz. Target & capaian mengikuti urutan ini.
 */
class HafalanOrderTargetTest extends TestCase
{
    use RefreshDatabase;

    private QuranLineTargetService $service;

    private Collection $surahs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuranLineTargetService;

        // 114 surah; jumlah ayat = ayat terakhir di batas juz.
        $totals = [];
        foreach (HafalanOrder::JUZ_RANGES as $ranges) {
            foreach ($ranges as $range) {
                $totals[$range['surah']] = max($totals[$range['surah']] ?? 0, $range['end']);
            }
        }
        foreach ($totals as $number => $total) {
            Surah::create(['number' => $number, 'name_ar' => "S{$number}", 'name_latin' => "Surah {$number}", 'total_ayah' => $total]);
        }
        $this->surahs = Surah::query()->get()->keyBy('number');
    }

    private function lines(int $surah, int $from, int $to): float
    {
        return ReportController::calculateLines($surah, $from, $to, (int) $this->surahs[$surah]->total_ayah);
    }

    private function juzLines(int $juz): float
    {
        return collect(HafalanOrder::JUZ_RANGES[$juz])->sum(fn ($r) => $this->lines($r['surah'], $r['start'], $r['end']));
    }

    /** Semua surah Juz 30 kecuali yang disebut sudah selesai disetor. */
    private function juz30AllDoneExcept(array $pending): array
    {
        return collect(range(78, 114))->reject(fn ($s) => in_array($s, $pending, true))
            ->mapWithKeys(fn ($s) => [$s => (int) $this->surahs[$s]->total_ayah])->all();
    }

    #[Test]
    public function after_finishing_juz_30_the_target_continues_from_the_start_of_juz_29(): void
    {
        // Tinggal An-Naba; sisanya sudah selesai. 5 baris setelah An-Naba -> Al-Mulk dari ayat 1.
        $position = $this->service->targetPosition(78, 1, $this->lines(78, 1, 40) + 5, $this->surahs, $this->juz30AllDoneExcept([78]));

        $this->assertSame(67, $position['surah']->number);
        $this->assertSame(1, $position['ayah_start']);
    }

    #[Test]
    public function after_finishing_juz_29_the_target_continues_from_the_start_of_juz_28(): void
    {
        $position = $this->service->targetPosition(67, 1, $this->juzLines(29) + 3, $this->surahs);

        $this->assertSame(58, $position['surah']->number, 'Juz 28 dimulai dari Al-Mujadilah.');
        $this->assertSame(1, $position['ayah_start']);
    }

    #[Test]
    public function a_juz_that_starts_mid_surah_begins_at_that_ayah(): void
    {
        // Juz 27 dimulai dari Adz-Dzariyat ayat 31.
        $position = $this->service->targetPosition(58, 1, $this->juzLines(28) + 2, $this->surahs);

        $this->assertSame(51, $position['surah']->number);
        $this->assertSame(31, $position['ayah_start']);
    }

    #[Test]
    public function juz_30_counts_only_surahs_not_yet_memorised_before_moving_on(): void
    {
        // Mulai di Al-Qari'ah (101); yang belum: 101, 99, 90. Setelah itu langsung Juz 29.
        $done = $this->juz30AllDoneExcept([101, 99, 90]);
        $afterHundredOne = $this->lines(101, 1, 11);

        $next = $this->service->targetPosition(101, 1, $afterHundredOne + 1, $this->surahs, $done);
        $this->assertSame(99, $next['surah']->number, 'Surah berikutnya = surah Juz 30 yang belum disetor.');

        $all = $afterHundredOne + $this->lines(99, 1, 8) + $this->lines(90, 1, 20);
        $this->assertSame(67, $this->service->targetPosition(101, 1, $all + 1, $this->surahs, $done)['surah']->number);
    }

    #[Test]
    public function reaching_is_judged_by_memorisation_order_not_surah_number(): void
    {
        $this->assertTrue($this->service->hasReached(67, 1, 78, 40), 'Al-Mulk (Juz 29) sudah melewati An-Naba.');
        $this->assertFalse($this->service->hasReached(78, 40, 67, 1));
        $this->assertTrue($this->service->hasReached(58, 1, 77, 50), 'Juz 28 melewati Juz 29.');
        $this->assertTrue($this->service->hasReached(67, 10, 67, 5));
    }

    #[Test]
    public function lines_until_measures_progress_along_the_same_order(): void
    {
        $this->assertSame($this->lines(67, 1, 10), $this->service->linesUntil(67, 1, 67, 10, $this->surahs));
        $this->assertSame($this->juzLines(29) + $this->lines(58, 1, 3), $this->service->linesUntil(67, 1, 58, 3, $this->surahs));
        $this->assertSame(0.0, $this->service->linesUntil(58, 1, 67, 5, $this->surahs), 'Capaian di belakang titik awal = 0.');
    }

    #[Test]
    public function latest_record_on_the_same_day_is_the_furthest_in_memorisation_order(): void
    {
        $record = fn (int $surah, int $ayah) => (object) ['submitted_at' => '2026-10-01', 'surah' => $this->surahs[$surah], 'ayah_end' => $ayah];

        $latest = $this->service->latestByPosition(collect([$record(78, 40), $record(67, 5)]));

        $this->assertSame(67, $latest->surah->number);
    }
}
