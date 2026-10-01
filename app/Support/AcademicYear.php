<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;

/**
 * Tahun ajaran ("2026/2027", Juli s.d. Juni). Aplikasi dipakai mulai FIRST; tahun ajaran
 * sebelumnya tidak ditawarkan dan diganti tahun ajaran aktif bila diminta.
 */
class AcademicYear
{
    public const FIRST = '2026/2027';

    /**
     * Tahun ajaran aktif dari Pengaturan Rapor (tidak valid/sebelum FIRST = FIRST).
     */
    public static function active(): string
    {
        $saved = (string) Setting::get('academic_year', self::FIRST);

        return self::isValid($saved) ? $saved : self::FIRST;
    }

    /**
     * Format "YYYY/YYYY+1" dan tidak sebelum FIRST.
     */
    public static function isValid(?string $year): bool
    {
        if (! preg_match('/^(\d{4})\/(\d{4})$/', (string) $year, $match)) {
            return false;
        }

        return (int) $match[2] === (int) $match[1] + 1 && (int) $match[1] >= self::startYear(self::FIRST);
    }

    public static function startYear(string $year): int
    {
        return (int) substr($year, 0, 4);
    }

    /**
     * Tahun ajaran yang memuat tanggal itu (paling awal FIRST).
     */
    public static function forDate(Carbon $date): string
    {
        $start = max($date->month >= 7 ? $date->year : $date->year - 1, self::startYear(self::FIRST));

        return $start.'/'.($start + 1);
    }

    /**
     * Pilihan tahun ajaran, terbaru dulu: dari FIRST s.d. yang paling akhir di antara tahun aktif,
     * tahun berjalan, dan yang sedang dibuka.
     *
     * @return array<int, string>
     */
    public static function options(?string $current = null): array
    {
        $last = max(
            self::startYear(self::active()),
            self::startYear(self::forDate(now())),
            self::isValid($current) ? self::startYear($current) : 0,
        );

        return collect(range($last, self::startYear(self::FIRST)))
            ->map(fn ($start) => $start.'/'.($start + 1))
            ->all();
    }
}
