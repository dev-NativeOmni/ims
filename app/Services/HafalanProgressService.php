<?php

namespace App\Services;

use App\Http\Controllers\ReportController;
use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use App\Support\AyahCoverage;
use App\Support\HafalanOrder;
use App\Support\TargetRules;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Progres hafalan satu murid menurut urutan hafalan sekolah -- satu sumber untuk
 * Target Triwulan, Laporan Triwulan, Laporan Periodik, dan Wali Kelas:
 *
 * - Cakupan: ayat yang sudah lulus disetor (urutan bebas).
 * - Urutan di dalam juz: dideteksi dari setoran (surah makin kecil = dari akhir juz),
 *   bisa dikoreksi guru (Student::juz_orders).
 * - Titik awal triwulan: setoran pertama di triwulan itu.
 * - Target baris: pertemuan aktif x baris per level; capaian baris: setoran lulus di triwulan.
 * - Target guru (surah & ayat): arah hafalan; tercapai bila semua ayat sampai target lulus.
 * - Tuntas: capaian baris >= target baris.
 */
class HafalanProgressService
{
    private ?Collection $surahsByNumber = null;

    public function __construct(private readonly QuranLineTargetService $quran) {}

    public function surahs(): Collection
    {
        return $this->surahsByNumber ??= Surah::query()->get()->keyBy('number');
    }

    /**
     * Semua setoran murid (semua status), urut waktu: surah_number, ayah_start, ayah_end, status, submitted_at.
     */
    public function records(Student $student): Collection
    {
        return HafalanRecordSurah::query()
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->join('surahs', 'surahs.id', '=', 'hafalan_record_surahs.surah_id')
            ->whereNull('hafalan_records.deleted_at')
            ->where('hafalan_records.student_id', $student->id)
            ->orderBy('hafalan_records.submitted_at')
            ->orderBy('hafalan_records.id')
            ->orderBy('hafalan_record_surahs.sort_order')
            ->orderBy('hafalan_record_surahs.id')
            ->get([
                'surahs.number as surah_number', 'hafalan_record_surahs.ayah_start', 'hafalan_record_surahs.ayah_end',
                'hafalan_record_surahs.status', 'hafalan_record_surahs.baris', 'hafalan_records.submitted_at',
            ]);
    }

    /**
     * Cakupan ayat lulus dengan batas waktu: $before (eksklusif, tanggal) dan/atau $until (inklusif, sampai akhir hari).
     *
     * @return array<int, array<int, array{0: int, 1: int}>>
     */
    public function coverage(Collection $records, ?Carbon $before = null, ?Carbon $until = null): array
    {
        return AyahCoverage::fromRanges(
            $records
                ->filter(fn ($r) => $r->status === 'passed')
                ->filter(fn ($r) => $before === null || Carbon::parse($r->submitted_at)->lt($before->copy()->startOfDay()))
                ->filter(fn ($r) => $until === null || Carbon::parse($r->submitted_at)->lte($until->copy()->endOfDay()))
                ->map(fn ($r) => [(int) $r->surah_number, (int) $r->ayah_start, (int) $r->ayah_end])
        );
    }

    /**
     * Urutan di dalam juz hasil deteksi setoran: juz => 'asc'|'desc'. Juz dengan kurang
     * dari dua surah berbeda tidak dideteksi (memakai default / koreksi guru).
     *
     * @return array<int, string>
     */
    public function detectedJuzOrders(Collection $records): array
    {
        $surahsByJuz = [];
        foreach ($records->where('status', 'passed') as $record) {
            $juz = HafalanOrder::juzOf((int) $record->surah_number, (int) $record->ayah_start);
            $surahsByJuz[$juz] ??= [];
            if (! in_array((int) $record->surah_number, $surahsByJuz[$juz], true)) {
                $surahsByJuz[$juz][] = (int) $record->surah_number;
            }
        }

        $orders = [];
        foreach ($surahsByJuz as $juz => $surahs) {
            if (count($surahs) >= 2) {
                $orders[$juz] = end($surahs) < $surahs[0] ? HafalanOrder::DESC : HafalanOrder::ASC;
            }
        }

        return $orders;
    }

    /**
     * Urutan di dalam juz yang dipakai: koreksi guru > deteksi setoran > default.
     *
     * @return array<int, string>
     */
    public function juzOrders(Student $student, Collection $records): array
    {
        $manual = collect($student->juz_orders ?? [])
            ->filter(fn ($order) => in_array($order, [HafalanOrder::ASC, HafalanOrder::DESC], true))
            ->mapWithKeys(fn ($order, $juz) => [(int) $juz => $order])
            ->all();

        return $manual + $this->detectedJuzOrders($records);
    }

    /**
     * Setoran pertama pada rentang tanggal (titik awal triwulan).
     */
    public function firstBetween(Collection $records, Carbon $start, Carbon $end): mixed
    {
        return $records->first(fn ($r) => Carbon::parse($r->submitted_at)->betweenIncluded($start->copy()->startOfDay(), $end->copy()->endOfDay()));
    }

    /**
     * Titik awal triwulan = setoran pertama murid di triwulan itu. Belum ada setoran di
     * triwulan: lanjutan setoran lulus terakhir sebelum triwulan (ayat yang sudah dihafal
     * dilewati oleh HafalanOrder::segments), lalu awal urutan hafalan.
     *
     * @return array{surah: int, ayah: int, source: string, date: ?string}
     */
    public function startPoint(Student $student, Collection $records, Carbon $termStart, Carbon $termEnd): array
    {
        $first = $this->firstBetween($records, $termStart, $termEnd);
        if ($first) {
            return ['surah' => (int) $first->surah_number, 'ayah' => (int) $first->ayah_start, 'source' => 'first_setoran', 'date' => Carbon::parse($first->submitted_at)->toDateString()];
        }

        $before = $records
            ->filter(fn ($r) => $r->status === 'passed' && Carbon::parse($r->submitted_at)->lt($termStart->copy()->startOfDay()))
            ->last();
        if ($before) {
            return ['surah' => (int) $before->surah_number, 'ayah' => (int) $before->ayah_end, 'source' => 'history', 'date' => Carbon::parse($before->submitted_at)->toDateString()];
        }

        $firstJuz = HafalanOrder::juzSequence($student->hafalan_direction)[0];
        [$surah, $ayah] = HafalanOrder::juzPieces($firstJuz, $this->juzOrders($student, $records)[$firstJuz] ?? HafalanOrder::defaultJuzOrder($firstJuz))[0];

        return ['surah' => $surah, 'ayah' => $ayah, 'source' => 'default', 'date' => null];
    }

    /**
     * Baris setoran lulus pada rentang tanggal (inklusif) -- sama dengan kolom baris di tab
     * Capaian Hafalan: baris yang tersimpan di setoran (bila diisi), selain itu dihitung dari
     * ayat. Mengulang ayat lama ikut dihitung; setoran belum lulus tidak.
     */
    public function passedLines(Collection $records, Carbon $from, Carbon $until): float
    {
        if ($until->lt($from)) {
            return 0.0;
        }

        $lines = $records
            ->filter(fn ($r) => $r->status === 'passed'
                && Carbon::parse($r->submitted_at)->betweenIncluded($from->copy()->startOfDay(), $until->copy()->endOfDay()))
            ->sum(fn ($r) => $r->baris !== null
                ? (float) $r->baris
                : ReportController::calculateLines((int) $r->surah_number, (int) $r->ayah_start, (int) $r->ayah_end, (int) ($this->surahs()->get((int) $r->surah_number)?->total_ayah ?? 0)));

        return round((float) $lines, 1);
    }

    /**
     * Target baris = pertemuan aktif kelas pada rentang x baris per pertemuan level murid
     * (Pengaturan Target Hafalan). 0 untuk Ummi / tanpa kelas.
     */
    public function targetLines(Student $student, Carbon $from, Carbon $to): int
    {
        $level = TargetRules::linesForLevel($student->tahfizh_level);
        $classRoom = $student->classRoom;
        if ($level === null || ! $classRoom || $to->lt($from)) {
            return 0;
        }

        return (int) ($level * app(AcademicCalendarService::class)->scheduledMeetings($classRoom, $from->copy()->startOfDay(), $to->copy()->startOfDay()));
    }

    /**
     * Posisi target guru (surah, ayat): jalur dari titik awal triwulan (setoran pertama) sampai
     * target, dan apakah semua ayat di jalur itu sudah lulus disetor pada $cutoff.
     *
     * @return array{position_reached: bool, start: array, path_start: ?array{0: int, 1: int}}
     */
    public function evaluate(Student $student, int $targetSurah, int $targetAyah, Carbon $termStart, Carbon $termEnd, Carbon $cutoff, ?Collection $records = null): array
    {
        $records ??= $this->records($student);
        $start = $this->startPoint($student, $records, $termStart, $termEnd);
        $coverageNow = $this->coverage($records, null, $cutoff);

        $pieces = $this->quran->piecesUntil(
            $start['surah'], $start['ayah'], $targetSurah, $targetAyah, $this->surahs(),
            $this->coverage($records, $termStart), $student->hafalan_direction, $this->juzOrders($student, $records)
        );

        return [
            'position_reached' => $pieces === null
                ? AyahCoverage::contains($coverageNow[$targetSurah] ?? [], $targetAyah)
                : $this->quran->piecesCovered($pieces, $coverageNow),
            'start' => $start,
            // Ayat pertama jalur target (setelah melewati ayat yang sudah dihafal), untuk tampilan "dari ... s.d. ...".
            'path_start' => $pieces ? [$pieces[0][0], $pieces[0][1]] : null,
        ];
    }

    /**
     * Rentang ayat target untuk tampilan: dari ayat pertama jalur target sampai ayat target,
     * mis. "21 - 40" (satu surah) atau "Al-Ma'arij 41 - Al-Jinn 12" (lintas surah).
     */
    public function targetRangeLabel(?array $evaluation, int $targetSurah, int $targetAyah): string
    {
        $pathStart = $evaluation['path_start'] ?? null;
        if ($pathStart === null) {
            return (string) $targetAyah;
        }

        [$surah, $ayah] = $pathStart;

        $name = fn (int $number) => $this->surahs()->get($number)?->name_latin ?? "Surah {$number}";

        return $surah === $targetSurah
            ? "{$ayah} - {$targetAyah}"
            : "{$name($surah)} {$ayah} - {$name($targetSurah)} {$targetAyah}";
    }

    /**
     * Rincian per bulan satu triwulan: target baris (pertemuan aktif x level) & capaian baris
     * (setoran lulus) per bulan dan kumulatif, target guru tiap bulan (surah & ayat), serta
     * rekap term. Tuntas bulan/term = capaian baris >= target baris.
     *
     * @param  Collection<int, HafalanTarget>  $targets  target di dalam triwulan
     * @param  array<string, array{start: Carbon, end: Carbon}>  $months
     * @return array{months: array<string, array>, target: ?HafalanTarget, evaluation: array, position: ?array, start: array}
     */
    public function termBreakdown(Student $student, Collection $targets, array $months, Carbon $cutoff, ?Collection $records = null): array
    {
        $records ??= $this->records($student);
        $termStart = reset($months)['start']->copy()->startOfDay();
        $termEnd = end($months)['end']->copy()->endOfDay();

        $result = ['months' => [], 'target' => null, 'evaluation' => null, 'position' => null, 'start' => $this->startPoint($student, $records, $termStart, $termEnd)];
        $cumTarget = 0;
        $prevAchieved = 0.0;

        foreach ($months as $monthKey => $range) {
            $target = $targets
                ->filter(fn ($t) => $t->surah && Carbon::parse($t->target_date)->format('Y-m') === $monthKey)
                ->sortBy(fn ($t) => Carbon::parse($t->target_date)->format('Y-m-d').sprintf('%010d', $t->id))
                ->last();
            $until = $range['end']->copy()->endOfDay()->min($cutoff);
            $cumAchieved = $until->lt($range['start']) ? $prevAchieved : $this->passedLines($records, $termStart, $until);
            $monthTarget = $this->targetLines($student, $range['start'], $range['end']);
            $monthAchieved = round(max(0, $cumAchieved - $prevAchieved), 1);
            $cumTarget += $monthTarget;

            $result['months'][$monthKey] = [
                'target' => $target,
                'target_lines' => $monthTarget,
                'achieved_lines' => $monthAchieved,
                'cumulative_target_lines' => $cumTarget,
                'cumulative_achieved_lines' => $cumAchieved,
                'reached' => $monthAchieved >= $monthTarget,
                'position' => $target ? $this->evaluate($student, (int) $target->surah->number, (int) $target->ayah, $termStart, $termEnd, $until, $records) : null,
            ];

            if ($target) {
                $result['target'] = $target;
            }
            $prevAchieved = $cumAchieved;
        }

        $achieved = $this->passedLines($records, $termStart, $cutoff->copy()->min($termEnd));
        $result['evaluation'] = [
            'target_lines' => $cumTarget,
            'achieved_lines' => $achieved,
            'reached' => $achieved >= $cumTarget,
            'progress' => $cumTarget > 0 ? (int) min(100, round($achieved / $cumTarget * 100)) : 100,
        ];
        if ($result['target']) {
            $result['position'] = $this->evaluate($student, (int) $result['target']->surah->number, (int) $result['target']->ayah, $termStart, $termEnd, $cutoff->copy()->min($termEnd), $records);
        }

        return $result;
    }

    // ── Ringkasan hafalan keseluruhan (method asli, dipertahankan) ──

    public function totalQuranAyahs(): int
    {
        $total = (int) Surah::query()->sum('total_ayah');

        return $total > 0 ? $total : 6236;
    }

    public function memorizedAyahCount(Student $student): int
    {
        $recordsBySurah = HafalanRecordSurah::query()
            ->whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $student->id))
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
                    'surahs.surah',
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
