<?php

namespace App\Services;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentProgressService
{
    private static ?array $allAyahs = null;

    private static ?array $juzTotalAyahs = null;

    public function visibleStudentQuery(?User $user): Builder
    {
        $query = Student::query();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->userHasAnyRole($user, ['super_admin', 'admin', 'headmaster', 'supervisor', 'coordinator_tahfizh', 'tanse'])) {
            return $query;
        }

        if ($this->userHasAnyRole($user, ['teacher'])) {
            $teacherProfile = $user->teacherProfile
                ?? TeacherProfile::query()->where('user_id', $user->id)->first();

            if (! $teacherProfile) {
                // Smart fallback: Find teacher profile with matching name or unlinked user_id
                $teacherProfile = TeacherProfile::query()
                    ->whereHas('user', function ($q) use ($user) {
                        $q->where('name', 'like', '%'.$user->name.'%')
                          ->orWhere('username', $user->username);
                    })
                    ->first();

                if (! $teacherProfile) {
                    $teacherProfile = TeacherProfile::query()->whereNull('user_id')->first();
                }

                if ($teacherProfile && ! $teacherProfile->user_id) {
                    $teacherProfile->update(['user_id' => $user->id]);
                }
            }

            $teacherId = $teacherProfile?->id;

            if (! $teacherId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where('teacher_id', $teacherId);
        }

        if ($this->userHasAnyRole($user, ['parent'])) {
            $parentId = $user->parentProfile?->id
                ?? ParentProfile::query()->where('user_id', $user->id)->value('id');

            if (! $parentId) {
                return $query->whereRaw('1 = 0');
            }

            // Relasi parent-student via pivot table parent_student (confirmed dari migration)
            return $query->whereIn('id', function ($subQuery) use ($parentId) {
                $subQuery->select('student_id')
                    ->from('parent_student')
                    ->where('parent_id', $parentId);
            });
        }

        if ($this->userHasAnyRole($user, ['student'])) {
            // students.user_id selalu ada (confirmed dari migration)
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function buildRows(Collection $students): Collection
    {
        return $students
            ->map(fn (Student $student) => $this->calculate($student))
            ->values();
    }

    public function calculate(Student $student): array
    {
        try {
            $totalQuranAyahs = $this->totalQuranAyahs();
        $memorizedAyahs = $this->memorizedAyahCount($student);

        $progressPercent = $totalQuranAyahs > 0
            ? round(($memorizedAyahs / $totalQuranAyahs) * 100, 2)
            : 0;

        $hafalanRecordsQuery = HafalanRecord::query()
            ->where('student_id', $student->id);

        $murajaahRecordsQuery = MurajaahRecord::query()
            ->where('student_id', $student->id);

        $targetQuery = HafalanTarget::query()
            ->where('student_id', $student->id);

        $latestHafalan = (clone $hafalanRecordsQuery)
            ->with('surah')
            ->latest('submitted_at')
            ->latest()
            ->first();

        $latestMurajaah = (clone $murajaahRecordsQuery)
            ->with('surah')
            ->latest('reviewed_at')
            ->latest()
            ->first();

        $activeTargetStatuses = ['active', 'planned', 'in_progress'];
        $juzStats = $this->getJuzStats($student);

        $classRoomName = $student->classRoom?->name ?? '';
        $classRoomLevel = $student->classRoom?->level ?? '';
        $tahfizhLevel = $student->tahfizh_level ?? 'reguler';

        $isGrade10 = (bool) (
            (preg_match('/\bX\b/i', $classRoomName) && !preg_match('/\b(XI|XII)\b/i', $classRoomName))
            || preg_match('/\b10\b/i', $classRoomName)
            || preg_match('/^X[-_\s]?E/i', $classRoomName)
            || preg_match('/kelas\s*(X|10)/i', $classRoomName)
            || (preg_match('/\bX\b/i', $classRoomLevel) && !preg_match('/\b(XI|XII)\b/i', $classRoomLevel))
            || preg_match('/\b10\b/i', $classRoomLevel)
        ) && !preg_match('/\b(XI|XII|11|12)\b/i', $classRoomName);

        $isUmmiProgram = $isGrade10 || $tahfizhLevel === 'ummi';
        $programCategory = $isUmmiProgram ? 'ummi' : 'reguler';

        // ─── Ummi Program Details ───
        $latestUmmiRecord = \App\Models\UmmiRecord::query()
            ->with('surah')
            ->where('student_id', $student->id)
            ->latest('tanggal')
            ->latest()
            ->first();

        $latestUmmiTarget = HafalanTarget::query()
            ->with('surah')
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereNotNull('ummi_jilid')
            ->latest('target_date')
            ->first();

        $currentJilidStr = $latestUmmiRecord?->ummi_jilid ?? 'Jilid 1';
        preg_match('/(\d+)/', $currentJilidStr, $mJilid);
        $currentJilidNum = isset($mJilid[1]) ? min(3, max(1, (int) $mJilid[1])) : 1;
        preg_match('/(\d+)/', (string) ($latestUmmiRecord?->ummi_halaman ?? ''), $mHal);
        $currentHalaman = isset($mHal[1]) ? min(40, max(1, (int) $mHal[1])) : 1;
        // Jilid Ummi Dewasa: 3 Jilid @ 40 Halaman (120 Halaman Total)
        $totalPagesCompleted = max(1, ($currentJilidNum - 1) * 40 + $currentHalaman);
        $ummiJilidPercent = min(100.0, round(($totalPagesCompleted / 120) * 100, 1));

        // ─── Reguler Program Details (For Grade 11 & 12) ───
        $levelBaris = match ($tahfizhLevel) {
            'tahsin' => 3,
            'reguler' => 5,
            'akselerasi' => 7,
            default => 5,
        };

        $programName = strtolower($student->classRoom?->program?->name ?? '');
        $meetingFrequency = $student->classRoom?->program?->meeting_frequency ?? 'setiap hari';

        // Program Reguler (Kelas 11 & 12 F2-F4) -> 1x/sepekan (~4 pertemuan/bulan)
        // Program Tahfizh (Kelas 11 & 12 F1) -> 5x/sepekan (~20 pertemuan/bulan)
        $isWeeklyProgram = ($meetingFrequency === 'seminggu sekali')
            || str_contains($programName, 'reguler')
            || (bool) preg_match('/F[2-9]\b/i', $classRoomName);

        if (str_contains($programName, 'tahfizh') || (bool) preg_match('/F1\b/i', $classRoomName)) {
            $isWeeklyProgram = false;
        }

        $meetingsPerMonth = $isWeeklyProgram ? 4 : 20;
        $targetBarisMonth = $levelBaris * $meetingsPerMonth;

        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $passedRecordsThisMonth = HafalanRecord::with('surah')
            ->where('student_id', $student->id)
            ->where('status', 'passed')
            ->whereBetween('submitted_at', [$startOfMonth, $endOfMonth])
            ->get();

        $capaianBarisMonth = $passedRecordsThisMonth->sum(fn ($r) => $r->lines_count);
        $regulerBarisPercent = $targetBarisMonth > 0 ? min(100.0, round(($capaianBarisMonth / $targetBarisMonth) * 100, 1)) : 0;

        // ─── Status Badge Calculation (🟢 On-Track / 🟡 Mendekati / 🔴 Perlu Ditingkatkan) ───
        $overdueCount = (clone $targetQuery)
            ->whereIn('status', $activeTargetStatuses)
            ->whereDate('target_date', '<', today())
            ->count();

        $achievementPercent = $isUmmiProgram ? $ummiJilidPercent : $regulerBarisPercent;

        if ($overdueCount === 0 && $achievementPercent >= 90) {
            $statusBadge = 'tuntas';
            $statusLabel = 'Tuntas / Sesuai Target';
            $statusColor = 'emerald';
            $statusIcon = '🟢';
        } elseif ($overdueCount === 0 && $achievementPercent >= 70) {
            $statusBadge = 'mendekati';
            $statusLabel = 'Mendekati Target';
            $statusColor = 'amber';
            $statusIcon = '🟡';
        } else {
            $statusBadge = 'perlu_perhatian';
            $statusLabel = 'Perlu Ditingkatkan';
            $statusColor = 'rose';
            $statusIcon = '🔴';
        }

        return [
            'student' => $student,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_number' => $student->student_number ?? null,
            'class_room_name' => $student->classRoom?->name,
            'program_name' => $student->classRoom?->program?->name,
            'program_category' => $programCategory,
            'is_ummi_program' => $isUmmiProgram,
            'tahfizh_level' => $tahfizhLevel,

            'status_badge' => $statusBadge,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
            'status_icon' => $statusIcon,

            // Ummi Metrics
            'ummi_record' => $latestUmmiRecord,
            'ummi_target' => $latestUmmiTarget,
            'ummi_jilid_str' => $currentJilidStr,
            'ummi_jilid_num' => $currentJilidNum,
            'ummi_halaman' => $latestUmmiRecord?->ummi_halaman ?? '-',
            'ummi_jilid_percent' => $ummiJilidPercent,
            'ummi_tatap_muka' => $latestUmmiRecord?->tatap_muka ?? '-',
            'ummi_munaqasyah_score' => $latestUmmiRecord?->nilai_munaqasyah ?? null,

            // Reguler Metrics
            'level_baris' => $levelBaris,
            'capaian_baris_month' => $capaianBarisMonth,
            'target_baris_month' => $targetBarisMonth,
            'reguler_baris_percent' => $regulerBarisPercent,

            'total_quran_ayahs' => $totalQuranAyahs,
            'memorized_ayahs' => $memorizedAyahs,
            'remaining_ayahs' => max(0, $totalQuranAyahs - $memorizedAyahs),
            'progress_percent' => $progressPercent,
            'completed_juz_count' => $juzStats['juz_count'],
            'completed_juz_list' => $juzStats['juz_count'] > 0
                ? 'Juz '.implode(', ', $juzStats['completed_juz'])
                : 'Belum ada Juz lengkap',

            'total_hafalan_records' => (clone $hafalanRecordsQuery)->count(),
            'passed_hafalan_records' => (clone $hafalanRecordsQuery)
                ->where('status', 'passed')
                ->count(),
            'repeat_hafalan_records' => (clone $hafalanRecordsQuery)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count(),

            'total_murajaah_records' => (clone $murajaahRecordsQuery)->count(),
            'average_hafalan_score' => round((float) (clone $hafalanRecordsQuery)->avg('score'), 2),
            'average_murajaah_score' => $this->averageMurajaahScore($student),

            'total_targets' => (clone $targetQuery)->count(),
            'active_targets' => (clone $targetQuery)
                ->whereIn('status', $activeTargetStatuses)
                ->count(),
            'completed_targets' => (clone $targetQuery)
                ->where('status', 'completed')
                ->count(),
            'missed_targets' => (clone $targetQuery)
                ->where('status', 'missed')
                ->count(),
            'overdue_targets' => (clone $targetQuery)
                ->whereIn('status', $activeTargetStatuses)
                ->whereDate('target_date', '<', today())
                ->count(),
            'latest_hafalan_surah' => $latestHafalan?->surah?->name_latin
                ?? $latestHafalan?->surah?->name
                ?? null,
            'latest_hafalan_ayah' => $latestHafalan
                ? $latestHafalan->ayah_start.' - '.$latestHafalan->ayah_end
                : null,
            'latest_hafalan_date' => $latestHafalan?->submitted_at,

            'latest_murajaah_surah' => $latestMurajaah?->surah?->name_latin
                ?? $latestMurajaah?->surah?->name
                ?? null,
            'latest_murajaah_ayah' => $latestMurajaah
                ? $latestMurajaah->ayah_start.' - '.$latestMurajaah->ayah_end
                : null,
            'latest_murajaah_date' => $latestMurajaah?->reviewed_at,

            'term_milestones' => $this->getTermMilestones($student),
        ];
        } catch (\Throwable $e) {
            return [
                'student' => $student,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_number' => $student->student_number ?? null,
                'class_room_name' => $student->classRoom?->name,
                'program_name' => $student->classRoom?->program?->name,
                'program_category' => 'reguler',
                'is_ummi_program' => false,
                'tahfizh_level' => $student->tahfizh_level ?? 'reguler',
                'status_badge' => 'tuntas',
                'status_label' => 'On-Track',
                'status_color' => 'emerald',
                'status_icon' => '🟢',
                'memorized_ayahs' => 0,
                'total_quran_ayahs' => 6236,
                'remaining_ayahs' => 6236,
                'progress_percent' => 0,
                'completed_juz_count' => 0,
                'completed_juz_list' => 'Belum ada Juz lengkap',
                'total_hafalan_records' => 0,
                'passed_hafalan_records' => 0,
                'repeat_hafalan_records' => 0,
                'total_murajaah_records' => 0,
                'average_hafalan_score' => 0,
                'average_murajaah_score' => 0,
                'total_targets' => 0,
                'active_targets' => 0,
                'completed_targets' => 0,
                'missed_targets' => 0,
                'overdue_targets' => 0,
                'term_milestones' => [
                    'first_record' => null,
                    'journey' => [],
                ],
            ];
        }
    }

    private function getJuzStats(Student $student): array
    {
        if (self::$allAyahs === null) {
            $ayahs = DB::table('ayahs')
                ->select('id', 'surah_id', 'ayah_number', 'juz')
                ->get();

            self::$allAyahs = [];
            self::$juzTotalAyahs = [];

            foreach ($ayahs as $ayah) {
                self::$allAyahs[$ayah->surah_id][$ayah->ayah_number] = $ayah->juz;
                if (! isset(self::$juzTotalAyahs[$ayah->juz])) {
                    self::$juzTotalAyahs[$ayah->juz] = 0;
                }
                self::$juzTotalAyahs[$ayah->juz]++;
            }
        }

        $passedRecords = HafalanRecord::where('student_id', $student->id)
            ->where('status', 'passed')
            ->whereNotNull('surah_id')
            ->whereNotNull('ayah_start')
            ->whereNotNull('ayah_end')
            ->get(['surah_id', 'ayah_start', 'ayah_end']);

        $juzMemorizedCount = [];
        $memorizedMap = [];

        foreach ($passedRecords as $record) {
            $start = (int) $record->ayah_start;
            $end = (int) $record->ayah_end;
            $surahId = (int) $record->surah_id;

            for ($a = $start; $a <= $end; $a++) {
                if (isset(self::$allAyahs[$surahId][$a])) {
                    $juz = self::$allAyahs[$surahId][$a];
                    $key = "{$surahId}-{$a}";
                    if (! isset($memorizedMap[$key])) {
                        $memorizedMap[$key] = true;
                        if (! isset($juzMemorizedCount[$juz])) {
                            $juzMemorizedCount[$juz] = 0;
                        }
                        $juzMemorizedCount[$juz]++;
                    }
                }
            }
        }

        $completedJuz = [];
        foreach (self::$juzTotalAyahs as $juz => $total) {
            $memorized = $juzMemorizedCount[$juz] ?? 0;
            if ($memorized >= $total) {
                $completedJuz[] = $juz;
            }
        }

        sort($completedJuz);

        return [
            'completed_juz' => $completedJuz,
            'juz_count' => count($completedJuz),
        ];
    }

    public function summaryFromRows(Collection $rows): array
    {
        return [
            'total_students' => $rows->count(),
            'total_memorized_ayahs' => (int) $rows->sum('memorized_ayahs'),
            'total_hafalan_records' => (int) $rows->sum('total_hafalan_records'),
            'total_murajaah_records' => (int) $rows->sum('total_murajaah_records'),
            'total_active_targets' => (int) $rows->sum('active_targets'),
            'total_overdue_targets' => (int) $rows->sum('overdue_targets'),
            'average_progress_percent' => round((float) $rows->avg('progress_percent'), 2),
            'average_hafalan_score' => round((float) $rows->avg('average_hafalan_score'), 2),
            'average_murajaah_score' => round((float) $rows->avg('average_murajaah_score'), 2),
        ];
    }

    private function memorizedAyahCount(Student $student): int
    {
        $records = HafalanRecord::query()
            ->where('student_id', $student->id)
            ->where('status', 'passed')
            ->whereNotNull('surah_id')
            ->whereNotNull('ayah_start')
            ->whereNotNull('ayah_end')
            ->get(['surah_id', 'ayah_start', 'ayah_end'])
            ->groupBy('surah_id');

        if ($records->isEmpty()) {
            return 0;
        }

        $surahTotals = Surah::query()
            ->pluck('total_ayah', 'id');

        $total = 0;

        foreach ($records as $surahId => $surahRecords) {
            $surahTotalAyah = (int) ($surahTotals[$surahId] ?? 0);

            if ($surahTotalAyah <= 0) {
                continue;
            }

            $intervals = [];

            foreach ($surahRecords as $record) {
                $start = max(1, (int) $record->ayah_start);
                $end = min($surahTotalAyah, (int) $record->ayah_end);

                if ($start <= $end) {
                    $intervals[] = [$start, $end];
                }
            }

            $total += $this->countMergedIntervals($intervals);
        }

        return $total;
    }

    private function countMergedIntervals(array $intervals): int
    {
        if (empty($intervals)) {
            return 0;
        }

        usort($intervals, fn (array $a, array $b) => $a[0] <=> $b[0]);

        $merged = [];

        foreach ($intervals as [$start, $end]) {
            if (empty($merged)) {
                $merged[] = [$start, $end];

                continue;
            }

            $lastIndex = count($merged) - 1;

            if ($start <= $merged[$lastIndex][1] + 1) {
                $merged[$lastIndex][1] = max($merged[$lastIndex][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }

        $count = 0;

        foreach ($merged as [$start, $end]) {
            $count += ($end - $start + 1);
        }

        return $count;
    }

    private function averageMurajaahScore(Student $student): float
    {
        // murajaah_records.overall_score selalu ada (confirmed dari migration)
        return round((float) MurajaahRecord::query()
            ->where('student_id', $student->id)
            ->avg('overall_score'), 2);
    }

    private function totalQuranAyahs(): int
    {
        $total = (int) Surah::query()->sum('total_ayah');

        return $total > 0 ? $total : 6236;
    }

    private function userHasAnyRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }

            if (($user->role?->name ?? null) === $role) {
                return true;
            }
        }

        return false;
    }

    public function getTermMilestones(Student $student): array
    {
        try {
            $firstHafalan = HafalanRecord::query()
                ->with('surah')
                ->where('student_id', $student->id)
                ->where(function ($q) {
                    $q->where('status', 'passed')->orWhereNull('status');
                })
                ->orderBy('submitted_at', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            $firstUmmi = \App\Models\UmmiRecord::query()
                ->with('surah')
                ->where('student_id', $student->id)
                ->orderBy('tanggal', 'asc')
                ->orderBy('id', 'asc')
                ->first();

            $firstRecord = null;
            if ($firstHafalan && $firstUmmi) {
                $hafDate = \Carbon\Carbon::parse($firstHafalan->submitted_at);
                $ummiDate = \Carbon\Carbon::parse($firstUmmi->tanggal);
                if ($hafDate->lte($ummiDate)) {
                    $firstRecord = [
                        'type' => 'hafalan',
                        'date' => $firstHafalan->submitted_at?->format('d M Y'),
                        'raw_date' => $firstHafalan->submitted_at,
                        'title' => ($firstHafalan->surah?->name_latin ?? 'Surah #'.$firstHafalan->surah_id).' (Ayat '.$firstHafalan->ayah_start.' - '.$firstHafalan->ayah_end.')',
                    ];
                } else {
                    $uTitle = $firstUmmi->surah?->name_latin
                        ? ($firstUmmi->surah->name_latin . ' (Ayat ' . ($firstUmmi->hafalan_ayah ?: '-') . ')')
                        : (($firstUmmi->ummi_jilid ?? 'Ummi') . ' (Hal. ' . ($firstUmmi->ummi_halaman ?? '-') . ')');
                    $firstRecord = [
                        'type' => 'ummi',
                        'date' => $firstUmmi->tanggal?->format('d M Y'),
                        'raw_date' => $firstUmmi->tanggal,
                        'title' => $uTitle,
                    ];
                }
            } elseif ($firstHafalan) {
                $firstRecord = [
                    'type' => 'hafalan',
                    'date' => $firstHafalan->submitted_at?->format('d M Y'),
                    'raw_date' => $firstHafalan->submitted_at,
                    'title' => ($firstHafalan->surah?->name_latin ?? 'Surah #'.$firstHafalan->surah_id).' (Ayat '.$firstHafalan->ayah_start.' - '.$firstHafalan->ayah_end.')',
                ];
            } elseif ($firstUmmi) {
                $uTitle = $firstUmmi->surah?->name_latin
                    ? ($firstUmmi->surah->name_latin . ' (Ayat ' . ($firstUmmi->hafalan_ayah ?: '-') . ')')
                    : (($firstUmmi->ummi_jilid ?? 'Ummi') . ' (Hal. ' . ($firstUmmi->ummi_halaman ?? '-') . ')');
                $firstRecord = [
                    'type' => 'ummi',
                    'date' => $firstUmmi->tanggal?->format('d M Y'),
                    'raw_date' => $firstUmmi->tanggal,
                    'title' => $uTitle,
                ];
            }

            $currentMonth = (int) date('n');
            $currentYear = (int) date('Y');
            $currentSchoolYearStart = $currentMonth >= 7 ? $currentYear : $currentYear - 1;

            $className = $student->classRoom?->name ?? '';
            $level = (int) ($student->classRoom?->level ?? 0);

            $isGrade12 = (bool) ($level === 12 || preg_match('/\bXII\b/i', $className) || preg_match('/\b12\b/i', $className));
            $isGrade11 = (bool) ($level === 11 || (preg_match('/\bXI\b/i', $className) && !preg_match('/\bXII\b/i', $className)) || preg_match('/\b11\b/i', $className));

            $currentGradeNum = 10;
            if ($isGrade12) {
                $currentGradeNum = 12;
            } elseif ($isGrade11) {
                $currentGradeNum = 11;
            }

            $grades = [
                10 => ['code' => 'X', 'name' => 'Kelas 10', 'year_start' => $currentSchoolYearStart - ($currentGradeNum - 10)],
                11 => ['code' => 'XI', 'name' => 'Kelas 11', 'year_start' => $currentSchoolYearStart - ($currentGradeNum - 11)],
                12 => ['code' => 'XII', 'name' => 'Kelas 12', 'year_start' => $currentSchoolYearStart - ($currentGradeNum - 12)],
            ];

            $todayStr = now()->toDateString();
            $journey = [];

            foreach ($grades as $gNum => $gInfo) {
                $syStart = $gInfo['year_start'];
                $syEnd = $syStart + 1;

                $terms = [
                    1 => ['name' => 'Term 1 (Jul - Sep)', 'start' => "{$syStart}-07-01", 'end' => "{$syStart}-09-30"],
                    2 => ['name' => 'Term 2 (Okt - Des)', 'start' => "{$syStart}-10-01", 'end' => "{$syStart}-12-31"],
                    3 => ['name' => 'Term 3 (Jan - Mar)', 'start' => "{$syEnd}-01-01", 'end' => "{$syEnd}-03-31"],
                    4 => ['name' => 'Term 4 (Apr - Jun)', 'start' => "{$syEnd}-04-01", 'end' => "{$syEnd}-06-30"],
                ];

                $termResults = [];

                foreach ($terms as $tNum => $tInfo) {
                    $tStart = $tInfo['start'];
                    $tEnd = $tInfo['end'];
                    $isCurrent = ($todayStr >= $tStart && $todayStr <= $tEnd);

                    $hafalanInTerm = HafalanRecord::query()
                        ->with('surah')
                        ->where('student_id', $student->id)
                        ->where(function ($q) {
                            $q->where('status', 'passed')->orWhereNull('status');
                        })
                        ->whereDate('submitted_at', '>=', $tStart)
                        ->whereDate('submitted_at', '<=', $tEnd)
                        ->orderBy('submitted_at', 'asc')
                        ->get();

                    $ummiInTerm = \App\Models\UmmiRecord::query()
                        ->with('surah')
                        ->where('student_id', $student->id)
                        ->whereDate('tanggal', '>=', $tStart)
                        ->whereDate('tanggal', '<=', $tEnd)
                        ->orderBy('tanggal', 'asc')
                        ->get();

                    $firstH = $hafalanInTerm->first();
                    $lastH = $hafalanInTerm->last();

                    $firstU = $ummiInTerm->first();
                    $lastU = $ummiInTerm->last();

                    $termFirst = null;
                    $termLast = null;

                    // Format Reguler
                    $formatH = function ($h) {
                        return [
                            'date' => $h->submitted_at?->format('d/m/Y'),
                            'surah_name' => $h->surah?->name_latin ?? '-',
                            'ayah_range' => 'Ayat '.$h->ayah_start.'-'.$h->ayah_end,
                            'full_text' => ($h->surah?->name_latin ?? '-').' (Ayat '.$h->ayah_start.'-'.$h->ayah_end.')',
                        ];
                    };

                    // Format Ummi
                    $formatU = function ($u) {
                        $fullStr = $u->surah?->name_latin
                            ? ($u->surah->name_latin . ' (Ayat ' . ($u->hafalan_ayah ?: '-') . ')')
                            : (($u->ummi_jilid ?? 'Ummi') . ' (Hal. ' . ($u->ummi_halaman ?? '-') . ')');
                        return [
                            'date' => $u->tanggal?->format('d/m/Y'),
                            'surah_name' => $u->surah?->name_latin ?? ($u->ummi_jilid ?? 'Ummi'),
                            'ayah_range' => $u->hafalan_ayah ? ('Ayat '.$u->hafalan_ayah) : ('Hal. '.($u->ummi_halaman ?? '-')),
                            'full_text' => $fullStr,
                        ];
                    };

                    if ($firstH && $firstU) {
                        $hDate = \Carbon\Carbon::parse($firstH->submitted_at);
                        $uDate = \Carbon\Carbon::parse($firstU->tanggal);
                        $termFirst = $hDate->lte($uDate) ? $formatH($firstH) : $formatU($firstU);
                    } elseif ($firstH) {
                        $termFirst = $formatH($firstH);
                    } elseif ($firstU) {
                        $termFirst = $formatU($firstU);
                    }

                    if ($lastH && $lastU) {
                        $hDate = \Carbon\Carbon::parse($lastH->submitted_at);
                        $uDate = \Carbon\Carbon::parse($lastU->tanggal);
                        $termLast = $hDate->gte($uDate) ? $formatH($lastH) : $formatU($lastU);
                    } elseif ($lastH) {
                        $termLast = $formatH($lastH);
                    } elseif ($lastU) {
                        $termLast = $formatU($lastU);
                    }

                    $totalLines = $hafalanInTerm->sum('lines_count') + $ummiInTerm->sum('lines_count');

                    $termResults[$tNum] = [
                        'term_number' => $tNum,
                        'name' => $tInfo['name'],
                        'start_date' => $tStart,
                        'end_date' => $tEnd,
                        'is_current' => $isCurrent,
                        'has_data' => ($termFirst !== null),
                        'first_setoran' => $termFirst,
                        'last_setoran' => $termLast,
                        'total_records' => $hafalanInTerm->count() + $ummiInTerm->count(),
                        'total_lines' => $totalLines,
                    ];
                }

                $journey[] = [
                    'grade_num' => $gNum,
                    'grade_name' => $gInfo['name'],
                    'school_year' => "{$syStart}/{$syEnd}",
                    'is_current_grade' => ($gNum === $currentGradeNum),
                    'terms' => $termResults,
                ];
            }

            return [
                'first_record' => $firstRecord,
                'journey' => $journey,
            ];
        } catch (\Throwable $e) {
            return [
                'first_record' => null,
                'journey' => [],
            ];
        }
    }
}
