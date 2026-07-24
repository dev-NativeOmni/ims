<?php

namespace App\Http\Controllers;

use App\Models\AdabMaterial;
use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\TahfizhExam;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
        //
    }

    public function redirect(Request $request): RedirectResponse
    {
        $user = $request->user()->loadMissing('role');

        if (! $user->isActive()) {
            auth()->logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Akun Anda sedang nonaktif.',
                ]);
        }

        return match ($user->role?->name) {
            'super_admin' => redirect()->route('super-admin.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'teacher' => redirect()->route('teacher.dashboard'),
            'parent' => redirect()->route('parent.dashboard'),
            'student' => redirect()->route('student.dashboard'),
            'supervisor' => redirect()->route('supervisor.dashboard'),
            'headmaster' => redirect()->route('reports.teachers'),
            'tanse' => redirect()->route('tanse.dashboard'),
            'coordinator_tahfizh' => redirect()->route('coordinator-tahfizh.dashboard'),
            'pendamping_adab' => redirect()->route('pendamping-adab.dashboard'),
            default => redirect()->route('login')->withErrors([
                'email' => 'Role akun belum valid.',
            ]),
        };
    }

    public function superAdmin(): View
    {
        $stats = $this->dashboardService->adminStats();
        $today = now()->toDateString();
        $stats['adab_filled_today'] = AdabRecord::where('assessment_date', $today)->count();
        $stats['adab_total_students'] = Student::count();

        return view('dashboards.admin', [
            'title' => 'Super Admin Dashboard',
            'subtitle' => 'Monitoring penuh seluruh data IMS.',
            'stats' => $stats,
        ]);
    }

    public function admin(): View
    {
        $stats = $this->dashboardService->adminStats();
        $today = now()->toDateString();
        $stats['adab_filled_today'] = AdabRecord::where('assessment_date', $today)->count();
        $stats['adab_total_students'] = Student::count();

        return view('dashboards.admin', [
            'title' => 'Admin Dashboard',
            'subtitle' => 'Monitoring operasional santri, guru, hafalan, dan murajaah.',
            'stats' => $stats,
        ]);
    }

    public function teacher(Request $request): View
    {
        return view('dashboards.teacher', [
            'stats' => $this->dashboardService->teacherStats($request->user()),
        ]);
    }

    public function parent(Request $request): View
    {
        return view('dashboards.parent', [
            'stats' => $this->dashboardService->parentStats($request->user()),
        ]);
    }

    public function student(Request $request): View
    {
        return view('dashboards.student', [
            'stats' => $this->dashboardService->studentStats($request->user()),
        ]);
    }

    public function supervisor(Request $request): View
    {
        $today = now()->toDateString();

        $students = Student::with(['classRoom', 'adabRecords' => function ($q) use ($today) {
            $q->where('assessment_date', $today);
        }])->orderBy('name')->get();

        $totalStudents = $students->count();
        $filledCount = $students->filter(fn ($s) => $s->adabRecords->isNotEmpty())->count();
        $notFilledCount = $totalStudents - $filledCount;

        return view('dashboards.supervisor', compact('students', 'totalStudents', 'filledCount', 'notFilledCount', 'today'));
    }

    public function coordinatorTahfizh(Request $request): View
    {
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $stats = [
            'hafalan_this_month' => HafalanRecord::whereBetween('submitted_at', [$startOfMonth, $endOfMonth])->count(),
            'hafalan_today' => HafalanRecord::whereDate('submitted_at', $today)->count(),
            'murajaah_this_month' => MurajaahRecord::whereBetween('reviewed_at', [$startOfMonth, $endOfMonth])->count(),
            'murajaah_today' => MurajaahRecord::whereDate('reviewed_at', $today)->count(),
            'active_targets' => HafalanTarget::where('status', 'in_progress')->count(),
            'completed_targets' => HafalanTarget::where('status', 'completed')->count(),
            'exams_this_month' => TahfizhExam::whereBetween('exam_date', [$startOfMonth, $endOfMonth])->count(),
            'passed_exams' => TahfizhExam::whereBetween('exam_date', [$startOfMonth, $endOfMonth])->where('total_score', '>=', 70)->count(),
        ];

        $recentHafalan = HafalanRecord::with(['student', 'surah'])
            ->latest('submitted_at')
            ->take(5)
            ->get();

        return view('dashboards.coordinator-tahfizh', compact('stats', 'recentHafalan'));
    }

    public function pendampingAdab(Request $request): View
    {
        $today = now()->toDateString();
        $year = (int) date('Y');
        $month = (int) date('n');

        $totalStudents = Student::where('status', 'active')->count();
        $filledToday = AdabRecord::where('assessment_date', $today)->count();
        $fillPercentage = $totalStudents > 0 ? round(($filledToday / $totalStudents) * 100, 1) : 0;

        $students = Student::where('status', 'active')->get();
        $monthlyScores = $students->map(fn ($s) => Setting::calculateAdabScore($s->id, $year, $month)['final_score']);
        $avgScoreMonth = $monthlyScores->isNotEmpty() ? round($monthlyScores->avg(), 1) : 0;
        $adabGradeMonth = Setting::getAdabGrade($avgScoreMonth);

        $classRankings = ClassRoom::with('students')
            ->get()
            ->map(function ($classRoom) use ($year, $month) {
                $st = $classRoom->students->where('status', 'active');
                if ($st->isEmpty()) {
                    return ['name' => $classRoom->name, 'avg_score' => 0];
                }
                $sc = $st->map(fn ($s) => Setting::calculateAdabScore($s->id, $year, $month)['final_score']);
                return ['name' => $classRoom->name, 'avg_score' => round($sc->avg(), 1)];
            })
            ->sortByDesc('avg_score')
            ->take(5)
            ->values();

        $stats = [
            'total_students' => $totalStudents,
            'adab_filled_today' => $filledToday,
            'fill_percentage_today' => $fillPercentage,
            'avg_adab_score_month' => $avgScoreMonth,
            'adab_grade_month' => $adabGradeMonth,
            'total_materials' => AdabMaterial::count(),
            'effective_days' => Setting::getEffectiveWorkdaysInMonth($year, $month),
        ];

        return view('dashboards.pendamping-adab', compact('stats', 'classRankings'));
    }

    public function tanse(Request $request): View
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $violationsMonth = StudentPoint::where('type', '!=', 'reward')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $rewardsMonth = StudentPoint::where('type', 'reward')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->count();

        $stats = [
            'total_violations_month' => $violationsMonth->count(),
            'total_violation_points_month' => $violationsMonth->sum('points'),
            'lateness_count_month' => $violationsMonth->where('type', 'lateness')->count(),
            'attribute_count_month' => $violationsMonth->where('type', 'attribute')->count(),
            'rewards_count_month' => $rewardsMonth,
        ];

        $recentPoints = StudentPoint::with(['student'])
            ->latest('date')
            ->take(5)
            ->get();

        return view('dashboards.tanse', compact('stats', 'recentPoints'));
    }
}
