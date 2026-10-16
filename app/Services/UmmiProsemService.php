<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TargetLock;
use App\Models\UmmiProsemRow;
use App\Support\AcademicYear;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Program Semester (Prosem) metode Ummi Kelas 10 (docs/prosem-ummi.md):
 * - plan(): urutan tatap muka (TM) semester -- tiap baris Prosem menyumbang TM sesuai sebaran pekannya;
 *   halaman peraga & buku baris dibagi rata ke TM-nya, hafalan (ziyadah) & drill (muraja'ah) berlaku
 *   di semua TM baris itu. Baris tanpa materi = TM pengulangan/evaluasi.
 * - meetingFor(): TM yang dijadwalkan untuk satu kelas pada satu tanggal (pekan Prosem + urutan hari
 *   pertemuan Ummi kelas itu di pekan tersebut).
 * - monthTarget() & syncTargets(): target Ummi bulanan otomatis (posisi Prosem di akhir bulan), disimpan
 *   sebagai HafalanTarget ber-auto_month; target yang diubah guru (auto_month kosong) tidak disentuh.
 * Pekan ke-n dalam bulan: pekan Senin-Minggu, pekan 1 memuat tanggal 1.
 */
class UmmiProsemService
{
    public const SEMESTER_MONTHS = [1 => [7, 8, 9, 10, 11, 12], 2 => [1, 2, 3, 4, 5, 6]];

    /** @var array<string, array> */
    private array $plans = [];

    public function __construct(private readonly AcademicCalendarService $calendar) {}

    /** [tahun ajaran, semester] yang memuat tanggal. */
    public static function semesterOf(Carbon $date): array
    {
        return [AcademicYear::forDate($date), $date->month >= 7 ? 1 : 2];
    }

    public static function weekOfMonth(Carbon $date): int
    {
        $first = $date->copy()->startOfMonth();

        return intdiv($date->day + $first->dayOfWeekIso - 2, 7) + 1;
    }

    /** Kalender tahun untuk bulan semester (Juli-Desember = tahun awal, Januari-Juni = tahun berikutnya). */
    public static function yearOfMonth(string $academicYear, int $month): int
    {
        return AcademicYear::startYear($academicYear) + ($month < 7 ? 1 : 0);
    }

    public function forgetPlans(): void
    {
        $this->plans = [];
    }

    /** @return Collection<int, UmmiProsemRow> */
    public function rows(string $academicYear, int $semester): Collection
    {
        return UmmiProsemRow::query()->with(['hafalanStart', 'hafalanEnd', 'drillStart', 'drillEnd'])
            ->where('academic_year', $academicYear)->where('semester', $semester)
            ->orderBy('position')->get();
    }

    /**
     * Urutan TM semester: [no, month, week, row, row_tm (1..tm), jilid, peraga [a,b]|null, buku [a,b]|null,
     * hafalan [Surah, Surah]|null, drill [Surah, Surah]|null].
     *
     * @return array<int, array<string, mixed>>
     */
    public function plan(string $academicYear, int $semester): array
    {
        $key = "{$academicYear}|{$semester}";
        if (isset($this->plans[$key])) {
            return $this->plans[$key];
        }

        $rows = $this->rows($academicYear, $semester);
        $used = [];
        $plan = [];
        foreach (self::SEMESTER_MONTHS[$semester] as $month) {
            foreach (range(1, 5) as $week) {
                foreach ($rows as $row) {
                    $count = (int) ($row->weeks[(string) $month][(string) $week] ?? $row->weeks[$month][$week] ?? 0);
                    for ($i = 0; $i < $count; $i++) {
                        $index = $used[$row->id] = ($used[$row->id] ?? 0) + 1;
                        $plan[] = [
                            'no' => count($plan) + 1,
                            'month' => $month,
                            'week' => $week,
                            'row' => $row,
                            'row_tm' => $index,
                            'jilid' => $row->jilid,
                            'peraga' => self::share($row->peraga_start, $row->peraga_end, $row->tm, $index),
                            'buku' => self::share($row->buku_start, $row->buku_end, $row->tm, $index),
                            'hafalan' => $row->hafalanStart ? [$row->hafalanStart, $row->hafalanEnd ?? $row->hafalanStart] : null,
                            'drill' => $row->drillStart ? [$row->drillStart, $row->drillEnd ?? $row->drillStart] : null,
                        ];
                    }
                }
            }
        }

        return $this->plans[$key] = $plan;
    }

    /**
     * Bagian halaman untuk TM ke-$index dari $tm: halaman dibagi rata, sisa ke TM awal. Rentang terbalik
     * (mis. 18-4) tidak dibagi: semua TM memakai rentang utuh. TM tanpa halaman baru = halaman terakhir
     * bagian sebelumnya (pengulangan).
     *
     * @return array{0: int, 1: int}|null
     */
    public static function share(?int $start, ?int $end, int $tm, int $index): ?array
    {
        if (! $start || ! $end || $tm < 1) {
            return null;
        }
        if ($end < $start) {
            return [$start, $end];
        }
        $pages = $end - $start + 1;
        $base = intdiv($pages, $tm);
        $extra = $pages % $tm;
        $from = $start;
        for ($i = 1; $i <= $index; $i++) {
            $size = $base + ($i <= $extra ? 1 : 0);
            if ($i === $index) {
                return $size > 0 ? [$from, $from + $size - 1] : [max($start, $from - 1), max($start, $from - 1)];
            }
            $from += $size;
        }

        return null;
    }

    /**
     * TM terjadwal untuk kelas pada tanggal: pekan Prosem tanggal itu, urutan hari pertemuan Ummi kelas di
     * pekan itu (dibatasi jumlah TM pekan itu).
     *
     * @return array{entry: ?array, week_count: int, nth: int, next: ?array}
     */
    public function meetingFor(ClassRoom $class, Carbon $date): array
    {
        [$year, $semester] = self::semesterOf($date);
        $plan = $this->plan($year, $semester);
        $week = self::weekOfMonth($date);
        $inWeek = array_values(array_filter($plan, fn ($e) => $e['month'] === $date->month && $e['week'] === $week));

        $nth = 0;
        $cursor = $date->copy()->startOfWeek();
        while ($cursor->lte($date)) {
            if ($cursor->month === $date->month && $this->calendar->isEffectiveDay($class, $cursor, true)) {
                $nth++;
            }
            $cursor->addDay();
        }

        $entry = $inWeek === [] ? null : $inWeek[min(max($nth, 1), count($inWeek)) - 1];
        $next = $entry ? null : collect($plan)->first(fn ($e) => self::slotDate($year, $e) > $date->copy()->startOfDay());

        return ['entry' => $entry, 'week_count' => count($inWeek), 'nth' => $nth, 'next' => $next];
    }

    /** Senin pekan Prosem sebuah TM (perkiraan tanggal). */
    public static function slotDate(string $academicYear, array $entry): Carbon
    {
        $first = Carbon::create(self::yearOfMonth($academicYear, $entry['month']), $entry['month'], 1);

        return $first->copy()->startOfWeek()->addWeeks($entry['week'] - 1)->max($first);
    }

    /**
     * Target Ummi bulanan dari Prosem: posisi TM terakhir yang dijadwalkan s.d. akhir bulan itu (Jilid,
     * halaman buku & peraga akhir, surah hafalan terakhir). Null bila belum ada TM bermateri.
     *
     * @return array{ummi_jilid: string, halaman_buku: string, halaman_peraga: ?string, surah_id: ?int, ayah: null}|null
     */
    public function monthTarget(string $academicYear, int $semester, int $month): ?array
    {
        $order = array_flip(self::SEMESTER_MONTHS[$semester]);
        if (! isset($order[$month])) {
            return null;
        }
        $upTo = array_values(array_filter($this->plan($academicYear, $semester), fn ($e) => $order[$e['month']] <= $order[$month]));
        $book = collect($upTo)->reverse()->first(fn ($e) => $e['jilid'] && $e['buku']);
        if (! $book) {
            return null;
        }
        $hafalan = collect($upTo)->reverse()->first(fn ($e) => $e['hafalan']);

        return [
            'ummi_jilid' => $book['jilid'],
            'halaman_buku' => (string) max($book['buku']),
            'halaman_peraga' => $book['peraga'] ? (string) max($book['peraga']) : null,
            'surah_id' => $hafalan ? $hafalan['hafalan'][1]->id : null,
            'ayah' => null,
        ];
    }

    /**
     * Buat/perbarui target Ummi bulanan otomatis dari Prosem untuk murid Kelas 10 (metode Ummi). Target
     * buatan/ubahan guru (auto_month kosong) & bulan terkunci tidak disentuh.
     *
     * @param  Collection<int, Student>|null  $students  null = semua murid aktif Kelas 10
     * @return int jumlah target dibuat/diubah
     */
    public function syncTargets(string $academicYear, int $semester, ?Collection $students = null): int
    {
        if ($this->plan($academicYear, $semester) === []) {
            return 0; // Prosem semester ini belum diisi: target yang ada tidak disentuh.
        }
        $students ??= Student::query()->with('classRoom.program')->where('status', 'active')->get();
        $students = $students->filter(fn (Student $s) => $s->usesUmmi() && ($s->classRoom?->isGradeTen() ?? false));
        $deadlines = app(TargetDeadlineService::class);
        $changed = 0;

        foreach (self::SEMESTER_MONTHS[$semester] as $month) {
            $values = $this->monthTarget($academicYear, $semester, $month);
            $start = Carbon::create(self::yearOfMonth($academicYear, $month), $month, 1);
            $monthKey = $start->format('Y-m');
            foreach ($students as $student) {
                if (TargetLock::blocksStudent($student, $start)) {
                    continue;
                }
                $existing = HafalanTarget::query()->where('student_id', $student->id)
                    ->whereBetween('target_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString().' 23:59:59'])
                    ->orderByDesc('id')->first();
                if ($existing && $existing->auto_month === null) {
                    continue; // target guru
                }
                if (! $values) {
                    if ($existing) {
                        $existing->delete();
                        $changed++;
                    }

                    continue;
                }
                $attributes = $values + [
                    'target_date' => $deadlines->forMonth($start)->toDateString(),
                    'auto_month' => $monthKey,
                    'notes' => 'Otomatis dari Prosem Ummi',
                ];
                if ($existing && collect($attributes)->every(fn ($v, $k) => (string) ($k === 'target_date' ? $existing->target_date?->toDateString() : $existing->{$k}) === (string) $v)) {
                    continue;
                }
                $attributes += ['status' => 'active', 'book_status' => 'active', 'surah_status' => $values['surah_id'] ? 'active' : null, 'completed_at' => null];
                $target = $existing
                    ? tap($existing)->update($attributes)
                    : HafalanTarget::create($attributes + ['student_id' => $student->id, 'teacher_id' => $student->teacher_id]);
                app(HafalanTargetAutoCompletionService::class)->refresh($target->fresh());
                $changed++;
            }
        }

        return $changed;
    }

    /** Label surah rentang hafalan/drill: "An-Nas – Al-'Asr". */
    public static function rangeLabel(?array $range): string
    {
        if (! $range) {
            return '-';
        }
        [$from, $to] = $range;

        return $from->id === $to->id ? $from->name_latin : "{$from->name_latin} – {$to->name_latin}";
    }

    /** Label halaman: "3-4" / "5". */
    public static function pagesLabel(?array $pages): string
    {
        if (! $pages) {
            return '-';
        }

        return $pages[0] === $pages[1] ? (string) $pages[0] : "{$pages[0]}-{$pages[1]}";
    }

    /** Nama surah untuk impor (dipakai SurahNameMatcher). */
    public static function surahIdsByNumber(): Collection
    {
        return Surah::query()->pluck('id', 'number');
    }
}
