<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Support\HafalanOrder;
use App\Support\TargetRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Target triwulan kelas 11 & 12 (Kelas 10 / UMMI memakai target jilid di Target Bulanan).
 *
 * Target diisi manual oleh guru per bulan di halaman Target Triwulan (satu target per murid
 * per bulan, deadline = pertemuan aktif terakhir bulan itu). Target triwulan = target bulan
 * terakhir yang terisi -- sumber yang sama dengan Laporan Triwulan & rapor.
 */
class AutoHafalanTargetService
{
    public function __construct(
        private readonly AcademicCalendarService $calendar,
        private readonly HafalanProgressService $progress,
    ) {}

    /**
     * Baris per pertemuan (Pengaturan Target Hafalan); null untuk Ummi.
     */
    public static function levelBaris(?string $tahfizhLevel): ?int
    {
        return TargetRules::linesForLevel($tahfizhLevel);
    }

    /**
     * Bulan-bulan triwulan untuk satu kelas: label, pertemuan aktif, dan deadline
     * (pertemuan aktif terakhir; akhir bulan bila bulan itu tidak ada pertemuan).
     *
     * @return array<string, array{label: string, start: Carbon, end: Carbon, meetings: int, deadline: Carbon, has_meeting: bool}>
     */
    public function termMonths(ClassRoom $classRoom, Carbon $date): array
    {
        $months = [];
        foreach ($this->calendar->termMonths($date) as $monthKey => $range) {
            $last = $this->calendar->lastMeetingDate($classRoom, $range['start'], $range['end']);
            $months[$monthKey] = [
                'label' => $range['start']->locale('id')->translatedFormat('F Y'),
                'start' => $range['start'],
                'end' => $range['end'],
                'meetings' => $this->calendar->scheduledMeetings($classRoom, $range['start'], $range['end']),
                'deadline' => $last ?? $range['end']->copy(),
                'has_meeting' => $last !== null,
            ];
        }

        return $months;
    }

    /**
     * Target satu murid per bulan di triwulan ini (target terakhir di bulan itu), target triwulan
     * (bulan terakhir yang terisi), capaian, dan progres cakupan ayat terhadap target triwulan.
     */
    public function termPlan(Student $student, Carbon $date, ?ClassRoom $classRoom = null, ?array $months = null): array
    {
        $classRoom ??= $student->classRoom;
        $plan = [
            'eligible' => false, 'reason' => null, 'level_baris' => self::levelBaris($student->tahfizh_level),
            'start' => null, 'months' => [], 'target' => null, 'target_month' => null,
            'capaian' => null, 'progress' => 0, 'reached' => false, 'juz_orders' => [],
            'juz_order_source' => fn (int $juz) => 'default',
        ];

        if (! $classRoom) {
            return ['reason' => 'no_class'] + $plan;
        }
        if ($classRoom->isGradeTen()) {
            return ['reason' => 'ummi'] + $plan;
        }

        $months ??= $this->termMonths($classRoom, $date);
        $termStart = reset($months)['start'];
        $termEnd = end($months)['end'];
        $plan['eligible'] = true;

        $stored = $this->storedTermTargets($student, $termStart, $termEnd);
        foreach ($months as $monthKey => $month) {
            $target = $stored->filter(fn (HafalanTarget $t) => $t->target_date->format('Y-m') === $monthKey)->last();
            $plan['months'][$monthKey] = ['target' => $target];
            if ($target?->surah) {
                $plan['target'] = $target;
                $plan['target_month'] = $monthKey;
            }
        }

        $records = $this->progress->records($student);
        $surahs = $this->progress->surahs();
        $juzOrders = $this->progress->juzOrders($student, $records);
        $manualJuz = array_map('intval', array_keys($student->juz_orders ?? []));
        $detected = $this->progress->detectedJuzOrders($records);
        $plan['juz_orders'] = $juzOrders;
        $plan['juz_order_source'] = fn (int $juz) => in_array($juz, $manualJuz, true) ? 'manual' : (isset($detected[$juz]) ? 'auto' : 'default');

        $first = $this->progress->firstBetween($records, $termStart, $termEnd);
        if ($first) {
            $plan['start'] = [
                'surah' => $surahs->get((int) $first->surah_number),
                'ayah' => (int) $first->ayah_start,
                'juz' => HafalanOrder::juzOf((int) $first->surah_number, (int) $first->ayah_start),
                'date' => Carbon::parse($first->submitted_at)->toDateString(),
            ];
        }

        // Capaian = setoran lulus terakhir di triwulan ini; progres & tuntas dari cakupan ayat.
        $latest = $records
            ->filter(fn ($r) => $r->status === 'passed' && Carbon::parse($r->submitted_at)->betweenIncluded($termStart, $termEnd->copy()->endOfDay()))
            ->last();
        if ($latest) {
            $plan['capaian'] = [
                'surah' => $surahs->get((int) $latest->surah_number),
                'ayah' => (int) $latest->ayah_end,
                'date' => Carbon::parse($latest->submitted_at)->toDateString(),
            ];
        }

        if ($plan['target']) {
            $evaluation = $this->progress->evaluate($student, (int) $plan['target']->surah->number, (int) $plan['target']->ayah, $termStart, $termEnd, now(), $records);
            $plan['reached'] = $evaluation['reached'];
            $plan['progress'] = $evaluation['progress'];
        }

        return $plan;
    }

    /**
     * Status target setelah disimpan: completed bila semua ayat dari titik awal triwulan
     * sampai target sudah lulus disetor.
     */
    public function refreshStatus(HafalanTarget $target, Carbon $termStart, Carbon $termEnd): void
    {
        $target->loadMissing('student', 'surah');
        if (! $target->student || ! $target->surah) {
            return;
        }

        $reached = $this->progress->evaluate($target->student, (int) $target->surah->number, (int) $target->ayah, $termStart, $termEnd, now())['reached'];
        $target->update($reached
            ? ['status' => 'completed', 'completed_at' => $target->completed_at ?? now()]
            : ['status' => 'active', 'completed_at' => null]);
    }

    /**
     * Target tersimpan (tidak dihapus) di dalam triwulan, urut tanggal -- sumber yang sama dengan laporan.
     */
    public function storedTermTargets(Student $student, Carbon $termStart, Carbon $termEnd): Collection
    {
        return HafalanTarget::query()
            ->with('surah')
            ->where('student_id', $student->id)
            ->whereBetween('target_date', [$termStart->toDateString(), $termEnd->copy()->endOfDay()->toDateTimeString()])
            ->orderBy('target_date')
            ->orderBy('id')
            ->get();
    }
}
