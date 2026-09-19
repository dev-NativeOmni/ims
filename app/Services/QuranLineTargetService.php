<?php

namespace App\Services;

use App\Http\Controllers\ReportController;
use App\Models\Surah;
use Illuminate\Support\Collection;

/**
 * Menghitung posisi target hafalan (surah & ayat) dari titik awal + jumlah baris,
 * memakai hitungan baris mushaf otomatis yang sama dengan setoran
 * (ReportController::calculateLines) dan urutan mushaf berjalan maju
 * (ayat berikutnya, lalu surah berikutnya).
 */
class QuranLineTargetService
{
    /**
     * @param  Collection<int, Surah>  $surahsByNumber  keyed by surah number
     * @return array{surah: Surah, ayah_start: int, ayah_end: int}|null
     */
    public function targetPosition(int $startSurahNumber, int $startAyah, float $targetLines, Collection $surahsByNumber): ?array
    {
        if ($targetLines <= 0) {
            return null;
        }

        $remaining = $targetLines;
        $surahNumber = $startSurahNumber;
        $ayahStart = max(1, $startAyah);

        for ($i = 0; $i < 114; $i++) {
            $surah = $surahsByNumber->get($surahNumber);
            if (! $surah) {
                return null;
            }

            $totalAyah = (int) $surah->total_ayah;

            if ($ayahStart <= $totalAyah) {
                $linesToEnd = ReportController::calculateLines($surahNumber, $ayahStart, $totalAyah, $totalAyah);

                if ($linesToEnd >= $remaining) {
                    for ($ayah = $ayahStart; $ayah <= $totalAyah; $ayah++) {
                        if (ReportController::calculateLines($surahNumber, $ayahStart, $ayah, $totalAyah) >= $remaining) {
                            return ['surah' => $surah, 'ayah_start' => $ayahStart, 'ayah_end' => $ayah];
                        }
                    }

                    return ['surah' => $surah, 'ayah_start' => $ayahStart, 'ayah_end' => $totalAyah];
                }

                $remaining -= $linesToEnd;
            }

            $surahNumber = $surahNumber < 114 ? $surahNumber + 1 : 1;
            $ayahStart = 1;
        }

        return null;
    }

    /**
     * Apakah capaian sudah sampai atau melewati target (urutan mushaf berjalan maju:
     * nomor surah lebih besar = lebih jauh, lalu ayat).
     */
    public function hasReached(int $capaianSurahNumber, int $capaianAyah, int $targetSurahNumber, int $targetAyah): bool
    {
        if ($capaianSurahNumber !== $targetSurahNumber) {
            return $capaianSurahNumber > $targetSurahNumber;
        }

        return $capaianAyah >= $targetAyah;
    }
}
