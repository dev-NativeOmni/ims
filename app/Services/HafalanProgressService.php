<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Surah;
use Illuminate\Support\Collection;

class HafalanProgressService
{
    public function totalQuranAyahs(): int
    {
        $total = (int) Surah::query()->sum('total_ayah');

        return $total > 0 ? $total : 6236;
    }

    public function memorizedAyahCount(Student $student): int
    {
        $recordsBySurah = $student->hafalanRecords()
            ->where('status', 'passed')
            ->select([
                'surah_id',
                'ayah_start',
                'ayah_end',
            ])
            ->get()
            ->groupBy('surah_id');

        return $recordsBySurah->sum(function (Collection $records) {
            return $this->mergeAndCountRanges($records);
        });
    }

    public function progressPercentage(Student $student): float
    {
        $totalAyahs = $this->totalQuranAyahs();

        if ($totalAyahs <= 0) {
            return 0;
        }

        return round(($this->memorizedAyahCount($student) / $totalAyahs) * 100, 2);
    }

    public function summary(Student $student): array
    {
        $memorizedAyahCount = $this->memorizedAyahCount($student);
        $totalAyahCount = $this->totalQuranAyahs();

        return [
            'memorized_ayah_count' => $memorizedAyahCount,
            'total_ayah_count' => $totalAyahCount,
            'progress_percentage' => $totalAyahCount > 0
                ? round(($memorizedAyahCount / $totalAyahCount) * 100, 2)
                : 0,
            'total_hafalan_records' => $student->hafalanRecords()->count(),
            'total_murajaah_records' => $student->murajaahRecords()->count(),
            'latest_hafalan' => $student->hafalanRecords()
                ->with([
                    'surah',
                    'teacher.user',
                ])
                ->latest('submitted_at')
                ->latest()
                ->first(),
            'latest_murajaah' => $student->murajaahRecords()
                ->with([
                    'surah',
                    'teacher.user',
                ])
                ->latest('reviewed_at')
                ->latest()
                ->first(),
        ];
    }

    private function mergeAndCountRanges(Collection $records): int
    {
        $ranges = $records
            ->map(function ($record) {
                return [
                    'start' => (int) $record->ayah_start,
                    'end' => (int) $record->ayah_end,
                ];
            })
            ->sortBy('start')
            ->values();

        if ($ranges->isEmpty()) {
            return 0;
        }

        $total = 0;
        $currentStart = null;
        $currentEnd = null;

        foreach ($ranges as $range) {
            $start = $range['start'];
            $end = $range['end'];

            if ($currentStart === null) {
                $currentStart = $start;
                $currentEnd = $end;

                continue;
            }

            if ($start <= $currentEnd + 1) {
                $currentEnd = max($currentEnd, $end);

                continue;
            }

            $total += ($currentEnd - $currentStart + 1);

            $currentStart = $start;
            $currentEnd = $end;
        }

        if ($currentStart !== null && $currentEnd !== null) {
            $total += ($currentEnd - $currentStart + 1);
        }

        return $total;
    }
}