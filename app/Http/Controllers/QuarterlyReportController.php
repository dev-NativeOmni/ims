<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Services\AcademicCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class QuarterlyReportController extends Controller
{
    public static function mapScoreToGrade($score): string
    {
        if (empty($score)) {
            return 'A';
        }
        if (is_string($score) && ! is_numeric($score)) {
            return $score;
        }
        $scoreVal = (float) $score;
        if ($scoreVal >= 90) {
            return 'A+';
        }
        if ($scoreVal >= 80) {
            return 'A';
        }
        if ($scoreVal >= 70) {
            return 'B+';
        }
        if ($scoreVal >= 60) {
            return 'B';
        }
        if ($scoreVal >= 50) {
            return 'B-';
        }

        return 'C';
    }

    public function index(Request $request)
    {
        // Load all classrooms with their program
        $classRooms = ClassRoom::query()->with('program')->orderBy('name')->get();
        $selectedClassId = $request->input('class_room_id', $classRooms->first()?->id);
        $selectedClass = $classRooms->firstWhere('id', $selectedClassId);

        // Auto-detect defaults from latest database record to ensure the dashboard works on seeded data
        $latestRecord = HafalanRecord::query()->latest('submitted_at')->first();
        $detectedYearString = '2025/2026';
        $detectedTerm = '1';

        if ($latestRecord) {
            $latestDate = Carbon::parse($latestRecord->submitted_at);
            $detectedMonth = $latestDate->format('m');
            $yearVal = $latestDate->year;

            if (in_array($detectedMonth, ['07', '08', '09'])) {
                $detectedTerm = '1';
                $detectedYearString = "{$yearVal}/".($yearVal + 1);
            } elseif (in_array($detectedMonth, ['10', '11', '12'])) {
                $detectedTerm = '2';
                $detectedYearString = "{$yearVal}/".($yearVal + 1);
            } elseif (in_array($detectedMonth, ['01', '02', '03'])) {
                $detectedTerm = '3';
                $detectedYearString = ($yearVal - 1)."/{$yearVal}";
            } else {
                $detectedTerm = '4';
                $detectedYearString = ($yearVal - 1)."/{$yearVal}";
            }
        }

        $academicYear = $request->input('academic_year', $detectedYearString);
        $selectedTerm = $request->input('term', $detectedTerm);

        // Determine months of the selected term
        if ($selectedTerm == '1') {
            $monthsMap = ['07' => 'Juli', '08' => 'Agustus', '09' => 'September'];
        } elseif ($selectedTerm == '2') {
            $monthsMap = ['10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
        } elseif ($selectedTerm == '3') {
            $monthsMap = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret'];
        } else {
            $monthsMap = ['04' => 'April', '05' => 'Mei', '06' => 'Juni'];
        }

        // Parse start and end years
        $years = explode('/', $academicYear);
        $startYear = (int) $years[0];
        $endYear = isset($years[1]) ? (int) $years[1] : ($startYear + 1);

        // Date range of every month in the term
        $monthRanges = [];
        foreach ($monthsMap as $mCode => $mName) {
            $mYear = in_array($mCode, ['01', '02', '03', '04', '05', '06'], true) ? $endYear : $startYear;
            $mStart = "{$mYear}-{$mCode}-01";
            $monthRanges[$mCode] = [
                'label' => $mName,
                'start' => $mStart,
                'end' => date('Y-m-t', strtotime($mStart)),
            ];
        }
        $termStartDate = reset($monthRanges)['start'];
        $termEndDate = end($monthRanges)['end'];

        // Detect program type
        $programName = strtolower($selectedClass?->program?->name ?? '');
        $isTahfizhProgram = str_contains($programName, 'tahfizh') || str_contains($programName, 'akselerasi');

        // Get actual active students in the selected class
        $students = Student::query()
            ->with(['classRoom', 'teacher.user'])
            ->where('class_room_id', $selectedClassId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Fallback for empty seeded classrooms
        if ($students->isEmpty()) {
            $students = Student::query()
                ->with(['classRoom', 'teacher.user'])
                ->where('status', 'active')
                ->orderBy('name')
                ->take(10)
                ->get();
        }

        $studentIds = $students->pluck('id')->toArray();

        // Fetch the whole term once; each month is sliced from these in memory.
        $termAttendances = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereBetween('tanggal', [$termStartDate, $termEndDate])
            ->get();

        $termHafalanRecords = HafalanRecord::flattenSurahs(
            HafalanRecord::query()
                ->with('surahs.surah')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('submitted_at', [$termStartDate, $termEndDate])
                ->orderBy('submitted_at')
                ->get()
        );

        $termViolations = StudentPoint::query()
            ->whereIn('student_id', $studentIds)
            ->where('type', 'violation')
            ->whereBetween('date', [$termStartDate, $termEndDate])
            ->get();

        $latestTargets = HafalanTarget::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->where('target_date', '<=', $termEndDate)
            ->orderBy('target_date', 'desc')
            ->get()
            ->groupBy('student_id');

        $latestHafalans = HafalanRecord::flattenSurahs(
            HafalanRecord::query()
                ->with(['surahs' => fn ($q) => $q->where('status', 'passed')->with('surah')])
                ->whereIn('student_id', $studentIds)
                ->whereHas('surahs', fn ($q) => $q->where('status', 'passed'))
                ->where('submitted_at', '<=', $termEndDate)
                ->orderBy('submitted_at', 'desc')
                ->get()
        )->groupBy('student_id');

        // Group students by their Musyrif
        $studentsByHalaqah = $students->groupBy(function ($student) {
            return $student->teacher?->user?->name ?? 'Ust. Fuad Faris Ghazi';
        });

        $halaqahData = [];
        $calendar = new AcademicCalendarService;

        foreach ($studentsByHalaqah as $musyrifName => $groupStudents) {
            $gStudentIds = $groupStudents->pluck('id')->toArray();
            $gAttendances = $termAttendances->whereIn('student_id', $gStudentIds);
            $gHafalanRecords = $termHafalanRecords->whereIn('student_id', $gStudentIds);
            $gViolations = $termViolations->whereIn('student_id', $gStudentIds);

            $context = [
                'classRoom' => $selectedClass,
                'calendar' => $calendar,
                'isTahfizhProgram' => $isTahfizhProgram,
                'groupStudents' => $groupStudents,
                'gAttendances' => $gAttendances,
                'gHafalanRecords' => $gHafalanRecords,
                'gViolations' => $gViolations,
                'classAttendances' => $termAttendances,
                'classHafalanRecords' => $termHafalanRecords,
                'latestTargets' => $latestTargets,
                'latestHafalans' => $latestHafalans,
            ];

            $monthly = [];
            foreach ($monthRanges as $mCode => $range) {
                $monthly[$mCode] = $this->buildMonthReport($range, $context);
            }

            $termRecords = $this->buildTermRecords($monthly, $groupStudents, $gAttendances, $gViolations);

            $halaqahData[] = [
                'musyrif' => $musyrifName,
                'students' => $groupStudents,
                'is_tahfizh' => $isTahfizhProgram,
                // Grid presensi 3 bulan (khusus program Tahfizh); Reguler memakai presensi per bulan di 'monthly'.
                'presensi' => $isTahfizhProgram
                    ? $this->buildTahfizhPresensiGrid($monthRanges, $groupStudents, $termAttendances, $termHafalanRecords)
                    : [],
                'monthly' => $monthly,
                'term_records' => $termRecords,
                'months' => array_values($monthsMap),
                'total_students' => count($groupStudents),
                'tuntas_count' => collect($termRecords)->where('is_tuntas', true)->count(),
            ];
        }

        return view('reports.quarterly', [
            'classRooms' => $classRooms,
            'selectedClass' => $selectedClass,
            'isTahfizhProgram' => $isTahfizhProgram,
            'academicYear' => $academicYear,
            'selectedTerm' => $selectedTerm,
            'monthsMap' => $monthsMap,
            'halaqahData' => $halaqahData,
            'months' => array_values($monthsMap),
        ]);
    }

    private function dateString($value): string
    {
        return $value instanceof Carbon ? $value->toDateString() : Carbon::parse($value)->toDateString();
    }

    private function inRange($value, array $range): bool
    {
        $date = $this->dateString($value);

        return $date >= $range['start'] && $date <= $range['end'];
    }

    /**
     * Presensi Tahfizh: satu grid untuk seluruh bulan dalam term (maks. 12 pertemuan per bulan).
     */
    private function buildTahfizhPresensiGrid(array $monthRanges, $groupStudents, $classAttendances, $classHafalanRecords): array
    {
        $grid = [];

        foreach ($groupStudents as $student) {
            $studentAtt = $classAttendances->where('student_id', $student->id);
            $studentHaf = $classHafalanRecords->where('student_id', $student->id);
            $studentPresensi = [];

            foreach ($monthRanges as $range) {
                $mAtt = $studentAtt->filter(fn ($a) => $this->inRange($a->tanggal, $range));
                $mHaf = $studentHaf->filter(fn ($h) => $this->inRange($h->submitted_at, $range));

                $mUniqueDates = $classAttendances->filter(fn ($a) => $this->inRange($a->tanggal, $range))
                    ->pluck('tanggal')
                    ->map(fn ($d) => $this->dateString($d))
                    ->merge(
                        $classHafalanRecords->filter(fn ($h) => $this->inRange($h->submitted_at, $range))
                            ->pluck('submitted_at')
                            ->map(fn ($d) => $this->dateString($d))
                    )
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
                $mMeetings = array_slice($mUniqueDates, 0, 12);

                $mDays = [];
                for ($i = 1; $i <= 12; $i++) {
                    $date = $mMeetings[$i - 1] ?? null;
                    if ($date) {
                        $att = $mAtt->first(fn ($a) => $this->dateString($a->tanggal) === $date);
                        if ($att) {
                            $mDays[$i] = match ($att->status) {
                                'hadir' => 'H',
                                'sakit' => 'S',
                                'izin' => 'I',
                                'alpa' => 'A',
                                default => 'H'
                            };
                        } else {
                            $hasSetoran = $mHaf->contains(fn ($h) => $h->submitted_at->toDateString() === $date);
                            $mDays[$i] = $hasSetoran ? 'H' : '-';
                        }
                    } else {
                        $mDays[$i] = '-';
                    }
                }

                $studentPresensi[$range['label']] = [
                    'days' => $mDays,
                    'sakit' => $mAtt->where('status', 'sakit')->count(),
                    'izin' => $mAtt->where('status', 'izin')->count(),
                    'alpa' => $mAtt->where('status', 'alpa')->count(),
                ];
            }

            $grid[$student->id] = $studentPresensi;
        }

        return $grid;
    }

    /**
     * Laporan satu bulan untuk satu halaqoh: presensi (Reguler), jurnal, capaian setoran, dan ketuntasan.
     */
    private function buildMonthReport(array $range, array $context): array
    {
        $isTahfizhProgram = $context['isTahfizhProgram'];
        $groupStudents = $context['groupStudents'];
        $latestTargets = $context['latestTargets'];
        $latestHafalans = $context['latestHafalans'];

        $gAttendances = $context['gAttendances']->filter(fn ($a) => $this->inRange($a->tanggal, $range));
        $gHafalanRecords = $context['gHafalanRecords']->filter(fn ($h) => $this->inRange($h->submitted_at, $range));
        $violations = $context['gViolations']->filter(fn ($v) => $this->inRange($v->date, $range));

        // Tanggal unik (presensi atau setoran) sekelas pada bulan ini -- dasar jurnal tatap muka.
        $uniqueDates = $context['classAttendances']->filter(fn ($a) => $this->inRange($a->tanggal, $range))
            ->pluck('tanggal')
            ->map(fn ($d) => $this->dateString($d))
            ->merge(
                $context['classHafalanRecords']->filter(fn ($h) => $this->inRange($h->submitted_at, $range))
                    ->pluck('submitted_at')
                    ->map(fn ($d) => $this->dateString($d))
            )
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Hari efektif kelas di bulan ini (jadwal kelas, libur nasional, libur kelas):
        // membedakan "Libur" (tidak ada pertemuan) dari "Belum di input" (ada pertemuan
        // tapi musyrif belum mengisi).
        $monthStart = Carbon::parse($range['start']);
        $daysInMonth = $monthStart->daysInMonth;
        $effectiveByDay = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $effectiveByDay[$d] = $context['classRoom'] === null
                || $context['calendar']->isEffectiveDay($context['classRoom'], $monthStart->copy()->day($d));
        }
        $emptyPekanState = function (int $pStart, int $pEnd) use ($effectiveByDay, $daysInMonth): string {
            for ($d = $pStart; $d <= min($pEnd, $daysInMonth); $d++) {
                if ($effectiveByDay[$d]) {
                    return 'Belum di input';
                }
            }

            return 'Libur';
        };
        $emptyDayState = function (int $isoWeekday, int $pStart, int $pEnd) use ($effectiveByDay, $daysInMonth, $monthStart): string {
            for ($d = $pStart; $d <= min($pEnd, $daysInMonth); $d++) {
                if ((int) $monthStart->copy()->day($d)->format('N') === $isoWeekday) {
                    return $effectiveByDay[$d] ? 'Belum di input' : 'Libur';
                }
            }

            return '-';
        };

        // Jumlah pertemuan terjadwal bulan ini menurut kalender akademik & jadwal kelas
        // (bukan dari data yang sudah diinput musyrif) -- pengali target baris per bulan.
        // Program "seminggu sekali" dihitung maksimal satu pertemuan per pekan kalender.
        $isWeeklyProgram = $context['classRoom']?->program?->meeting_frequency === 'seminggu sekali';
        $scheduledMeetings = 0;
        $countedWeeks = [];
        foreach ($effectiveByDay as $day => $isEffective) {
            if (! $isEffective) {
                continue;
            }
            if ($isWeeklyProgram) {
                $weekKey = $monthStart->copy()->day($day)->format('o-W');
                if (isset($countedWeeks[$weekKey])) {
                    continue;
                }
                $countedWeeks[$weekKey] = true;
            }
            $scheduledMeetings++;
        }

        // A. Presensi mingguan (Reguler)
        $presensiData = [];
        if (! $isTahfizhProgram) {
            foreach ($groupStudents as $student) {
                $pekan = [];
                $sAtt = $gAttendances->where('student_id', $student->id);
                $sHaf = $gHafalanRecords->where('student_id', $student->id);

                for ($p = 1; $p <= 5; $p++) {
                    $pStart = 1 + ($p - 1) * 7;
                    $pEnd = $p === 5 ? 31 : $p * 7;

                    $att = $sAtt->first(function ($a) use ($pStart, $pEnd) {
                        $dayNum = (int) date('d', strtotime($a->tanggal));

                        return $dayNum >= $pStart && $dayNum <= $pEnd;
                    });

                    if ($att) {
                        $pekan[$p] = match ($att->status) {
                            'hadir' => 'Hadir',
                            'sakit' => 'Sakit',
                            'izin' => 'Izin',
                            'alpa' => 'Alpa',
                            default => 'Hadir'
                        };
                    } else {
                        $hasSetoran = $sHaf->contains(function ($h) use ($pStart, $pEnd) {
                            $dayNum = (int) $h->submitted_at->format('d');

                            return $dayNum >= $pStart && $dayNum <= $pEnd;
                        });
                        // Tidak ada presensi tercatat untuk pekan ini: anggap hadir
                        // hanya kalau memang ada setoran nyata. Selain itu, pekan tanpa
                        // hari efektif = "Libur", pekan dengan hari efektif tapi belum
                        // diisi = "Belum di input" -- bukan otomatis hadir.
                        $pekan[$p] = $hasSetoran ? 'Hadir' : $emptyPekanState($pStart, $pEnd);
                    }
                }

                $presensiData[$student->id] = [
                    'pekan' => $pekan,
                    'hadir' => collect($pekan)->filter(fn ($status) => $status === 'Hadir')->count(),
                    'sakit' => $sAtt->where('status', 'sakit')->count(),
                    'izin' => $sAtt->where('status', 'izin')->count(),
                    'alpa' => $sAtt->where('status', 'alpa')->count(),
                ];
            }
        }

        // B. Jurnal
        $jurnalData = [];
        if ($isTahfizhProgram) {
            foreach ($uniqueDates as $date) {
                $jurnalData[] = [
                    'tanggal' => date('d-m-Y', strtotime($date)),
                    'materi' => "Muroja'ah & Ziyadah Hafalan",
                    'jumlah_murid' => $gAttendances->filter(fn ($a) => $this->dateString($a->tanggal) === $date)->where('status', 'hadir')->count() ?: count($groupStudents),
                    'paraf' => '✓',
                ];
            }
            if (empty($jurnalData)) {
                $jurnalData[] = [
                    'tanggal' => 'Belum ada kegiatan',
                    'materi' => "Muroja'ah & Ziyadah Hafalan",
                    'jumlah_murid' => 0,
                    'paraf' => '-',
                ];
            }
        } else {
            for ($p = 1; $p <= 5; $p++) {
                $jurnalData[] = [
                    'tanggal' => "Pekan $p",
                    'materi' => "Muroja'ah & Ziyadah Hafalan",
                    'jumlah_murid' => count($groupStudents),
                    'paraf' => '✓',
                ];
            }
        }

        // C. Capaian setoran
        $tahfizhRecords = [];
        $regulerRecords = [];

        foreach ($groupStudents as $student) {
            $sHaf = $gHafalanRecords->where('student_id', $student->id);
            $sAttAll = $gAttendances->where('student_id', $student->id);
            $pekanRecords = [];
            $totalCapaianLines = 0;

            for ($p = 1; $p <= 5; $p++) {
                $pStart = 1 + ($p - 1) * 7;
                $pEnd = $p === 5 ? 31 : $p * 7;

                if ($isTahfizhProgram) {
                    $sAtt = $sAttAll;
                    $dailyLogs = [];
                    $weekLines = 0;

                    $pRecords = $sHaf->filter(function ($h) use ($pStart, $pEnd) {
                        $dayNum = (int) $h->submitted_at->format('d');

                        return $dayNum >= $pStart && $dayNum <= $pEnd;
                    });

                    $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                    $dayMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

                    foreach ($days as $dayName) {
                        $dayRecords = $pRecords->filter(function ($r) use ($dayName, $dayMap) {
                            $wDay = (int) date('w', strtotime($r->submitted_at));

                            return isset($dayMap[$wDay]) && $dayMap[$wDay] === $dayName;
                        })->filter(fn ($r) => $r->surah);

                        if ($dayRecords->isNotEmpty()) {
                            $lines = $dayRecords->sum('lines_count');
                            $surahLabel = $dayRecords
                                ->map(fn ($r) => "{$r->surah->name_latin} ({$r->ayah_start}-{$r->ayah_end})")
                                ->implode(', ');
                            $avgScore = $dayRecords->whereNotNull('score')->avg('score');
                            $dailyLogs[$dayName] = [
                                'surah' => $surahLabel,
                                'ayat_start' => '',
                                'ayat_end' => '',
                                'baris' => $lines,
                                'nilai' => self::mapScoreToGrade($avgScore),
                            ];
                            $weekLines += $lines;
                        } else {
                            $attRecord = $sAtt->first(function ($a) use ($dayName, $dayMap, $pStart, $pEnd) {
                                $dayNum = (int) date('d', strtotime($a->tanggal));
                                if ($dayNum < $pStart || $dayNum > $pEnd) {
                                    return false;
                                }
                                $wDay = (int) date('w', strtotime($a->tanggal));

                                return isset($dayMap[$wDay]) && $dayMap[$wDay] === $dayName;
                            });

                            $dailyLogs[$dayName] = [
                                'surah' => ($attRecord && $attRecord->status !== 'hadir')
                                    ? ucfirst($attRecord->status)
                                    : $emptyDayState(array_search($dayName, $dayMap), $pStart, $pEnd),
                                'ayat_start' => '',
                                'ayat_end' => '',
                                'baris' => 0,
                                'nilai' => '-',
                            ];
                        }
                    }

                    $pekanRecords[$p] = [
                        'days' => $dailyLogs,
                        'week_lines' => $weekLines,
                    ];
                    $totalCapaianLines += $weekLines;
                } else {
                    $weekRecords = $sHaf->filter(function ($h) use ($pStart, $pEnd) {
                        $dayNum = (int) $h->submitted_at->format('d');

                        return $dayNum >= $pStart && $dayNum <= $pEnd;
                    })->filter(fn ($h) => $h->surah);

                    if ($weekRecords->isNotEmpty()) {
                        $lines = $weekRecords->sum('lines_count');
                        $avgScore = $weekRecords->whereNotNull('score')->avg('score');
                        $pekanRecords[$p] = [
                            'surah' => $weekRecords->map(fn ($h) => $h->surah->name_latin)->implode(', '),
                            'ayat' => $weekRecords->map(fn ($h) => "{$h->ayah_start}-{$h->ayah_end}")->implode(', '),
                            'baris' => $lines,
                            'nilai' => self::mapScoreToGrade($avgScore),
                            'kehadiran' => 'Hadir',
                        ];
                        $totalCapaianLines += $lines;
                    } else {
                        $pekanRecords[$p] = [
                            'surah' => '-',
                            'ayat' => '-',
                            'baris' => 0,
                            'nilai' => '-',
                            'kehadiran' => $presensiData[$student->id]['pekan'][$p],
                        ];
                    }
                }
            }

            $levelBaris = match ($student->tahfizh_level) {
                'tahsin' => 3,
                'reguler' => 5,
                'akselerasi' => 7,
                'ummi' => null,
                default => 5,
            };
            $targetLines = ($levelBaris === null) ? 0 : ($levelBaris * $scheduledMeetings);
            $isTuntas = ($levelBaris === null) ? true : ($totalCapaianLines >= $targetLines);

            $studentTarget = $latestTargets->get($student->id)?->first();
            $studentHafalan = $latestHafalans->get($student->id)?->first();

            $record = [
                'student_id' => $student->id,
                'name' => $student->name,
                'nis' => $student->student_number ?? '4407-2526'.sprintf('%03d', $student->id),
                'level' => ucfirst($student->tahfizh_level ?? 'reguler'),
                'pekan' => $pekanRecords,
                'target_lines' => $targetLines,
                'total_lines' => $totalCapaianLines,
                'is_tuntas' => $isTuntas,
                'pelanggaran' => $violations->where('student_id', $student->id)->count(),
                'target_surah' => $studentTarget?->surah?->name_latin ?? '-',
                'target_ayat' => $studentTarget ? $studentTarget->ayah_range : '-',
                'capaian_surah' => $studentHafalan?->surah?->name_latin ?? '-',
                'capaian_ayat' => $studentHafalan ? "{$studentHafalan->ayah_start}-{$studentHafalan->ayah_end}" : '-',
            ];

            if ($isTahfizhProgram) {
                $tahfizhRecords[] = $record;
            } else {
                $regulerRecords[] = $record;
            }
        }

        $records = $isTahfizhProgram ? $tahfizhRecords : $regulerRecords;

        return [
            'label' => $range['label'],
            'presensi' => $presensiData,
            'jurnal' => $jurnalData,
            'tahfizh_records' => $tahfizhRecords,
            'reguler_records' => $regulerRecords,
            'tuntas_count' => collect($records)->where('is_tuntas', true)->count(),
        ];
    }

    /**
     * Rekap satu term per murid: baris & target dijumlahkan dari semua bulan, absensi dan pelanggaran dihitung sepanjang term.
     */
    private function buildTermRecords(array $monthly, $groupStudents, $gAttendances, $gViolations): array
    {
        $termRecords = [];

        foreach ($groupStudents as $student) {
            $rows = collect($monthly)->map(function ($month) use ($student) {
                $records = $month['tahfizh_records'] ?: $month['reguler_records'];

                return collect($records)->firstWhere('student_id', $student->id);
            })->filter();

            $first = $rows->first();
            $totalLines = $rows->sum('total_lines');
            $targetLines = $rows->sum('target_lines');
            $studentAtt = $gAttendances->where('student_id', $student->id);

            $termRecords[] = [
                'student_id' => $student->id,
                'name' => $student->name,
                'level' => $first['level'] ?? ucfirst($student->tahfizh_level ?? 'reguler'),
                'target_surah' => $first['target_surah'] ?? '-',
                'target_ayat' => $first['target_ayat'] ?? '-',
                'capaian_surah' => $first['capaian_surah'] ?? '-',
                'capaian_ayat' => $first['capaian_ayat'] ?? '-',
                'total_lines' => $totalLines,
                'target_lines' => $targetLines,
                'is_tuntas' => $totalLines >= $targetLines,
                'alpa' => $studentAtt->where('status', 'alpa')->count(),
                'izin' => $studentAtt->where('status', 'izin')->count(),
                'sakit' => $studentAtt->where('status', 'sakit')->count(),
                'pelanggaran' => $gViolations->where('student_id', $student->id)->count(),
            ];
        }

        return $termRecords;
    }
}
