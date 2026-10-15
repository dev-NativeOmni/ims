<?php

namespace App\Services;

use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Support\AcademicYear;
use App\Support\TargetRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Tabungan baris Tahfizh Kelas 11 & 12 (keputusan sekolah, Oktober 2026; docs/tabungan-baris.md):
 * - Menabung: kelebihan baris ayat baru di atas target triwulan masuk tabungan (semua kelebihan,
 *   apa pun posisi surah:ayat), Program Reguler & Tahfizh.
 * - Memakai: triwulan yang barisnya kurang memakai tabungan HANYA bila saldo cukup menutup seluruh
 *   kekurangan (jadi Tuntas); bila tidak cukup, saldo utuh.
 * - Saldo maksimal = target baris triwulan berjalan (SAVINGS_CAP_TERMS x target); berlaku sampai lulus
 *   (terbawa dari kelas 11 ke 12), dihitung sejak AcademicYear::FIRST. Triwulan Kelas 10/Ummi dilewati.
 * Saldo tidak disimpan: dihitung ulang dari riwayat setoran tiap triwulan (aturan baris sama dengan rapor,
 * HafalanProgressService::termBreakdown tanpa tabungan). Rapor terkunci menyimpan angkanya sendiri.
 */
class LineSavingsService
{
    /** Saldo maksimal = berapa kali target baris triwulan. */
    public const SAVINGS_CAP_TERMS = 1;

    /** @var array<string, float> saldo sebelum triwulan, per murid & awal triwulan (satu request) */
    private array $balances = [];

    /**
     * Tabungan untuk satu triwulan: saldo sebelum, dipakai, ditabung, saldo sesudah.
     *
     * @return array{before: float, used: float, deposit: float, after: float}
     */
    public function forTerm(Student $student, Carbon $termStart, float $achieved, float $target, ?Collection $records = null): array
    {
        return self::apply($achieved, $target, $this->balanceBefore($student, $termStart, $records));
    }

    /**
     * @return array{before: float, used: float, deposit: float, after: float}
     */
    public static function apply(float $achieved, float $target, float $before): array
    {
        $before = round($before, 1);
        $result = ['before' => $before, 'used' => 0.0, 'deposit' => 0.0, 'after' => $before];
        if ($target <= 0) {
            return $result;
        }

        if ($achieved >= $target) {
            $cap = $target * self::SAVINGS_CAP_TERMS;
            $after = max($before, min($before + ($achieved - $target), $cap));
            $result['deposit'] = round($after - $before, 1);
            $result['after'] = round($after, 1);
        } elseif ($before + 1e-9 >= $target - $achieved) {
            $result['used'] = round($target - $achieved, 1);
            $result['after'] = round($before - ($target - $achieved), 1);
        }

        return $result;
    }

    /** Saldo tabungan sebelum triwulan yang dimulai $termStart. */
    public function balanceBefore(Student $student, Carbon $termStart, ?Collection $records = null): float
    {
        $key = $student->id.'|'.$termStart->toDateString();
        if (isset($this->balances[$key])) {
            return $this->balances[$key];
        }

        $progress = app(HafalanProgressService::class);
        $calendar = app(AcademicCalendarService::class);
        $records ??= $progress->records($student);
        $targets = HafalanTarget::with('surah')->where('student_id', $student->id)->get()->filter(fn ($t) => $t->surah);

        $balance = 0.0;
        foreach ($this->termsBefore($termStart) as [$start, $end]) {
            $class = $student->classRoomOn(StudentClassHistory::referenceDate($end));
            if (! $class) {
                continue;
            }
            $atTerm = clone $student;
            $atTerm->setRelation('classRoom', $class->loadMissing('program'));
            if ($atTerm->usesUmmi() || TargetRules::linesForLevel($atTerm->tahfizh_level) === null) {
                continue;
            }

            $termTargets = $targets->filter(fn ($t) => $t->target_date && Carbon::parse($t->target_date)->betweenIncluded($start, $end->copy()->endOfDay()));
            $evaluation = $progress->termBreakdown($atTerm, $termTargets, $calendar->termMonths($start), now()->min($end->copy()->endOfDay()), $records, false)['evaluation'];
            $balance = self::apply((float) $evaluation['achieved_lines'], (float) $evaluation['target_lines'], $balance)['after'];
        }

        return $this->balances[$key] = round($balance, 1);
    }

    /**
     * Triwulan sejak AcademicYear::FIRST yang sudah dimulai dan berakhir sebelum $termStart.
     *
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    private function termsBefore(Carbon $termStart): array
    {
        $terms = [];
        $year = AcademicYear::FIRST;
        for ($i = 0; $i < 40; $i++) {
            foreach ([1, 2, 3, 4] as $term) {
                [$start, $end] = AcademicYear::termRange($year, $term);
                if ($start->gte($termStart->copy()->startOfDay()) || $start->gt(now())) {
                    return $terms;
                }
                $terms[] = [$start, $end];
            }
            $next = AcademicYear::startYear($year) + 1;
            $year = $next.'/'.($next + 1);
        }

        return $terms;
    }
}
