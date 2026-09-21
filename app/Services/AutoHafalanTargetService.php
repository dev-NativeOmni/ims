<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Membuat & memperbarui target hafalan otomatis per murid per bulan untuk kelas
 * 11 dan 12 (Kelas 10 / UMMI memakai target buatan guru).
 *
 * Titik awal = surah & ayat pertama yang disetorkan murid di term tersebut.
 * Target akhir tiap bulan = titik awal + total baris target sampai bulan itu
 * (jumlah pertemuan terjadwal x baris per level), dihitung maju menurut mushaf
 * dengan hitungan baris otomatis yang sama dengan setoran.
 *
 * Target buatan guru selalu menang: bulan yang sudah punya target guru tidak dibuat
 * target otomatis, target otomatis yang diedit guru menjadi target guru, dan target
 * otomatis yang dihapus guru tidak dibuat ulang.
 */
class AutoHafalanTargetService
{
    private ?Collection $surahsByNumber = null;

    public function __construct(
        private readonly AcademicCalendarService $calendar,
        private readonly QuranLineTargetService $quran,
        private readonly HafalanTargetAutoCompletionService $completion,
    ) {}

    public static function levelBaris(?string $tahfizhLevel): ?int
    {
        return match ($tahfizhLevel) {
            'tahsin' => 3,
            'reguler' => 5,
            'akselerasi' => 7,
            'ummi' => null,
            default => 5,
        };
    }

    /**
     * Sinkronkan target otomatis satu murid untuk term yang memuat $date.
     */
    public function syncStudent(Student $student, Carbon $date): void
    {
        $student->loadMissing('classRoom.program');
        $classRoom = $student->classRoom;
        $termStart = $this->calendar->termStartDate($date);
        $months = $this->calendar->termMonths($date);
        $termEnd = end($months)['end'];

        $desired = $this->desiredTargets($student, $classRoom, $months, $termStart, $termEnd);

        $autoRows = HafalanTarget::withTrashed()
            ->where('student_id', $student->id)
            ->whereIn('auto_month', array_keys($months))
            ->get()
            ->keyBy('auto_month');

        $manualMonths = HafalanTarget::query()
            ->where('student_id', $student->id)
            ->whereNull('auto_month')
            ->whereBetween('target_date', [$termStart->toDateString(), $termEnd->copy()->endOfDay()->toDateTimeString()])
            ->pluck('target_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
            ->unique()
            ->all();

        foreach ($months as $monthKey => $range) {
            $row = $autoRows->get($monthKey);

            // Bulan yang sudah punya target guru tidak diisi target otomatis.
            $position = in_array($monthKey, $manualMonths, true) ? null : ($desired[$monthKey] ?? null);

            if ($position === null) {
                if ($row && ! $row->trashed()) {
                    $row->forceDelete();
                }

                continue;
            }

            // Target otomatis yang dihapus guru tidak dibuat ulang.
            if ($row?->trashed()) {
                continue;
            }

            $attributes = [
                'surah_id' => $position['surah']->id,
                'ayah' => $position['ayah_end'],
                'target_date' => $range['end']->toDateString(),
            ];

            if ($row === null) {
                if (! $student->teacher_id) {
                    continue;
                }

                $row = HafalanTarget::create($attributes + [
                    'student_id' => $student->id,
                    'teacher_id' => $student->teacher_id,
                    'status' => 'active',
                    'auto_month' => $monthKey,
                    'notes' => 'Target otomatis (jumlah pertemuan x level, dari setoran pertama term).',
                ]);
            } elseif ($row->surah_id !== $attributes['surah_id']
                || (int) $row->ayah !== $attributes['ayah']
                || $row->target_date?->toDateString() !== $attributes['target_date']) {
                $row->fill($attributes);
                $row->status = 'active';
                $row->completed_at = null;
                $row->save();
            } else {
                // Tidak ada perubahan posisi: status tetap (mis. sudah completed).
                continue;
            }

            if ($this->completion->matchingPassedRecordForTarget($row)) {
                $row->update(['status' => 'completed', 'completed_at' => now()]);
            }
        }
    }

    /**
     * Sinkronkan semua murid aktif kelas 11/12 dalam satu kelas.
     */
    public function syncClass(ClassRoom $classRoom, Carbon $date): void
    {
        if ($classRoom->isGradeTen()) {
            return;
        }

        Student::query()
            ->where('class_room_id', $classRoom->id)
            ->where('status', 'active')
            ->get()
            ->each(function (Student $student) use ($date, $classRoom) {
                $student->setRelation('classRoom', $classRoom);
                $this->syncStudent($student, $date);
            });
    }

    /**
     * Posisi target akhir tiap bulan, atau array kosong bila murid tidak berhak/tidak punya titik awal.
     *
     * @return array<string, array{surah: Surah, ayah_start: int, ayah_end: int}>
     */
    private function desiredTargets(Student $student, ?ClassRoom $classRoom, array $months, Carbon $termStart, Carbon $termEnd): array
    {
        $levelBaris = self::levelBaris($student->tahfizh_level);

        if (! $classRoom || $classRoom->isGradeTen() || $levelBaris === null) {
            return [];
        }

        $first = HafalanRecordSurah::query()
            ->select('hafalan_record_surahs.*')
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at')
            ->where('hafalan_records.student_id', $student->id)
            ->whereBetween('hafalan_records.submitted_at', [$termStart->toDateString(), $termEnd->copy()->endOfDay()->toDateTimeString()])
            ->orderBy('hafalan_records.submitted_at')
            ->orderBy('hafalan_records.id')
            ->orderBy('hafalan_record_surahs.sort_order')
            ->orderBy('hafalan_record_surahs.id')
            ->with('surah')
            ->first();

        if (! $first || ! $first->surah) {
            return [];
        }

        $surahs = $this->surahsByNumber ??= Surah::query()->get()->keyBy('number');

        $desired = [];
        $cumulativeLines = 0;

        foreach ($months as $monthKey => $range) {
            $meetings = $this->calendar->scheduledMeetings($classRoom, $range['start'], $range['end']);
            $cumulativeLines += $levelBaris * $meetings;

            if ($meetings === 0) {
                continue;
            }

            $position = $this->quran->targetPosition(
                (int) $first->surah->number,
                (int) $first->ayah_start,
                (float) $cumulativeLines,
                $surahs
            );

            if ($position !== null) {
                $desired[$monthKey] = $position;
            }
        }

        return $desired;
    }
}
