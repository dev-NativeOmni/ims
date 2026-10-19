<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TargetLock;
use App\Models\UmmiProsemOverride;
use App\Models\UmmiProsemRow;
use App\Models\UmmiRecord;
use App\Support\AcademicYear;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Program Semester (Prosem) metode Ummi Kelas 10 (docs/prosem-ummi.md), per kelas & halaqoh (musyrif).
 * Cakupan dicari dari yang paling khusus: kelas+musyrif, lalu kelas (semua halaqoh), lalu umum.
 * - plan(): urutan tatap muka (TM) semester -- tiap baris Prosem menyumbang TM sesuai sebaran pekannya;
 *   halaman peraga & buku baris dibagi rata ke TM-nya, hafalan (ziyadah) & drill (muraja'ah) berlaku
 *   di semua TM baris itu. Baris tanpa materi = TM drill/ujian.
 * - meetingFor(): TM yang dijadwalkan untuk kelas & halaqoh pada satu tanggal (pekan Prosem + urutan hari
 *   pertemuan Ummi kelas itu di pekan tersebut; kelas pekanan = gabungan TM sepekan).
 * - monthTarget() & syncTargets(): target Ummi bulanan otomatis (posisi Prosem di akhir bulan), disimpan
 *   sebagai HafalanTarget ber-auto_month; target yang diubah guru (auto_month kosong) tidak disentuh.
 * Pekan ke-n dalam bulan: pekan Senin-Minggu, pekan 1 memuat tanggal 1.
 */
class UmmiProsemService
{
    public const SEMESTER_MONTHS = [1 => [7, 8, 9, 10, 11, 12], 2 => [1, 2, 3, 4, 5, 6]];

    /** @var array<string, array> */
    private array $plans = [];

    /** @var array<string, Collection> */
    private array $scopes = [];

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
        $this->scopes = [];
    }

    /**
     * Baris Prosem satu cakupan persis (null = umum).
     *
     * @return Collection<int, UmmiProsemRow>
     */
    public function rows(string $academicYear, int $semester, ?int $classId = null, ?int $teacherId = null): Collection
    {
        return UmmiProsemRow::query()->with(['hafalanStart', 'hafalanEnd', 'drillStart', 'drillEnd'])
            ->where('academic_year', $academicYear)->where('semester', $semester)
            ->where('class_room_id', $classId)->where('teacher_id', $teacherId)
            ->orderBy('position')->get();
    }

    /**
     * Cakupan yang punya Prosem: [[class_room_id|null, teacher_id|null], ...].
     *
     * @return Collection<int, array{0: ?int, 1: ?int}>
     */
    public function scopes(string $academicYear, int $semester): Collection
    {
        return $this->scopes["{$academicYear}|{$semester}"] ??= UmmiProsemRow::query()
            ->where('academic_year', $academicYear)->where('semester', $semester)
            ->select('class_room_id', 'teacher_id')->distinct()->get()
            ->map(fn ($r) => [$r->class_room_id !== null ? (int) $r->class_room_id : null, $r->teacher_id !== null ? (int) $r->teacher_id : null])
            ->values();
    }

    /**
     * Cakupan Prosem yang berlaku untuk kelas & musyrif, atau null bila tidak ada.
     *
     * @return array{0: ?int, 1: ?int}|null
     */
    public function resolveScope(string $academicYear, int $semester, ?int $classId, ?int $teacherId): ?array
    {
        $scopes = $this->scopes($academicYear, $semester);
        $candidates = array_filter([[$classId, $teacherId], [$classId, null], [null, null]], fn ($s) => $s[0] !== null || $s[1] === null);
        foreach ($candidates as $scope) {
            if ($scopes->contains(fn ($s) => $s === $scope)) {
                return $scope;
            }
        }

        return null;
    }

    /**
     * Urutan TM semester untuk kelas & musyrif (cakupan Prosem yang berlaku).
     *
     * @return array<int, array<string, mixed>>
     */
    public function plan(string $academicYear, int $semester, ?int $classId = null, ?int $teacherId = null): array
    {
        $scope = $this->resolveScope($academicYear, $semester, $classId, $teacherId);
        if ($scope === null) {
            return [];
        }
        $key = "{$academicYear}|{$semester}|".($scope[0] ?? '-').'|'.($scope[1] ?? '-');

        return $this->plans[$key] ??= $this->planFromRows($this->rows($academicYear, $semester, $scope[0], $scope[1]), $semester);
    }

    /**
     * Urutan TM dari baris Prosem: [no, month, week, row, row_tm (1..tm), jilid, peraga [a,b]|null,
     * buku [a,b]|null, hafalan [Surah, Surah]|null, drill [Surah, Surah]|null].
     *
     * @param  Collection<int, UmmiProsemRow>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function planFromRows(Collection $rows, int $semester): array
    {
        $used = [];
        $plan = [];
        $peraga = self::peragaRanges($rows);
        foreach (self::SEMESTER_MONTHS[$semester] as $month) {
            foreach (range(1, 5) as $week) {
                foreach ($rows as $row) {
                    $count = (int) ($row->weeks[(string) $month][(string) $week] ?? 0);
                    for ($i = 0; $i < $count; $i++) {
                        $index = $used[$row->id] = ($used[$row->id] ?? 0) + 1;
                        $plan[] = [
                            'no' => count($plan) + 1,
                            'month' => $month,
                            'week' => $week,
                            'row' => $row,
                            'row_tm' => $index,
                            'jilid' => $row->jilid,
                            // Peraga sebagai halaman berurutan 1-40 dalam jilid (Peraga ganjil 1-20, genap 21-40).
                            'peraga' => isset($peraga[$row->id]) ? self::share($peraga[$row->id][0], $peraga[$row->id][1], $row->tm, $index) : null,
                            'buku' => self::share($row->buku_start, $row->buku_end, $row->tm, $index),
                            'hafalan' => $row->hafalanStart ? [$row->hafalanStart, $row->hafalanEnd ?? $row->hafalanStart] : null,
                            'drill' => $row->drillStart ? [$row->drillStart, $row->drillEnd ?? $row->drillStart] : null,
                        ];
                    }
                }
            }
        }

        return $plan;
    }

    /**
     * Halaman peraga tiap baris sebagai urutan 1-40 dalam jilid. Satu jilid memakai dua peraga 20 halaman
     * (Jilid 1 = Peraga 1 & 2, Jilid 2 = Peraga 3 & 4, ...): "18-4" = Peraga pertama hlm 18-20 lalu Peraga
     * kedua hlm 1-4 (= 18-24); baris berikutnya yang mulai dari halaman lebih kecil = sudah di peraga kedua.
     *
     * @param  Collection<int, UmmiProsemRow>  $rows
     * @return array<int, array{0: int, 1: int}> row id => [awal, akhir]
     */
    public static function peragaRanges(Collection $rows): array
    {
        $ranges = [];
        $state = []; // jilid => [bagian peraga (1|2), halaman akhir terakhir]
        foreach ($rows->sortBy('position') as $row) {
            if (! $row->jilid || ! $row->peraga_start || ! $row->peraga_end) {
                continue;
            }
            [$part, $last] = $state[$row->jilid] ?? [1, 0];
            if ($row->peraga_start < $last) {
                $part++; // penomoran mulai lagi = peraga kedua
            }
            $start = ($part - 1) * 20 + $row->peraga_start;
            if ($row->peraga_end < $row->peraga_start) {
                $part++; // melewati akhir peraga pertama
            }
            $end = ($part - 1) * 20 + $row->peraga_end;
            $ranges[$row->id] = [min($start, 40), min(max($end, $start), 40)];
            $state[$row->jilid] = [$part, $row->peraga_end];
        }

        return $ranges;
    }

    /**
     * Label halaman peraga dari urutan 1-40 dalam jilid: "Peraga 1 hlm 18-20 · Peraga 2 hlm 1-4".
     */
    public static function peragaLabel(?string $jilid, ?array $pages): string
    {
        if (! $pages) {
            return '-';
        }
        $base = preg_match('/(\d+)/', (string) $jilid, $m) ? ((int) $m[1] - 1) * 2 : null;
        $parts = [];
        foreach ([[1, 20], [21, 40]] as $i => [$from, $to]) {
            $a = max($pages[0], $from);
            $b = min($pages[1], $to);
            if ($a > $b) {
                continue;
            }
            $label = $base === null ? 'Peraga' : 'Peraga '.($base + $i + 1);
            $a -= $i * 20;
            $b -= $i * 20;
            $parts[] = $label.' hlm '.($a === $b ? $a : "{$a}-{$b}");
        }

        return implode(' · ', $parts);
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
     * TM terjadwal untuk kelas & halaqoh pada tanggal: pekan Prosem tanggal itu, urutan hari pertemuan Ummi
     * kelas itu di pekan tersebut (dibatasi jumlah TM pekan itu). Kelas pekanan (sekali seminggu): semua TM
     * pekan itu digabung jadi satu target.
     *
     * @return array{entry: ?array, week_count: int, nth: int, next: ?array, weekly: bool}
     */
    public function meetingFor(ClassRoom $class, Carbon $date, ?int $teacherId = null): array
    {
        [$year, $semester] = self::semesterOf($date);
        $plan = $this->plan($year, $semester, $class->id, $teacherId);
        $week = self::weekOfMonth($date);
        $inWeek = array_values(array_filter($plan, fn ($e) => $e['month'] === $date->month && $e['week'] === $week));
        $weekly = $class->loadMissing('program')->program?->meeting_frequency === 'seminggu sekali';

        $nth = 0;
        $cursor = $date->copy()->startOfWeek();
        while ($cursor->lte($date)) {
            if ($cursor->month === $date->month && $this->calendar->isEffectiveDay($class, $cursor, true)) {
                $nth++;
            }
            $cursor->addDay();
        }

        $entry = match (true) {
            $inWeek === [] => null,
            $weekly => self::merge($inWeek),
            default => $inWeek[min(max($nth, 1), count($inWeek)) - 1],
        };
        $next = $entry ? null : collect($plan)->first(fn ($e) => self::slotDate($year, $e) > $date->copy()->startOfDay());

        return ['entry' => $entry, 'week_count' => count($inWeek), 'nth' => $nth, 'next' => $next, 'weekly' => $weekly];
    }

    /**
     * Target TM berikutnya seorang murid menurut jurnal Ummi-nya (tidak mengikuti kalender: target tetap
     * sampai terpenuhi). TM bermateri terpenuhi bila posisi buku jurnal (Jilid + halaman) sudah sampai
     * halaman akhir TM itu; TM Drill/Ujian (tanpa halaman) terpenuhi setelah sejumlah sesi jurnal tercatat
     * sesudah materi terakhir yang terpenuhi. "Samakan" musyrif (UmmiProsemOverride) memajukan target.
     *
     * @return array{plan: array, done: int, next: ?array, position: ?int, override: ?int, last_session: ?string}
     */
    public function studentProgress(Student $student, string $academicYear, int $semester): array
    {
        $plan = $this->plan($academicYear, $semester, $student->class_room_id, $student->teacher_id);
        $records = UmmiRecord::query()->where('student_id', $student->id)->orderBy('tanggal')->orderBy('id')
            ->get(['tanggal', 'ummi_jilid', 'ummi_halaman']);

        // Posisi tertinggi berjalan & tanggal pertama tiap posisi tercapai.
        $timeline = [];
        $max = 0;
        foreach ($records as $record) {
            $value = UmmiProgressService::pageValue($record->ummi_jilid, $record->ummi_halaman) ?? 0;
            if ($value > $max) {
                $max = $value;
                $timeline[] = [$value, Carbon::parse($record->tanggal)->toDateString()];
            }
        }
        $reachedOn = function (int $required) use ($timeline): ?string {
            foreach ($timeline as [$value, $date]) {
                if ($value >= $required) {
                    return $date;
                }
            }

            return null;
        };

        $done = 0;
        $anchor = null;
        foreach ($plan as $i => $entry) {
            if ($entry['jilid'] && $entry['buku']) {
                $date = $reachedOn((int) UmmiProgressService::pageValue($entry['jilid'], (string) max($entry['buku'])));
                if ($date !== null) {
                    $done = $i + 1;
                    $anchor = $date;
                }
            }
        }
        // TM Drill/Ujian sesudah materi terakhir yang terpenuhi: dihitung dari jumlah sesi jurnal sesudahnya.
        // Kelas pekanan: satu sesi = semua TM pekan Prosem itu.
        $weekly = $student->classRoom?->loadMissing('program')->program?->meeting_frequency === 'seminggu sekali';
        $isMaterial = fn (array $e) => $e['jilid'] && $e['buku'];
        $sessions = $records->filter(fn ($r) => $anchor === null || Carbon::parse($r->tanggal)->toDateString() > $anchor)->count();
        while ($sessions > 0 && isset($plan[$done]) && ! $isMaterial($plan[$done])) {
            $slot = [$plan[$done]['month'], $plan[$done]['week']];
            do {
                $done++;
            } while ($weekly && isset($plan[$done]) && ! $isMaterial($plan[$done]) && [$plan[$done]['month'], $plan[$done]['week']] === $slot);
            $sessions--;
        }

        $override = UmmiProsemOverride::query()->where('student_id', $student->id)
            ->where('academic_year', $academicYear)->where('semester', $semester)->value('tm_no');
        $nextIndex = max($done, $override ? $override - 1 : 0);

        return [
            'plan' => $plan,
            'done' => $done,
            'next' => $plan[$nextIndex] ?? null,
            'position' => $max ?: null,
            'override' => $override ? (int) $override : null,
            'last_session' => $records->last()?->tanggal ? Carbon::parse($records->last()->tanggal)->toDateString() : null,
        ];
    }

    /**
     * Target satu sesi mulai TM $entry: kelas pekanan = TM itu + TM sesudahnya di pekan Prosem yang sama.
     */
    public static function sessionTarget(array $plan, array $entry, bool $weekly): array
    {
        if (! $weekly) {
            return $entry;
        }
        $same = array_values(array_filter($plan, fn ($e) => $e['no'] >= $entry['no'] && $e['month'] === $entry['month'] && $e['week'] === $entry['week']));

        return count($same) > 1 ? self::merge($same) : $entry;
    }

    /** Gabungan beberapa TM (satu pekan kelas pekanan): halaman dari awal TM pertama s.d. akhir TM terakhir. */
    public static function merge(array $entries): array
    {
        $first = $entries[0];
        $last = end($entries);
        $pages = function (string $key) use ($entries) {
            $ranges = array_values(array_filter(array_column($entries, $key)));

            return $ranges === [] ? null : [$ranges[0][0], end($ranges)[1]];
        };

        return [
            'no' => $first['no'],
            'no_end' => $last['no'],
            'month' => $first['month'],
            'week' => $first['week'],
            'jilid' => $last['jilid'] ?? $first['jilid'],
            'peraga' => $pages('peraga'),
            'buku' => $pages('buku'),
            'hafalan' => collect($entries)->pluck('hafalan')->filter()->last(),
            'drill' => collect($entries)->pluck('drill')->filter()->first(),
            'row' => $last['row'],
            'row_tm' => $last['row_tm'],
        ];
    }

    /** Senin pekan Prosem sebuah TM (perkiraan tanggal). */
    public static function slotDate(string $academicYear, array $entry): Carbon
    {
        $first = Carbon::create(self::yearOfMonth($academicYear, $entry['month']), $entry['month'], 1);

        return $first->copy()->startOfWeek()->addWeeks($entry['week'] - 1)->max($first);
    }

    /**
     * Target Ummi bulanan dari Prosem kelas & halaqoh: posisi TM terakhir yang dijadwalkan s.d. akhir bulan
     * (Jilid, halaman buku & peraga akhir, surah hafalan terjauh). Null bila belum ada TM bermateri.
     *
     * @return array{ummi_jilid: string, halaman_buku: string, halaman_peraga: ?string, surah_id: ?int, ayah: null}|null
     */
    public function monthTarget(string $academicYear, int $semester, int $month, ?int $classId = null, ?int $teacherId = null): ?array
    {
        $order = array_flip(self::SEMESTER_MONTHS[$semester]);
        if (! isset($order[$month])) {
            return null;
        }
        $upTo = array_values(array_filter($this->plan($academicYear, $semester, $classId, $teacherId), fn ($e) => $order[$e['month']] <= $order[$month]));
        $book = collect($upTo)->reverse()->first(fn ($e) => $e['jilid'] && $e['buku']);
        if (! $book) {
            return null;
        }
        $hafalan = collect($upTo)->reverse()->first(fn ($e) => $e['hafalan']);

        return [
            'ummi_jilid' => $book['jilid'],
            'halaman_buku' => (string) max($book['buku']),
            'halaman_peraga' => $book['peraga'] ? self::peragaLabel($book['jilid'], [max($book['peraga']), max($book['peraga'])]) : null,
            'surah_id' => $hafalan ? self::furthest($hafalan['hafalan'])->id : null,
            'ayah' => null,
        ];
    }

    /**
     * Buat/perbarui target Ummi bulanan otomatis dari Prosem untuk murid Kelas 10 (metode Ummi) yang
     * punya Prosem untuk kelas & halaqohnya. Target buatan/ubahan guru (auto_month kosong) & bulan
     * terkunci tidak disentuh.
     *
     * @param  Collection<int, Student>|null  $students  null = semua murid aktif
     * @return int jumlah target dibuat/diubah
     */
    public function syncTargets(string $academicYear, int $semester, ?Collection $students = null): int
    {
        if ($this->scopes($academicYear, $semester)->isEmpty()) {
            return 0; // Prosem semester ini belum diisi: target yang ada tidak disentuh.
        }
        $students ??= Student::query()->with('classRoom.program')->where('status', 'active')->get();
        $students = $students->filter(fn (Student $s) => $s->usesUmmi() && ($s->classRoom?->isGradeTen() ?? false)
            && $this->resolveScope($academicYear, $semester, $s->class_room_id, $s->teacher_id) !== null);
        $deadlines = app(TargetDeadlineService::class);
        $changed = 0;

        foreach (self::SEMESTER_MONTHS[$semester] as $month) {
            $start = Carbon::create(self::yearOfMonth($academicYear, $month), $month, 1);
            $monthKey = $start->format('Y-m');
            foreach ($students as $student) {
                if (TargetLock::blocksStudent($student, $start)) {
                    continue;
                }
                $values = $this->monthTarget($academicYear, $semester, $month, $student->class_room_id, $student->teacher_id);
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

    /**
     * Surah terjauh rentang hafalan menurut urutan Ummi (Juz 30 mundur dari An-Nas): nomor terkecil bila
     * keduanya di Juz 30 ("Al 'Ala - At Tin" = Al-A'la); selain itu surah akhir tulisan.
     */
    public static function furthest(array $range): Surah
    {
        [$from, $to] = $range;
        if ((int) $from->number >= 78 && (int) $to->number >= 78) {
            return (int) $from->number < (int) $to->number ? $from : $to;
        }

        return $to;
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
}
