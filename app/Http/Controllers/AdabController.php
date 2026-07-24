<?php

namespace App\Http\Controllers;

use App\Models\AdabMentorAssessment;
use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdabController extends Controller
{
    /* -----------------------------------------------------------------------
     | INDEX
     * -------------------------------------------------------------------- */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $isStudent = $user->hasRole('student');

        if ($isStudent) {
            $student = Student::where('user_id', $user->id)->firstOrFail();

            return redirect()->route('adab.show', $student);
        }

        $isAdmin = $user->hasAnyRole(['super_admin', 'admin']);
        $isSupervisor = $user->hasRole('supervisor');
        $isTeacher = $user->hasRole('teacher');
        $isParent = $user->hasRole('parent');
        $isPendampingAdab = $user->hasRole('pendamping_adab');

        $classRoomsQuery = ClassRoom::query()->orderBy('name');
        $studentQuery = Student::query()->with(['classRoom']);

        if ($isPendampingAdab && ! $isAdmin && ! $isSupervisor) {
            $assignedClassIds = ClassRoom::where('pendamping_adab_id', $user->id)->pluck('id');
            $studentQuery->whereIn('class_room_id', $assignedClassIds);
            $classRoomsQuery->whereIn('id', $assignedClassIds);
        } elseif ($isTeacher) {
            $teacherProfile = $user->teacherProfile;
            $studentQuery->where('teacher_id', $teacherProfile?->id);
        } elseif ($isParent) {
            $parentProfile = $user->parentProfile;
            $studentQuery->whereHas('parents', function ($q) use ($parentProfile) {
                $q->where('parent_profiles.id', $parentProfile?->id);
            });
        }

        $classRooms = $classRoomsQuery->get();

        if ($request->filled('class_room_id')) {
            $studentQuery->where('class_room_id', $request->integer('class_room_id'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $studentQuery->where('name', 'like', "%{$search}%");
        }

        $students = $studentQuery->orderBy('name')->paginate(10)->withQueryString();

        $today = now()->toDateString();
        $year = $request->integer('year', (int) now()->format('Y'));
        $month = $request->integer('month', (int) now()->format('n'));

        foreach ($students as $student) {
            $student->today_record = AdabRecord::where('student_id', $student->id)
                ->where('assessment_date', $today)
                ->first();

            $adabScoreData = Setting::calculateAdabScore($student->id, $year, $month);
            $student->adab_attendance_rate = $adabScoreData['attendance_rate'];
            $student->mentor_score = $adabScoreData['mentor_score'];
            $student->average_adab_score = $adabScoreData['final_score'];
            $student->adab_grade = $adabScoreData['grade'];
            $student->adab_grade_label = $adabScoreData['grade_label'];
        }

        // Categories & Stats
        $categories = Setting::getAdabQuestions();

        $allVisibleStudentIds = (clone $studentQuery)->pluck('id');
        $adabStats = AdabRecord::whereIn('student_id', $allVisibleStudentIds)
            ->whereNotNull('answers')
            ->get();

        $catStats = [];
        foreach ($categories as $catIdx => $cat) {
            $total = 0;
            $count = 0;
            foreach ($adabStats as $rec) {
                $answers = $rec->answers;
                if (isset($answers["cat_{$catIdx}"])) {
                    $catAnswers = $answers["cat_{$catIdx}"];
                    $count += count($catAnswers);
                    $total += array_sum(array_map(fn ($v) => $v ? 1 : 0, $catAnswers));
                }
            }
            $catStats[$catIdx] = $count > 0 ? round(($total / $count) * 100, 1) : 0;
        }

        $classRankings = ClassRoom::query()
            ->with(['students'])
            ->get()
            ->map(function ($classRoom) use ($year, $month) {
                $students = $classRoom->students->where('status', 'active');
                if ($students->isEmpty()) {
                    return ['name' => $classRoom->name, 'avg_score' => 0];
                }
                $scores = $students->map(fn ($s) => Setting::calculateAdabScore($s->id, $year, $month)['final_score']);
                return [
                    'name' => $classRoom->name,
                    'avg_score' => round($scores->avg(), 1),
                ];
            })
            ->sortByDesc('avg_score')
            ->take(5)
            ->values();

        return view('adab.index', compact(
            'students', 'classRooms', 'isAdmin', 'isSupervisor',
            'today', 'year', 'month', 'catStats', 'categories', 'classRankings'
        ));
    }

    /* -----------------------------------------------------------------------
     | MONTHLY CHART — Kuisioner Adab per Kelas per Bulan
     * -------------------------------------------------------------------- */
    public function monthlyChart(Request $request): View
    {
        $user = Auth::user();

        $year = $request->integer('year', (int) now()->format('Y'));
        $month = $request->integer('month', (int) now()->format('n'));

        $classRoomsQuery = ClassRoom::query()->with(['students' => function ($q) {
            $q->where('status', 'active');
        }])->orderBy('name');

        if ($user->hasRole('pendamping_adab') && ! $user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
            $classRoomsQuery->where('pendamping_adab_id', $user->id);
        }

        $classRooms = $classRoomsQuery->get();

        $effectiveDaysTotal = Setting::getEffectiveDaysCount($year, $month);

        $classReport = $classRooms->map(function ($classRoom) use ($year, $month, $effectiveDaysTotal) {
            $students = $classRoom->students;
            $totalStudents = $students->count();

            if ($totalStudents === 0) {
                return [
                    'class_room' => $classRoom,
                    'total_students' => 0,
                    'avg_filled_days' => 0,
                    'attendance_rate' => 0,
                    'students_detail' => [],
                ];
            }

            $studentsDetail = [];
            $totalAttendanceRateSum = 0;
            $totalFilledDaysSum = 0;

            foreach ($students as $student) {
                $det = Setting::getStudentAdabAttendanceDetails($student->id, $year, $month);
                $adabScore = Setting::calculateAdabScore($student->id, $year, $month);

                $studentsDetail[] = [
                    'student' => $student,
                    'filled_days' => $det['effective_days_filled'],
                    'attendance_rate' => $det['attendance_rate'],
                    'final_score' => $adabScore['final_score'],
                    'grade' => $adabScore['grade'],
                ];

                $totalAttendanceRateSum += $det['attendance_rate'];
                $totalFilledDaysSum += $det['effective_days_filled'];
            }

            $avgAttendanceRate = round($totalAttendanceRateSum / $totalStudents, 1);
            $avgFilledDays = round($totalFilledDaysSum / $totalStudents, 1);

            return [
                'class_room' => $classRoom,
                'total_students' => $totalStudents,
                'avg_filled_days' => $avgFilledDays,
                'attendance_rate' => $avgAttendanceRate,
                'students_detail' => $studentsDetail,
            ];
        });

        $totalStudentsAll = $classReport->sum('total_students');
        $overallAttendanceRate = $totalStudentsAll > 0
            ? round($classReport->sum(fn ($c) => $c['attendance_rate'] * $c['total_students']) / $totalStudentsAll, 1)
            : 0;

        $monthsList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        // 12-Month Historical Trend for progress comparison
        $allStudentsInScope = $classRooms->flatMap(fn ($c) => $c->students);
        $totalScopeStudents = $allStudentsInScope->count();

        $monthlyTrends = [];
        for ($m = 1; $m <= 12; $m++) {
            if ($totalScopeStudents === 0) {
                $monthlyTrends[$m] = [
                    'month_name' => substr($monthsList[$m], 0, 3),
                    'full_month_name' => $monthsList[$m],
                    'rate' => 0,
                ];
                continue;
            }

            $rateSum = 0;
            foreach ($allStudentsInScope as $st) {
                $det = Setting::getStudentAdabAttendanceDetails($st->id, $year, $m);
                $rateSum += $det['attendance_rate'];
            }
            $avgMonthRate = round($rateSum / $totalScopeStudents, 1);

            $monthlyTrends[$m] = [
                'month_name' => substr($monthsList[$m], 0, 3),
                'full_month_name' => $monthsList[$m],
                'rate' => $avgMonthRate,
            ];
        }

        return view('adab.chart', compact(
            'classReport', 'year', 'month', 'effectiveDaysTotal',
            'overallAttendanceRate', 'monthsList', 'monthlyTrends'
        ));
    }

    /* -----------------------------------------------------------------------
     | CREATE — show questionnaire form
     * -------------------------------------------------------------------- */
    public function create(Student $student): View|RedirectResponse
    {
        $user = Auth::user();

        $isOwn = $user->hasRole('student') && $student->user_id === $user->id;
        $isAdminOrSupervisor = $user->hasAnyRole(['super_admin', 'admin', 'supervisor']);
        $isPendampingAdab = $user->hasRole('pendamping_adab') && ($student->classRoom?->pendamping_adab_id === $user->id || $student->classRoom?->pendamping_adab_id === null);
        $isTeacher = $user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id;

        abort_unless($isOwn || $isAdminOrSupervisor || $isPendampingAdab || $isTeacher, 403);

        $categories = Setting::getAdabQuestions();

        return view('adab.create', compact('student', 'categories'));
    }

    /* -----------------------------------------------------------------------
     | STORE — save student questionnaire
     * -------------------------------------------------------------------- */
    public function store(Request $request, Student $student): RedirectResponse
    {
        $user = Auth::user();

        $isOwn = $user->hasRole('student') && $student->user_id === $user->id;
        $isAdminOrSupervisor = $user->hasAnyRole(['super_admin', 'admin', 'supervisor']);
        $isPendampingAdab = $user->hasRole('pendamping_adab') && ($student->classRoom?->pendamping_adab_id === $user->id || $student->classRoom?->pendamping_adab_id === null);
        $isTeacher = $user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id;

        abort_unless($isOwn || $isAdminOrSupervisor || $isPendampingAdab || $isTeacher, 403);

        $today = now()->toDateString();
        $categories = Setting::getAdabQuestions();

        $answers = [];
        $totalAnswers = 0;
        $positiveAnswers = 0;

        foreach ($categories as $catIdx => $cat) {
            $catKey = "cat_{$catIdx}";
            $catAnswers = $request->input("answers.{$catKey}", []);
            $processedAnswers = [];
            foreach ($cat['questions'] as $qIdx => $_) {
                $val = (bool) ($catAnswers[$qIdx] ?? false);
                $processedAnswers[] = $val;
                $totalAnswers++;
                if ($val) {
                    $positiveAnswers++;
                }
            }
            $answers[$catKey] = $processedAnswers;
        }

        $studentScore = $totalAnswers > 0 ? round(($positiveAnswers / $totalAnswers) * 100, 2) : 0;

        AdabRecord::updateOrCreate(
            [
                'student_id' => $student->id,
                'assessment_date' => $today,
            ],
            [
                'evaluator_id' => $user->id,
                'answers' => $answers,
                'student_score' => $studentScore,
                'notes' => $request->input('notes'),
            ]
        );

        return redirect()->route('adab.show', $student)
            ->with('success', 'Kuisioner adab harian berhasil disimpan.');
    }

    /* -----------------------------------------------------------------------
     | SHOW — detail adab santri
     * -------------------------------------------------------------------- */
    public function show(Student $student, Request $request): View
    {
        $user = Auth::user();

        $visible = false;
        if ($user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
            $visible = true;
        } elseif ($user->hasRole('pendamping_adab') && ($student->classRoom?->pendamping_adab_id === $user->id || $student->classRoom?->pendamping_adab_id === null)) {
            $visible = true;
        } elseif ($user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id) {
            $visible = true;
        } elseif ($user->hasRole('parent') && $student->parents->contains($user->parentProfile?->id)) {
            $visible = true;
        } elseif ($user->hasRole('student') && $student->user_id === $user->id) {
            $visible = true;
        }

        abort_unless($visible, 403);

        $student->load(['classRoom', 'teacher.user']);

        $year = $request->integer('year', (int) now()->format('Y'));
        $month = $request->integer('month', (int) now()->format('n'));

        $adabRecords = AdabRecord::where('student_id', $student->id)
            ->with(['evaluator'])
            ->orderBy('assessment_date', 'desc')
            ->paginate(10);

        // Mentor assessments (periodic)
        $mentorAssessments = AdabMentorAssessment::where('student_id', $student->id)
            ->with('mentor')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $latestMentor = $mentorAssessments->first();

        // New Adab Score 40/60 calculation
        $adabScoreData = Setting::calculateAdabScore($student->id, $year, $month);
        $attendanceRate = $adabScoreData['attendance_rate'];
        $effectiveDaysFilled = $adabScoreData['effective_days_filled'];
        $effectiveDaysTotal = $adabScoreData['effective_days_total'];
        $mentorScore = $adabScoreData['mentor_score'];
        $combinedScore = $adabScoreData['final_score'];
        $grade = $adabScoreData['grade'];
        $gradeLabel = $adabScoreData['grade_label'];

        // Per-category averages
        $categories = Setting::getAdabQuestions();
        $allRecords = AdabRecord::where('student_id', $student->id)->whereNotNull('answers')->get();
        $catAverages = [];
        foreach ($categories as $catIdx => $cat) {
            $total = 0;
            $count = 0;
            foreach ($allRecords as $rec) {
                $catAnswers = $rec->answers["cat_{$catIdx}"] ?? [];
                foreach ($catAnswers as $answer) {
                    $total += $answer ? 1 : 0;
                    $count++;
                }
            }
            $catAverages[$catIdx] = $count > 0 ? round(($total / $count) * 100, 1) : 0;
        }

        $mentorAlreadyScoredThisMonth = AdabMentorAssessment::where('student_id', $student->id)
            ->where('year', $year)
            ->where('month', $month)
            ->exists();

        $isMentor = $user->hasAnyRole(['super_admin', 'admin', 'supervisor'])
            || ($user->hasRole('pendamping_adab') && ($student->classRoom?->pendamping_adab_id === $user->id || $student->classRoom?->pendamping_adab_id === null));

        return view('adab.show', compact(
            'student', 'adabRecords', 'mentorAssessments',
            'attendanceRate', 'effectiveDaysFilled', 'effectiveDaysTotal',
            'mentorScore', 'combinedScore', 'grade', 'gradeLabel',
            'categories', 'catAverages',
            'latestMentor', 'isMentor',
            'mentorAlreadyScoredThisMonth', 'year', 'month'
        ));
    }

    /* -----------------------------------------------------------------------
     | DESTROY — delete a daily record
     * -------------------------------------------------------------------- */
    public function destroy(AdabRecord $adabRecord): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
            abort(403, 'Hanya Koordinator Keagamaan atau Admin yang dapat menghapus penilaian.');
        }

        $student = $adabRecord->student;
        $adabRecord->delete();

        return redirect()->route('adab.show', $student)
            ->with('success', 'Penilaian adab berhasil dihapus.');
    }

    /* -----------------------------------------------------------------------
     | STORE MENTOR SCORE — periodic (monthly)
     * -------------------------------------------------------------------- */
    public function storeMentorScore(Request $request, Student $student): RedirectResponse
    {
        $user = Auth::user();

        $isAuthorizedMentor = $user->hasAnyRole(['super_admin', 'admin', 'supervisor'])
            || ($user->hasRole('pendamping_adab') && ($student->classRoom?->pendamping_adab_id === $user->id || $student->classRoom?->pendamping_adab_id === null));

        abort_unless(
            $isAuthorizedMentor,
            403, 'Hanya pendamping adab kelas ini atau admin yang dapat memberi nilai.'
        );

        $validated = $request->validate([
            'mentor_score' => 'required|integer|min:0|max:100',
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string|max:1000',
        ]);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $periodLabel = ($months[$validated['month']] ?? '-').' '.$validated['year'];

        AdabMentorAssessment::updateOrCreate(
            [
                'student_id' => $student->id,
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'mentor_id' => $user->id,
                'mentor_score' => $validated['mentor_score'],
                'period_label' => $periodLabel,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return redirect()->route('adab.show', $student)
            ->with('success', "Nilai pendamping untuk periode {$periodLabel} berhasil disimpan: {$validated['mentor_score']}/100.");
    }
}
