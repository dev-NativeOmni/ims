<?php

namespace App\Services;

use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use App\Models\UmmiRecord;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Grafik capaian Ummi Kelas 10: dua posisi per murid dalam angka supaya bisa digambar.
 *
 * - Buku Ummi Dewasa: Jilid 1-3 x 40 halaman -> 1..120 (J2 h.5 = 45).
 * - Hafalan surah metode Ummi: urutan Juz 30 mundur (An-Nas -> An-Naba), lalu Juz 29, 28, ...
 *   maju dari awal juz (sama dengan HafalanOrder tanpa deteksi urutan) -> ayat ke-n dari awal.
 *
 * Target dari Target Bulanan (Ummi): Jilid + Halaman Buku, dan Surah + Ayat (ayat kosong =
 * sampai akhir surah). Tuntas dinilai terpisah untuk buku dan hafalan.
 */
class UmmiProgressService
{
    public const PAGES_PER_JILID = 40;

    public const JILID_COUNT = 3;

    /** @var array<int, array{surah: int, start: int, end: int, offset: int}>|null */
    private ?array $order = null;

    private ?Collection $surahs = null;

    /**
     * Posisi buku: "Jilid 2" + "15" / "12-15" / "Hal. 15" -> 55. Null bila tidak terbaca.
     */
    public static function pageValue(?string $jilid, ?string $halaman): ?int
    {
        if (! preg_match('/(\d+)/', (string) $jilid, $j) || ! preg_match_all('/\d+/', (string) $halaman, $h)) {
            return null;
        }

        $jilidNumber = min(self::JILID_COUNT, max(1, (int) $j[1]));
        $page = min(self::PAGES_PER_JILID, max(1, (int) max($h[0])));

        return ($jilidNumber - 1) * self::PAGES_PER_JILID + $page;
    }

    public static function pageLabel(?int $value): string
    {
        if (! $value) {
            return '-';
        }

        return 'J'.(intdiv($value - 1, self::PAGES_PER_JILID) + 1).' h.'.((($value - 1) % self::PAGES_PER_JILID) + 1);
    }

    /**
     * Posisi hafalan: ayat ke-n dalam urutan hafalan Ummi (An-Nas 1 = 1).
     */
    public function hafalanValue(int $surahNumber, int $ayah): ?int
    {
        foreach ($this->order() as $piece) {
            if ($piece['surah'] === $surahNumber && $ayah >= $piece['start'] && $ayah <= $piece['end']) {
                return $piece['offset'] + ($ayah - $piece['start'] + 1);
            }
        }

        return null;
    }

    public function hafalanLabel(?int $value): string
    {
        if (! $value) {
            return '-';
        }

        foreach ($this->order() as $piece) {
            if ($value <= $piece['offset'] + ($piece['end'] - $piece['start'] + 1)) {
                return ($this->surahs()->get($piece['surah'])?->name_latin ?? 'Surah '.$piece['surah']).' '.($piece['start'] + $value - $piece['offset'] - 1);
            }
        }

        return '-';
    }

    /**
     * Garis sumbu kanan: awal tiap surah sampai $max (nilai => nama surah).
     *
     * @return array<int, string>
     */
    public function hafalanTicks(int $max): array
    {
        $ticks = [];
        foreach ($this->order() as $piece) {
            $value = $piece['offset'] + 1;
            if ($value > $max) {
                break;
            }
            if ($piece['start'] === 1) {
                $ticks[$value] = $this->surahs()->get($piece['surah'])?->name_latin ?? 'Surah '.$piece['surah'];
            }
        }

        return $ticks;
    }

    /**
     * Batas atas sumbu hafalan: akhir juz yang memuat nilai terbesar (minimal Juz 30 penuh).
     */
    public function hafalanAxisMax(int $highest): int
    {
        $juzEnds = [];
        $running = 0;
        foreach (HafalanOrder::juzSequence(HafalanOrder::BACKWARD) as $juz) {
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                $running += $range['end'] - $range['start'] + 1;
            }
            $juzEnds[] = $running;
        }

        foreach ($juzEnds as $end) {
            if ($highest <= $end) {
                return $end;
            }
        }

        return end($juzEnds);
    }

    /**
     * Data grafik satu kelas untuk periode (bulanan atau term).
     *
     * @param  array<string, string>  $periodMonths  Y-m => nama bulan
     * @return array{rows: array, months: array<string, string>, book_done: int, book_total: int, hafalan_done: int, hafalan_total: int, hafalan_max: int, hafalan_ticks: array}
     */
    public function classChart(Collection $students, Carbon $start, Carbon $end, bool $isTerm, array $periodMonths): array
    {
        $studentIds = $students->pluck('id');
        $records = UmmiRecord::query()
            ->with('surahs.surah')
            ->whereIn('student_id', $studentIds)
            ->where('tanggal', '<=', $end->toDateString())
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->groupBy('student_id');
        $targets = HafalanTarget::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('ummi_jilid')
            ->whereBetween('target_date', [$start->toDateString(), $end->toDateString().' 23:59:59'])
            ->orderBy('target_date')
            ->orderBy('id')
            ->get()
            ->groupBy('student_id');

        $rows = [];
        $highest = 1;
        foreach ($students as $student) {
            $studentRecords = $records->get($student->id, collect());
            $row = $this->studentRow($student, $studentRecords, $targets->get($student->id, collect())->last(), $start, $end, $isTerm, $periodMonths);
            $highest = max($highest, $row['hafalan'] ?? 0, $row['target_hafalan'] ?? 0);
            $rows[] = $row;
        }

        $hafalanMax = $this->hafalanAxisMax($highest);
        $withBookTarget = collect($rows)->whereNotNull('target_book');
        $withHafalanTarget = collect($rows)->whereNotNull('target_hafalan');

        return [
            'rows' => $rows,
            'months' => $periodMonths,
            'book_done' => $withBookTarget->where('book_reached', true)->count(),
            'book_total' => $withBookTarget->count(),
            'hafalan_done' => $withHafalanTarget->where('hafalan_reached', true)->count(),
            'hafalan_total' => $withHafalanTarget->count(),
            'hafalan_max' => $hafalanMax,
            'hafalan_ticks' => $this->hafalanTicks($hafalanMax),
        ];
    }

    private function studentRow(Student $student, Collection $records, ?HafalanTarget $target, Carbon $start, Carbon $end, bool $isTerm, array $periodMonths): array
    {
        $bookAt = fn (Carbon $until) => $this->bestBook($records, $until);
        $hafalanAt = fn (Carbon $until) => $this->bestHafalan($records, $until);

        $book = $bookAt($end);
        $hafalan = $hafalanAt($end);

        $targetBook = $target ? self::pageValue($target->ummi_jilid, $target->halaman_buku) : null;
        $targetHafalan = $target?->surah
            ? $this->hafalanValue((int) $target->surah->number, (int) ($target->ayah ?: $target->surah->total_ayah))
            : null;

        $inPeriod = $records->filter(fn ($r) => Carbon::parse($r->tanggal)->betweenIncluded($start->copy()->startOfDay(), $end->copy()->endOfDay()));
        $lastSurah = $inPeriod->flatMap(fn ($r) => $r->surahs)->filter(fn ($s) => $s->surah)->last();

        $row = [
            'student' => $student,
            'book' => $book,
            'hafalan' => $hafalan,
            'book_label' => self::pageLabel($book),
            'hafalan_label' => $this->hafalanLabel($hafalan),
            'last_surah' => $lastSurah?->surah?->name_latin,
            'surahs_period' => $inPeriod->flatMap(fn ($r) => $r->surahs)->filter(fn ($s) => $s->surah)
                ->map(fn ($s) => $s->surah->name_latin.($s->hafalan_ayah ? ' ('.$s->hafalan_ayah.')' : ''))->unique()->values()->all(),
            'target_book' => $targetBook,
            'target_hafalan' => $targetHafalan,
            'target_book_label' => self::pageLabel($targetBook),
            'target_hafalan_label' => $this->hafalanLabel($targetHafalan),
            'book_reached' => $targetBook !== null && (int) $book >= $targetBook,
            'hafalan_reached' => $targetHafalan !== null && (int) $hafalan >= $targetHafalan,
        ];

        // Term: posisi awal term + tambahan tiap bulan (ditumpuk), puncak = posisi akhir term.
        if ($isTerm) {
            $beforeStart = $start->copy()->subDay()->endOfDay();
            $prevBook = (int) $bookAt($beforeStart);
            $prevHafalan = (int) $hafalanAt($beforeStart);
            $row['book_base'] = $prevBook;
            $row['hafalan_base'] = $prevHafalan;
            $row['book_months'] = [];
            $row['hafalan_months'] = [];
            foreach (array_keys($periodMonths) as $monthKey) {
                $monthEnd = Carbon::parse($monthKey.'-01')->endOfMonth()->min($end);
                $b = max($prevBook, (int) $bookAt($monthEnd));
                $h = max($prevHafalan, (int) $hafalanAt($monthEnd));
                $row['book_months'][$monthKey] = $b - $prevBook;
                $row['hafalan_months'][$monthKey] = $h - $prevHafalan;
                [$prevBook, $prevHafalan] = [$b, $h];
            }
        }

        return $row;
    }

    /** Posisi buku terjauh sampai $until (catatan tanpa jilid/halaman diabaikan). */
    private function bestBook(Collection $records, Carbon $until): ?int
    {
        return $records
            ->filter(fn ($r) => Carbon::parse($r->tanggal)->lte($until))
            ->map(fn ($r) => self::pageValue($r->ummi_jilid, $r->ummi_halaman))
            ->filter()
            ->max();
    }

    /** Posisi hafalan surah terjauh sampai $until. */
    private function bestHafalan(Collection $records, Carbon $until): ?int
    {
        return $records
            ->filter(fn ($r) => Carbon::parse($r->tanggal)->lte($until))
            ->flatMap(fn ($r) => $r->surahs)
            ->map(function ($entry) {
                if (! $entry->surah || ! preg_match_all('/\d+/', (string) $entry->hafalan_ayah, $m)) {
                    return $entry->surah ? $this->hafalanValue((int) $entry->surah->number, (int) $entry->surah->total_ayah) : null;
                }

                return $this->hafalanValue((int) $entry->surah->number, (int) max($m[0]));
            })
            ->filter()
            ->max();
    }

    /**
     * Urutan hafalan Ummi: Juz 30 mundur (An-Nas dulu), juz berikutnya maju dari awal juz.
     */
    private function order(): array
    {
        if ($this->order !== null) {
            return $this->order;
        }

        $order = [];
        $offset = 0;
        foreach (HafalanOrder::juzSequence(HafalanOrder::BACKWARD) as $juz) {
            foreach (HafalanOrder::juzPieces($juz, HafalanOrder::defaultJuzOrder($juz)) as [$surah, $from, $to]) {
                $order[] = ['surah' => $surah, 'start' => $from, 'end' => $to, 'offset' => $offset];
                $offset += $to - $from + 1;
            }
        }

        return $this->order = $order;
    }

    private function surahs(): Collection
    {
        return $this->surahs ??= Surah::query()->get(['number', 'name_latin', 'total_ayah'])->keyBy('number');
    }
}
