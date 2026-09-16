<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Setting;
use Carbon\Carbon;

/**
 * Menghitung nomor Tatap Muka (TM) berdasarkan kalender hari efektif
 * sebuah kelas: mengikuti jadwal hari kelas (tahfizh_days), frekuensi
 * pertemuan program (harian vs seminggu sekali), serta melewati tanggal
 * libur nasional dan libur khusus per-kelas.
 */
class AcademicCalendarService
{
    /**
     * Hari efektif UMMI: Senin-Kamis saja. Jumat tetap hari aktif kelas
     * (tahfizh_days) untuk tahfizh mandiri, tapi bukan bagian UMMI --
     * jadi tidak menambah hitungan TM UMMI walau kelasnya aktif hari itu.
     */
    private const UMMI_DAYS = [1, 2, 3, 4];

    /**
     * Tanggal mulai term (semester) yang memuat tanggal ini.
     * Term 1: Juli-September, Term 2: Oktober-Desember,
     * Term 3: Januari-Maret, Term 4: April-Juni.
     */
    public function termStartDate(Carbon $date): Carbon
    {
        $month = (int) $date->format('n');
        $year = (int) $date->format('Y');

        $termStartMonth = match (true) {
            $month >= 7 && $month <= 9 => 7,
            $month >= 10 && $month <= 12 => 10,
            $month >= 1 && $month <= 3 => 1,
            default => 4,
        };

        return Carbon::createFromDate($year, $termStartMonth, 1)->startOfDay();
    }

    /**
     * Apakah tanggal ini hari efektif untuk kelas: sesuai hari kelas
     * (tahfizh_days), bukan libur nasional, dan bukan libur khusus kelas
     * ini ("libur sebagian"). Untuk UMMI ($forUmmi), hari kelas dipersempit
     * ke Senin-Kamis saja, terlepas dari tahfizh_days kelasnya.
     */
    public function isEffectiveDay(ClassRoom $classRoom, Carbon $date, bool $forUmmi = false): bool
    {
        $dayOfWeek = (int) $date->format('N');
        $allowedDays = $forUmmi
            ? array_intersect($classRoom->tahfizh_days, self::UMMI_DAYS)
            : $classRoom->tahfizh_days;

        if (! in_array($dayOfWeek, $allowedDays, true)) {
            return false;
        }

        $year = (int) $date->format('Y');
        $dateString = $date->toDateString();

        if (in_array($dateString, Setting::getNationalHolidays($year), true)) {
            return false;
        }

        $classHolidaysRaw = Setting::get("class_holidays_{$year}");
        $classHolidays = $classHolidaysRaw ? json_decode($classHolidaysRaw, true) : [];

        if (is_array($classHolidays)
            && isset($classHolidays[$dateString])
            && in_array($classRoom->id, $classHolidays[$dateString], true)) {
            return false;
        }

        return true;
    }

    /**
     * Nomor Tatap Muka ke berapa suatu tanggal, dihitung dari hari efektif
     * pertama term yang memuat tanggal tsb sampai tanggal ini (inklusif).
     * Tanggal yang diberikan selalu dihitung sebagai satu pertemuan (karena
     * memang sedang dicatat setoran di tanggal itu), sekalipun jatuh di luar
     * hari efektif normal (mis. pertemuan susulan). Program dengan frekuensi
     * "seminggu sekali" hanya menghitung maksimal satu pertemuan per pekan
     * kalender. Untuk UMMI ($forUmmi), hanya Senin-Kamis yang dihitung --
     * Jumat dipakai untuk tahfizh mandiri dan tidak termasuk UMMI, sekalipun
     * kelasnya tetap aktif hari itu (tahfizh_days).
     */
    public function tatapMukaNumber(ClassRoom $classRoom, Carbon $date, bool $forUmmi = false): int
    {
        $isWeekly = $classRoom->program?->meeting_frequency === 'seminggu sekali';
        $targetDateString = $date->toDateString();

        $count = 0;
        $countedWeeks = [];
        $cursor = $this->termStartDate($date);

        while ($cursor->lte($date)) {
            $isTarget = $cursor->toDateString() === $targetDateString;

            if ($isTarget || $this->isEffectiveDay($classRoom, $cursor, $forUmmi)) {
                if ($isWeekly) {
                    $weekKey = $cursor->format('o-W');
                    if (! isset($countedWeeks[$weekKey])) {
                        $countedWeeks[$weekKey] = true;
                        $count++;
                    }
                } else {
                    $count++;
                }
            }

            $cursor = $cursor->copy()->addDay();
        }

        return $count;
    }
}
