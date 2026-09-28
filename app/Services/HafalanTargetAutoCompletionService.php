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
 * Target Ummi dinilai terpisah: status Buku (posisi buku terakhir >= Jilid & Halaman Buku) dan
 * status Hafalan (posisi hafalan surah >= Surah & Ayat); status keseluruhan = Aktif bila masih ada
 * bagian aktif, Selesai bila semua bagian selesai, selain itu Terlewat.
 * Dijalankan tiap malam (tad:sync-completed-targets) dan langsung saat setoran disimpan
 * (HafalanTargetStatusObserver).
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

    /**
     * Evaluasi ulang satu target sekarang (mis. setelah disimpan dari halaman Target Ummi).
     */
    public function refresh(HafalanTarget $target): void
    {
        $target->loadMissing(['student', 'surah']);
        $updates = $this->updatesFor($target);
        if ($updates !== null) {
            $target->update($updates);
        }
    }

    /**
     * Perubahan atribut untuk satu target, atau null bila tidak ada yang berubah.
     */
    private function updatesFor(HafalanTarget $target): ?array
    {
        if ($target->ummi_jilid) {
            return $this->ummiUpdates($target);
        }

        $status = $this->evaluate($target);
        if ($status === null) {
            return null;
        }

        return $status === 'completed'
            ? ['status' => 'completed', 'completed_at' => $this->matchingPassedRecordForTarget($target)?->hafalanRecord?->submitted_at?->copy()->endOfDay() ?? now()]
            : ['status' => 'missed'];
    }

    /**
     * Target Ummi: bagian yang masih aktif dinilai (Selesai bila tercapai, Terlewat bila deadline
     * lewat); bagian yang sudah Selesai/Terlewat tidak berubah lagi.
     */
    private function ummiUpdates(HafalanTarget $target): ?array
    {
        if (! $target->student || ! $target->target_date) {
            return null;
        }

        $ummi = app(UmmiProgressService::class);
        $goal = $ummi->targetValues($target);
        $position = $ummi->positionsFor([$target->student_id], now())[$target->student_id];
        $pastDeadline = $target->target_date->lt(today());

        $part = function (?string $current, ?int $goalValue, ?int $positionValue) use ($pastDeadline): ?string {
            if ($goalValue === null) {
                return null;
            }
            if (in_array($current, ['completed', 'missed'], true)) {
                return $current;
            }
            if ((int) $positionValue >= $goalValue) {
                return 'completed';
            }

            return $pastDeadline ? 'missed' : 'active';
        };

        $book = $part($target->book_status, $goal['book'], $position['book']);
        $surah = $part($target->surah_status, $goal['hafalan'], $position['hafalan']);
        $parts = array_values(array_filter([$book, $surah]));

        $status = match (true) {
            $parts === [] => $target->status,
            in_array('active', $parts, true) => 'active',
            ! in_array('missed', $parts, true) => 'completed',
            default => 'missed',
        };

        $updates = ['book_status' => $book, 'surah_status' => $surah, 'status' => $status];
        if ($status === 'completed' && $target->status !== 'completed') {
            $updates['completed_at'] = now();
        }

        $changed = array_filter($updates, fn ($value, $key) => $target->{$key} !== $value, ARRAY_FILTER_USE_BOTH);

        return $changed === [] ? null : $changed;
    }

    private function activeTargets(): Builder
    {
        // Target surah & ayat biasa, atau target Ummi (Jilid & Halaman Buku, surah opsional).
        return HafalanTarget::query()
            ->with(['student', 'surah'])
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNotNull('ummi_jilid')->orWhereNotNull('surah_id'));
    }

    /**
     * @return array{completed: int, missed: int}
     */
    private function syncQuery(Builder $query, bool $dryRun): array
    {
        $counts = ['completed' => 0, 'missed' => 0];

        $query->orderBy('id')->chunkById(100, function ($targets) use (&$counts, $dryRun) {
            foreach ($targets as $target) {
                $updates = $this->updatesFor($target);
                if ($updates === null) {
                    continue;
                }

                if (in_array($updates['status'] ?? null, ['completed', 'missed'], true)) {
                    $counts[$updates['status']]++;
                }
                if (! $dryRun) {
                    $target->update($updates);
                }
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
