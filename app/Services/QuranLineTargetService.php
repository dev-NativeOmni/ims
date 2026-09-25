<?php

namespace App\Services;

use App\Http\Controllers\ReportController;
use App\Models\Surah;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Menghitung posisi target hafalan (surah & ayat) dari titik awal + jumlah baris,
 * memakai hitungan baris mushaf otomatis yang sama dengan setoran
 * (ReportController::calculateLines) dan urutan hafalan sekolah (App\Support\HafalanOrder):
 * Juz 30 (fleksibel), lalu Juz 29, 28, ... masing-masing dari awal juz.
 */
class QuranLineTargetService
{
    /**
     * @param  Collection<int, Surah>  $surahsByNumber  keyed by surah number
     * @param  array<int, int>  $juz30Done  surah Juz 30 => ayat terakhir yang sudah disetor sebelum titik awal
     * @return array{surah: Surah, ayah_start: int, ayah_end: int}|null
     */
    public function targetPosition(int $startSurahNumber, int $startAyah, float $targetLines, Collection $surahsByNumber, array $juz30Done = []): ?array
    {
        if ($targetLines <= 0 || ! $surahsByNumber->has($startSurahNumber)) {
            return null;
        }

        $remaining = $targetLines;

        foreach (HafalanOrder::segments($startSurahNumber, $startAyah, $juz30Done, $surahsByNumber) as [$surahNumber, $from, $to]) {
            $surah = $surahsByNumber->get($surahNumber);
            if (! $surah || $from > $to) {
                continue;
            }

            $totalAyah = (int) $surah->total_ayah;
            $segmentLines = ReportController::calculateLines($surahNumber, $from, $to, $totalAyah);

            if ($segmentLines >= $remaining) {
                for ($ayah = $from; $ayah <= $to; $ayah++) {
                    if (ReportController::calculateLines($surahNumber, $from, $ayah, $totalAyah) >= $remaining) {
                        return ['surah' => $surah, 'ayah_start' => $from, 'ayah_end' => $ayah];
                    }
                }

                return ['surah' => $surah, 'ayah_start' => $from, 'ayah_end' => $to];
            }

            $remaining -= $segmentLines;
        }

        return null;
    }

    /**
     * Jumlah baris dari titik awal sampai (dan termasuk) posisi capaian, menurut urutan hafalan.
     * 0 bila capaian belum melewati titik awal.
     *
     * @param  array<int, int>  $juz30Done
     */
    public function linesUntil(int $startSurahNumber, int $startAyah, int $capaianSurah, int $capaianAyah, Collection $surahsByNumber, array $juz30Done = []): float
    {
        $lines = 0.0;
        $capaianJuz = HafalanOrder::juzOf($capaianSurah, $capaianAyah);

        foreach (HafalanOrder::segments($startSurahNumber, $startAyah, $juz30Done, $surahsByNumber) as [$surahNumber, $from, $to]) {
            // Sudah melewati juz capaian tanpa menemukannya: capaian tidak di depan titik awal.
            if (HafalanOrder::juzOf($surahNumber, $from) < $capaianJuz) {
                return 0.0;
            }

            $totalAyah = (int) ($surahsByNumber->get($surahNumber)?->total_ayah ?? 0);

            if ($surahNumber === $capaianSurah && $capaianAyah >= $from && $capaianAyah <= $to) {
                return $lines + ReportController::calculateLines($surahNumber, $from, $capaianAyah, $totalAyah);
            }

            $lines += ReportController::calculateLines($surahNumber, $from, $to, $totalAyah);
        }

        return 0.0;
    }

    /**
     * Apakah capaian sudah sampai atau melewati target menurut urutan hafalan
     * (Juz 30 -> 29 -> 28 ..., lihat HafalanOrder::rank()).
     */
    public function hasReached(int $capaianSurahNumber, int $capaianAyah, int $targetSurahNumber, int $targetAyah): bool
    {
        return HafalanOrder::rank($capaianSurahNumber, $capaianAyah) >= HafalanOrder::rank($targetSurahNumber, $targetAyah);
    }

    /**
     * Cari capaian terbaru dari kumpulan setoran (record dengan relasi `surah`).
     * Jika beberapa setoran punya submitted_at yang sama (mis. beberapa surat disetorkan
     * dalam satu sesi), yang dianggap capaian adalah yang posisinya paling jauh menurut
     * urutan hafalan (HafalanOrder::rank) -- bukan sekadar entri pertama yang
     * ter-load, karena urutan itu tidak menjamin urutan pengerjaan sebenarnya.
     *
     * @param  Collection<int, mixed>  $records
     */
    public function latestByPosition(Collection $records): mixed
    {
        if ($records->isEmpty()) {
            return null;
        }

        return $records->sort(function ($a, $b) {
            $dateA = $a->submitted_at ? Carbon::parse($a->submitted_at)->timestamp : 0;
            $dateB = $b->submitted_at ? Carbon::parse($b->submitted_at)->timestamp : 0;
            if ($dateA !== $dateB) {
                return $dateB <=> $dateA;
            }

            $rankA = HafalanOrder::rank((int) ($a->surah?->number ?? 114), (int) ($a->ayah_end ?? 0));
            $rankB = HafalanOrder::rank((int) ($b->surah?->number ?? 114), (int) ($b->ayah_end ?? 0));

            return $rankB <=> $rankA;
        })->first();
    }

    /**
     * Cari capaian terjauh dari kumpulan setoran, dengan aturan khusus Kelas 10 / Metode
     * Ummi: Ziyadah dimulai dari Juz 30 (Surah 114 An-Naas mundur ke 78 An-Naba'), jadi
     * capaian terjauh adalah nomor surah TERKECIL di rentang itu -- kebalikan dari urutan
     * mushaf normal yang dipakai kelas 11 & 12. Di luar Juz 30, tetap pakai urutan normal.
     *
     * @param  Collection<int, mixed>  $records
     */
    public function furthestRecord(Collection $records, bool $isGrade10Ummi = false): mixed
    {
        if ($records->isEmpty()) {
            return null;
        }

        if ($isGrade10Ummi) {
            $juz30Records = $records->filter(fn ($r) => ($r->surah?->number ?? 0) >= 78 && ($r->surah?->number ?? 0) <= 114);

            if ($juz30Records->isNotEmpty()) {
                return $juz30Records->sort(function ($a, $b) {
                    $numA = $a->surah?->number ?? 114;
                    $numB = $b->surah?->number ?? 114;
                    if ($numA !== $numB) {
                        return $numA <=> $numB;
                    }
                    $dateA = $a->submitted_at ? Carbon::parse($a->submitted_at)->timestamp : 0;
                    $dateB = $b->submitted_at ? Carbon::parse($b->submitted_at)->timestamp : 0;

                    return $dateB <=> $dateA;
                })->first();
            }
        }

        return $this->latestByPosition($records);
    }
}
