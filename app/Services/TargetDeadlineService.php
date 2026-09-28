<?php

namespace App\Services;

use App\Models\HafalanTarget;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Deadline target hafalan = hari aktif terakhir di bulannya: Senin-Jumat terakhir yang bukan
 * libur untuk semua kelas di Kalender Akademik (sama untuk seluruh sekolah); bila tidak ada,
 * tanggal terakhir bulan. Semua target Aktif mengikuti aturan ini dan disesuaikan ulang saat
 * Kalender Akademik diubah (SchoolCalendar::saveMonth) dan tiap malam. Deadline manual
 * (deadline_manual, diatur guru) lebih tinggi: tidak ikut disesuaikan.
 */
class TargetDeadlineService
{
    public function __construct(private readonly SchoolCalendar $calendar) {}

    public function forMonth(CarbonInterface $date): Carbon
    {
        $monthStart = Carbon::parse($date)->startOfMonth()->startOfDay();
        $cursor = $monthStart->copy()->endOfMonth()->startOfDay();

        while ($cursor->gte($monthStart)) {
            if (! $cursor->isWeekend() && ! $this->calendar->isTahfizhHoliday(null, $cursor)) {
                return $cursor;
            }
            $cursor = $cursor->copy()->subDay();
        }

        return $monthStart->copy()->endOfMonth()->startOfDay();
    }

    /**
     * Samakan deadline target Aktif di satu bulan dengan hari aktif terakhir bulan itu.
     */
    public function syncMonth(int $year, int $month): int
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $deadline = $this->forMonth($monthStart)->toDateString();

        return HafalanTarget::query()
            ->where('status', 'active')
            ->where('deadline_manual', false)
            ->whereBetween('target_date', [$monthStart->toDateString(), $monthStart->copy()->endOfMonth()->toDateString().' 23:59:59'])
            ->whereDate('target_date', '!=', $deadline)
            ->update(['target_date' => $deadline]);
    }

    /**
     * Samakan deadline semua target Aktif (per bulan yang ada).
     */
    public function syncAll(): int
    {
        return HafalanTarget::query()
            ->where('status', 'active')
            ->where('deadline_manual', false)
            ->whereNotNull('target_date')
            ->pluck('target_date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m'))
            ->unique()
            ->sum(fn (string $month) => $this->syncMonth((int) substr($month, 0, 4), (int) substr($month, 5, 2)));
    }
}
