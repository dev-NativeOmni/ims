<?php

namespace App\Services;

use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Status otomatis target guru (surah & ayat):
 * - Selesai  : semua ayat dari setoran pertama triwulan target sampai ayat target sudah lulus
 *              disetor (aturan yang sama dengan Target Triwulan), termasuk bila capaian melampaui;
 * - Terlewat : deadline sudah lewat (sebelum hari ini) dan target belum tercapai.
 * Dijalankan tiap malam (tad:sync-completed-targets) dan langsung saat setoran disimpan
 * (HafalanTargetStatusObserver). Target Ummi (Jilid/Halaman) belum diotomasi.
 */
class HafalanTargetAutoCompletionService
{
    /**
     * Evaluasi semua target aktif (tiap malam).
     *
     * @return array{completed: int, missed: int}
     */
    public function syncExistingTargets(bool $dryRun = false): array
    {
        return $this->syncQuery($this->activeTargets(), $dryRun);
    }

    /**
     * Evaluasi target aktif murid tertentu (setelah setoran disimpan/dihapus).
     *
     * @param  array<int, int>  $studentIds
     * @return array{completed: int, missed: int}
     */
    public function syncStudents(array $studentIds): array
    {
        return $studentIds === []
            ? ['completed' => 0, 'missed' => 0]
            : $this->syncQuery($this->activeTargets()->whereIn('student_id', $studentIds), false);
    }

    /**
     * Status baru untuk satu target: 'completed', 'missed', atau null (tetap aktif).
     */
    public function evaluate(HafalanTarget $target): ?string
    {
        if (! $target->student || ! $target->surah || ! $target->target_date) {
            return null;
        }

        $months = app(AcademicCalendarService::class)->termMonths($target->target_date);
        $reached = app(HafalanProgressService::class)->evaluate(
            $target->student, (int) $target->surah->number, (int) ($target->ayah ?: $target->surah->total_ayah),
            reset($months)['start'], end($months)['end'], now()
        )['position_reached'];

        if ($reached) {
            return 'completed';
        }

        return $target->target_date->lt(today()) ? 'missed' : null;
    }

    private function activeTargets(): Builder
    {
        return HafalanTarget::query()
            ->with(['student', 'surah'])
            ->where('status', 'active')
            ->whereNotNull('surah_id')
            ->whereNull('ummi_jilid');
    }

    /**
     * @return array{completed: int, missed: int}
     */
    private function syncQuery(Builder $query, bool $dryRun): array
    {
        $counts = ['completed' => 0, 'missed' => 0];

        $query->orderBy('id')->chunkById(100, function ($targets) use (&$counts, $dryRun) {
            foreach ($targets as $target) {
                $status = $this->evaluate($target);
                if ($status === null) {
                    continue;
                }

                $counts[$status]++;
                if ($dryRun) {
                    continue;
                }

                $target->update($status === 'completed'
                    ? ['status' => 'completed', 'completed_at' => $this->matchingPassedRecordForTarget($target)?->hafalanRecord?->submitted_at?->copy()->endOfDay() ?? now()]
                    : ['status' => 'missed']);
            }
        });

        return $counts;
    }

    public function matchingPassedRecordForTarget(HafalanTarget $target): ?HafalanRecordSurah
    {
        return HafalanRecordSurah::query()
            ->select('hafalan_record_surahs.*')
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at')
            ->where('hafalan_records.student_id', $target->student_id)
            ->where('hafalan_record_surahs.surah_id', $target->surah_id)
            ->where('hafalan_record_surahs.status', 'passed')
            ->where('hafalan_record_surahs.ayah_end', '>=', $target->ayah)
            ->with('hafalanRecord')
            ->orderBy('hafalan_records.submitted_at')
            ->orderBy('hafalan_record_surahs.id')
            ->first();
    }
}
