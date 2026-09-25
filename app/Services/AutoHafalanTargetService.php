<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Membuat & memperbarui target hafalan otomatis per murid per bulan untuk kelas
 * 11 dan 12 (Kelas 10 / UMMI memakai target buatan guru).
 *
 * Titik awal = surah & ayat pertama yang disetorkan murid di term tersebut.
 * Target akhir tiap bulan = titik awal + total baris target sampai bulan itu
 * (jumlah pertemuan terjadwal x baris per level), dihitung menurut urutan hafalan
 * sekolah (Juz 30 fleksibel, lalu Juz 29, 28, ... dari awal juz; lihat termPlan()).
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
        $plan = $this->termPlan($student, $termStart, $classRoom);

        return collect($plan['months'])
            ->filter(fn ($month) => $month['position'] !== null)
            ->map(fn ($month) => $month['position'])
            ->all();
    }

    /**
     * Rencana target satu triwulan untuk satu murid (dipakai target bulanan otomatis dan
     * halaman Target Triwulan): titik awal = setoran pertama triwulan, target = titik awal +
     * (pertemuan aktif x baris per level), dihitung menurut urutan hafalan sekolah
     * (Juz 30 fleksibel, lalu Juz 29, 28, ... dari awal juz). Target bulan = titik antara.
     *
     * @return array{
     *     eligible: bool, reason: ?string, level_baris: ?int,
     *     start: ?array{surah: Surah, ayah: int, date: ?string},
     *     months: array<string, array{label: string, meetings: int, cumulative_lines: int, position: ?array}>,
     *     term_meetings: int, target_lines: int, target: ?array,
     *     capaian: ?array{surah: Surah, ayah: int, date: ?string}, achieved_lines: float, progress: int, reached: bool
     * }
     */
    public function termPlan(Student $student, Carbon $date, ?ClassRoom $classRoom = null): array
    {
        $classRoom ??= $student->classRoom;
        $termStart = $this->calendar->termStartDate($date);
        $months = $this->calendar->termMonths($date);
        $termEnd = end($months)['end'];
        $levelBaris = self::levelBaris($student->tahfizh_level);

        $plan = [
            'eligible' => false, 'reason' => null, 'level_baris' => $levelBaris, 'start' => null,
            'months' => [], 'term_meetings' => 0, 'target_lines' => 0, 'target' => null,
            'capaian' => null, 'achieved_lines' => 0.0, 'progress' => 0, 'reached' => false,
        ];

        if (! $classRoom) {
            return ['reason' => 'no_class'] + $plan;
        }
        if ($classRoom->isGradeTen() || $levelBaris === null) {
            return ['reason' => 'ummi'] + $plan;
        }

        // Pertemuan aktif per bulan (jadwal kelas pekanan x kalender) -> baris kumulatif.
        $cumulative = 0;
        foreach ($months as $monthKey => $range) {
            $meetings = $this->calendar->scheduledMeetings($classRoom, $range['start'], $range['end']);
            $cumulative += $levelBaris * $meetings;
            $plan['months'][$monthKey] = [
                'label' => $range['start']->locale('id')->translatedFormat('F Y'),
                'meetings' => $meetings,
                'cumulative_lines' => $cumulative,
                'position' => null,
            ];
            $plan['term_meetings'] += $meetings;
        }
        $plan['target_lines'] = $cumulative;

        $termRecords = HafalanRecordSurah::query()
            ->select('hafalan_record_surahs.*', 'hafalan_records.submitted_at')
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at')
            ->where('hafalan_records.student_id', $student->id)
            ->whereBetween('hafalan_records.submitted_at', [$termStart->toDateString(), $termEnd->copy()->endOfDay()->toDateTimeString()])
            ->orderBy('hafalan_records.submitted_at')
            ->orderBy('hafalan_records.id')
            ->orderBy('hafalan_record_surahs.sort_order')
            ->orderBy('hafalan_record_surahs.id')
            ->with('surah')
            ->get()
            ->filter(fn ($record) => $record->surah !== null)
            ->values();

        $first = $termRecords->first();
        if (! $first) {
            return ['eligible' => true, 'reason' => 'no_start'] + $plan;
        }

        $surahs = $this->surahsByNumber ??= Surah::query()->get()->keyBy('number');
        $startSurah = (int) $first->surah->number;
        $startAyah = (int) $first->ayah_start;
        $juz30Done = $this->juz30DoneBefore($student, $termStart);

        $plan['eligible'] = true;
        $plan['start'] = ['surah' => $first->surah, 'ayah' => $startAyah, 'date' => $first->submitted_at ? Carbon::parse($first->submitted_at)->toDateString() : null];

        foreach ($plan['months'] as $monthKey => $month) {
            if ($month['meetings'] > 0) {
                $plan['months'][$monthKey]['position'] = $this->quran->targetPosition($startSurah, $startAyah, (float) $month['cumulative_lines'], $surahs, $juz30Done);
            }
        }
        $plan['target'] = $this->quran->targetPosition($startSurah, $startAyah, (float) $plan['target_lines'], $surahs, $juz30Done);

        // Capaian = setoran lulus terjauh di triwulan ini menurut urutan hafalan.
        $furthest = $termRecords
            ->filter(fn ($record) => $record->status === 'passed')
            ->sortByDesc(fn ($record) => HafalanOrder::rank((int) $record->surah->number, (int) $record->ayah_end))
            ->first();

        if ($furthest) {
            $plan['capaian'] = [
                'surah' => $furthest->surah,
                'ayah' => (int) $furthest->ayah_end,
                'date' => $furthest->submitted_at ? Carbon::parse($furthest->submitted_at)->toDateString() : null,
            ];
            $plan['achieved_lines'] = round($this->quran->linesUntil($startSurah, $startAyah, (int) $furthest->surah->number, (int) $furthest->ayah_end, $surahs, $juz30Done), 1);
            $plan['progress'] = $plan['target_lines'] > 0 ? (int) min(100, round($plan['achieved_lines'] / $plan['target_lines'] * 100)) : 0;
            $plan['reached'] = $plan['target'] !== null && $this->quran->hasReached(
                (int) $furthest->surah->number, (int) $furthest->ayah_end,
                (int) $plan['target']['surah']->number, (int) $plan['target']['ayah_end']
            );
        }

        return $plan;
    }

    /**
     * Surah Juz 30 yang sudah disetor (lulus) sebelum triwulan: nomor surah => ayat terakhir.
     * Juz 30 fleksibel, jadi sisa targetnya adalah surah/ayat yang belum pernah disetor.
     *
     * @return array<int, int>
     */
    private function juz30DoneBefore(Student $student, Carbon $termStart): array
    {
        return HafalanRecordSurah::query()
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->join('surahs', 'surahs.id', '=', 'hafalan_record_surahs.surah_id')
            ->whereNull('hafalan_records.deleted_at')
            ->where('hafalan_records.student_id', $student->id)
            ->where('hafalan_records.submitted_at', '<', $termStart->toDateString())
            ->where('hafalan_record_surahs.status', 'passed')
            ->whereBetween('surahs.number', [78, 114])
            ->groupBy('surahs.number')
            ->selectRaw('surahs.number as surah_number, max(hafalan_record_surahs.ayah_end) as ayah_end')
            ->pluck('ayah_end', 'surah_number')
            ->map(fn ($ayah) => (int) $ayah)
            ->all();
    }
}
