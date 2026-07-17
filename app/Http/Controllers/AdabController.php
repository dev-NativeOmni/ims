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

        $classRooms = ClassRoom::query()->orderBy('name')->get();

        $studentQuery = Student::query()->with(['classRoom']);

        if ($isTeacher) {
            $teacherProfile = $user->teacherProfile;
            $studentQuery->where('teacher_id', $teacherProfile?->id);
        } elseif ($isParent) {
            $parentProfile = $user->parentProfile;
            $studentQuery->whereHas('parents', function ($q) use ($parentProfile) {
                $q->where('parent_profiles.id', $parentProfile?->id);
            });
        }

        if ($request->filled('class_room_id')) {
            $studentQuery->where('class_room_id', $request->integer('class_room_id'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $studentQuery->where('name', 'like', "%{$search}%");
        }

        $students = $studentQuery->orderBy('name')->paginate(10)->withQueryString();

        $today = now()->toDateString();
        $thisYear = (int) now()->format('Y');
        $thisMonth = (int) now()->format('n');

        foreach ($students as $student) {
            $student->today_record = AdabRecord::where('student_id', $student->id)
                ->where('assessment_date', $today)
                ->first();

            // Average student_score from JSON-based records
            $avg = AdabRecord::where('student_id', $student->id)
                ->whereNotNull('student_score')
                ->avg('student_score') ?? 0;

            // Get latest mentor assessment for this month (or most recent)
            $mentorAssessment = AdabMentorAssessment::where('student_id', $student->id)
                ->orderByDesc('year')->orderByDesc('month')
                ->first();

            // Combined score: if mentor exists, weight 50/50; else just student score
            if ($mentorAssessment) {
                $combined = ($avg * 0.5) + ($mentorAssessment->mentor_score * 0.5);
            } else {
                $combined = $avg;
            }

            $student->average_adab_score = round($combined, 1);
            $student->adab_grade = Setting::getAdabGrade($combined);
        }

        // Stats for dashboard tab
        $allVisibleStudentIds = (clone $studentQuery)->pluck('id');
        $adabStats = AdabRecord::whereIn('student_id', $allVisibleStudentIds)
            ->whereNotNull('answers')
            ->get();

        $catStats = [0 => 0, 1 => 0, 2 => 0, 3 => 0];

        if ($adabStats->isNotEmpty()) {
            foreach ($catStats as $catIdx => $_) {
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
        }

        $categories = Setting::getAdabQuestions();

        $classRankings = ClassRoom::query()
            ->join('students', 'class_rooms.id', '=', 'students.class_room_id')
            ->join('adab_records', 'students.id', '=', 'adab_records.student_id')
            ->whereNotNull('adab_records.student_score')
            ->selectRaw('class_rooms.name, avg(adab_records.student_score) as avg_score')
            ->groupBy('class_rooms.id', 'class_rooms.name')
            ->orderByDesc('avg_score')
            ->limit(5)
            ->get();

        return view('adab.index', compact(
            'students', 'classRooms', 'isAdmin', 'isSupervisor',
            'today', 'catStats', 'categories', 'classRankings'
        ));
    }

    /* -----------------------------------------------------------------------
     | CREATE — show questionnaire form
     * -------------------------------------------------------------------- */
    public function create(Student $student): View|RedirectResponse
    {
        $user = Auth::user();

        $isOwn = $user->hasRole('student') && $student->user_id === $user->id;
        $isManager = $user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'pendamping_adab']);
        $isTeacher = $user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id;

        abort_unless($isOwn || $isManager || $isTeacher, 403);

        $today = now()->toDateString();
        $alreadyFilled = AdabRecord::where('student_id', $student->id)
            ->where('assessment_date', $today)
            ->exists();

        if ($alreadyFilled) {
            return redirect()->route('adab.show', $student)
                ->with('error', 'Kuisioner adab hari ini sudah diisi.');
        }

        $categories = Setting::getAdabQuestions();

        return view('adab.create', compact('student', 'categories'));
    }

    /* -----------------------------------------------------------------------
     | STORE — save daily questionnaire
     * -------------------------------------------------------------------- */
    public function store(Request $request, Student $student): RedirectResponse
    {
        $user = Auth::user();

        $isOwn = $user->hasRole('student') && $student->user_id === $user->id;
        $isManager = $user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'pendamping_adab']);
        $isTeacher = $user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id;

        abort_unless($isOwn || $isManager || $isTeacher, 403);

        $today = now()->toDateString();

        if (AdabRecord::where('student_id', $student->id)->where('assessment_date', $today)->exists()) {
            return redirect()->route('adab.show', $student)
                ->with('error', 'Kuisioner adab hari ini sudah diisi.');
        }

        $categories = Setting::getAdabQuestions();

        // Build validation rules dynamically
        $rules = ['notes' => 'nullable|string|max:1000'];
        foreach ($categories as $catIdx => $cat) {
            foreach ($cat['questions'] as $qIdx => $_) {
                $rules["cat_{$catIdx}_q{$qIdx}"] = 'required|boolean';
            }
        }
        $validated = $request->validate($rules);

        // Build answers JSON
        $answers = [];
        foreach ($categories as $catIdx => $cat) {
            $catAnswers = [];
            foreach ($cat['questions'] as $qIdx => $_) {
                $catAnswers[] = (bool) $validated["cat_{$catIdx}_q{$qIdx}"];
            }
            $answers["cat_{$catIdx}"] = $catAnswers;
        }

        // Calculate student score (0–100)
        $allAnswers = array_merge(...array_values($answers));
        $studentScore = count($allAnswers) > 0
            ? round((array_sum($allAnswers) / count($allAnswers)) * 100, 2)
            : 0;

        AdabRecord::create([
            'student_id' => $student->id,
            'evaluator_id' => Auth::id(),
            'assessment_date' => $today,
            'answers' => $answers,
            'student_score' => $studentScore,
            'total_score' => $studentScore, // total = student only until mentor adds score
            'notes' => $validated['notes'] ?? null,
        ]);

        $grade = Setting::getAdabGrade($studentScore);

        return redirect()->route('adab.show', $student)
            ->with('success', "Kuisioner Adab hari ini berhasil disimpan. Nilai Mandiri: {$studentScore}/100 (Nilai: {$grade}).");
    }

    /* -----------------------------------------------------------------------
     | SHOW — student adab detail + history
     * -------------------------------------------------------------------- */
    public function show(Student $student): View
    {
        $user = Auth::user();
        $visible = false;

        if ($user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'pendamping_adab'])) {
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

        // Calculate averages
        $allRecords = AdabRecord::where('student_id', $student->id)
            ->whereNotNull('student_score')
            ->get();

        $studentAvg = $allRecords->avg('student_score') ?? 0;

        // Combined: if mentor score exists, weighted 50/50
        $combinedScore = $latestMentor
            ? round(($studentAvg * 0.5) + ($latestMentor->mentor_score * 0.5), 1)
            : round($studentAvg, 1);

        $grade = Setting::getAdabGrade($combinedScore);
        $gradeLabel = Setting::getAdabGradeLabel($grade);

        // Per-category averages (for chart/display)
        $categories = Setting::getAdabQuestions();
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

        // Check if mentor already scored this month
        $thisYear = (int) now()->format('Y');
        $thisMonth = (int) now()->format('n');
        $mentorAlreadyScoredThisMonth = AdabMentorAssessment::where('student_id', $student->id)
            ->where('year', $thisYear)
            ->where('month', $thisMonth)
            ->exists();

        $isMentor = $user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'pendamping_adab']);

        return view('adab.show', compact(
            'student', 'adabRecords', 'mentorAssessments',
            'studentAvg', 'combinedScore', 'grade', 'gradeLabel',
            'categories', 'catAverages',
            'latestMentor', 'isMentor',
            'mentorAlreadyScoredThisMonth', 'thisYear', 'thisMonth'
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
        abort_unless(
            $user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'pendamping_adab']),
            403, 'Hanya pendamping adab atau admin yang dapat memberi nilai.'
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
