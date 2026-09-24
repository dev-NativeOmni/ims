<?php

namespace App\Services;

use App\Models\CalendarDay;
use App\Models\ClassRoom;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Satu-satunya sumber aturan hari efektif sekolah (dipakai lewat app(SchoolCalendar::class),
 * terdaftar sebagai scoped singleton supaya cache per request/tes tidak bocor).
 *
 * Tahfizh efektif = hari ada di jadwal kelas (tahfizh_days) DAN bukan libur Tahfizh
 *                   (global atau khusus kelas itu).
 * Adab efektif    = Selasa-Jumat DAN bukan libur Adab global.
 *
 * Tahun yang belum pernah diatur admin memakai DEFAULT_TOTAL_HOLIDAYS sebagai Libur Total.
 */
class SchoolCalendar
{
    /** Hari kuisioner Adab: Selasa-Jumat (ISO). */
    public const ADAB_DAYS = [2, 3, 4, 5];

    /** Libur Total bawaan untuk tahun yang belum diatur ('m-d'). */
    private const DEFAULT_TOTAL_HOLIDAYS = ['01-01', '05-01', '06-01', '08-17', '12-25'];

    /** @var array<int, array<string, array{tahfizh_off: bool, adab_off: bool}>> */
    private array $globalCache = [];

    /** @var array<int, array<string, array<int, int>>> */
    private array $classCache = [];

    /** @var array<string, array<string, true>> */
    private array $adabDatesCache = [];

    public function __construct()
    {
        // Instance baru = request/tes baru: jangan pakai skor adab dari kalender sebelumnya.
        Setting::flushCalendarCaches();
    }

    /**
     * Status libur untuk semua kelas: 'Y-m-d' => [tahfizh_off, adab_off].
     *
     * @return array<string, array{tahfizh_off: bool, adab_off: bool}>
     */
    public function globalDays(int $year): array
    {
        if (isset($this->globalCache[$year])) {
            return $this->globalCache[$year];
        }

        $days = CalendarDay::query()
            ->whereNull('class_room_id')
            ->whereYear('date', $year)
            ->get()
            ->mapWithKeys(fn (CalendarDay $day) => [$day->date->toDateString() => [
                'tahfizh_off' => $day->tahfizh_off,
                'adab_off' => $day->adab_off,
            ]])
            ->all();

        if ($days === [] && ! $this->isYearConfigured($year)) {
            foreach (self::DEFAULT_TOTAL_HOLIDAYS as $monthDay) {
                $days["{$year}-{$monthDay}"] = ['tahfizh_off' => true, 'adab_off' => true];
            }
        }

        return $this->globalCache[$year] = $days;
    }

    /**
     * Libur Tahfizh khusus kelas (Libur Sebagian): 'Y-m-d' => [class_room_id, ...].
     *
     * @return array<string, array<int, int>>
     */
    public function classDays(int $year): array
    {
        if (isset($this->classCache[$year])) {
            return $this->classCache[$year];
        }

        $days = [];
        CalendarDay::query()
            ->whereNotNull('class_room_id')
            ->whereYear('date', $year)
            ->where('tahfizh_off', true)
            ->orderBy('class_room_id')
            ->get(['date', 'class_room_id'])
            ->each(function (CalendarDay $day) use (&$days) {
                $days[$day->date->toDateString()][] = (int) $day->class_room_id;
            });

        return $this->classCache[$year] = $days;
    }

    /**
     * Tanggal Libur Total (Tahfizh & Adab) setahun -- bentuk lama Setting::getNationalHolidays().
     *
     * @return array<int, string>
     */
    public function totalHolidays(int $year): array
    {
        return array_keys(array_filter(
            $this->globalDays($year),
            fn (array $day) => $day['tahfizh_off'] && $day['adab_off']
        ));
    }

    public function isTahfizhHoliday(?ClassRoom $classRoom, CarbonInterface $date): bool
    {
        $dateString = $date->toDateString();

        if ($this->globalDays($date->year)[$dateString]['tahfizh_off'] ?? false) {
            return true;
        }

        return $classRoom !== null
            && in_array($classRoom->id, $this->classDays($date->year)[$dateString] ?? [], true);
    }

    /**
     * Hari pertemuan Tahfizh kelas pada pekan yang memuat tanggal ini (ISO 1-7).
     *
     * @return array<int, int>
     */
    public function classMeetingDays(ClassRoom $classRoom, CarbonInterface $date): array
    {
        return $classRoom->tahfizh_days;
    }

    public function isTahfizhEffectiveDay(ClassRoom $classRoom, CarbonInterface $date, array $onlyDays = []): bool
    {
        $allowedDays = $this->classMeetingDays($classRoom, $date);
        if ($onlyDays !== []) {
            $allowedDays = array_intersect($allowedDays, $onlyDays);
        }

        return in_array($date->dayOfWeekIso, $allowedDays, true)
            && ! $this->isTahfizhHoliday($classRoom, $date);
    }

    /**
     * Hari efektif Adab dalam sebulan ['Y-m-d' => true]; bulan berjalan dihitung sampai hari ini.
     *
     * @return array<string, true>
     */
    public function adabEffectiveDates(int $year, int $month, ?string $untilDate = null): array
    {
        $key = "{$year}-{$month}-".($untilDate ?? 'auto');
        if (isset($this->adabDatesCache[$key])) {
            return $this->adabDatesCache[$key];
        }

        $cursor = Carbon::create($year, $month, 1)->startOfDay();
        $end = match (true) {
            $untilDate !== null => Carbon::parse($untilDate)->endOfDay(),
            $cursor->isSameMonth(now()) => now()->endOfDay(),
            default => $cursor->copy()->endOfMonth(),
        };

        $dates = [];
        while ($cursor->lte($end) && $cursor->month === $month) {
            if ($this->isAdabEffectiveDay($cursor)) {
                $dates[$cursor->toDateString()] = true;
            }
            $cursor->addDay();
        }

        return $this->adabDatesCache[$key] = $dates;
    }

    public function isAdabEffectiveDay(CarbonInterface $date): bool
    {
        return in_array($date->dayOfWeekIso, self::ADAB_DAYS, true)
            && ! ($this->globalDays($date->year)[$date->toDateString()]['adab_off'] ?? false);
    }

    /**
     * Simpan status seluruh tanggal satu bulan (menggantikan isi bulan itu).
     *
     * @param  array<string, array{tahfizh_off: bool, adab_off: bool}>  $globalDays
     * @param  array<string, array<int, int>>  $classDays
     */
    public function saveMonth(int $year, int $month, array $globalDays, array $classDays, ?int $userId = null): void
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $inMonth = fn (string $date) => str_starts_with($date, $monthStart->format('Y-m-'));

        // Tahun pertama kali diatur: bekukan libur bawaan bulan-bulan lain supaya tidak hilang.
        if (! $this->isYearConfigured($year)) {
            foreach ($this->globalDays($year) as $date => $day) {
                if (! $inMonth($date)) {
                    CalendarDay::updateOrCreate(
                        ['date' => $date, 'class_room_id' => null],
                        ['tahfizh_off' => $day['tahfizh_off'], 'adab_off' => $day['adab_off'], 'updated_by' => $userId]
                    );
                }
            }
            Setting::set("calendar_configured_{$year}", '1');
        }

        CalendarDay::query()->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])->delete();

        foreach ($globalDays as $date => $day) {
            if ($inMonth($date) && ($day['tahfizh_off'] || $day['adab_off'])) {
                CalendarDay::create([
                    'date' => $date, 'class_room_id' => null,
                    'tahfizh_off' => (bool) $day['tahfizh_off'], 'adab_off' => (bool) $day['adab_off'],
                    'updated_by' => $userId,
                ]);
            }
        }

        $validClassIds = ClassRoom::query()->pluck('id')->all();
        foreach ($classDays as $date => $classIds) {
            if (! $inMonth($date)) {
                continue;
            }
            foreach (array_unique(array_map('intval', $classIds)) as $classId) {
                if (in_array($classId, $validClassIds, true)) {
                    CalendarDay::create([
                        'date' => $date, 'class_room_id' => $classId,
                        'tahfizh_off' => true, 'adab_off' => false, 'updated_by' => $userId,
                    ]);
                }
            }
        }

        $this->flush();
    }

    public function flush(): void
    {
        $this->globalCache = [];
        $this->classCache = [];
        $this->adabDatesCache = [];
        Setting::flushCalendarCaches();
    }

    private function isYearConfigured(int $year): bool
    {
        return Setting::get("calendar_configured_{$year}") === '1';
    }
}
