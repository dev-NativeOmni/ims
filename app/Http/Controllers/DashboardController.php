<?php

namespace App\Http\Controllers;

use App\Models\AdabRecord;
use App\Models\Student;
use App\Services\DashboardService;
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
            'tanse' => redirect()->route('student-points.index'),
            'coordinator_tahfizh' => redirect()->route('progress.index'),
            'pendamping_adab' => redirect()->route('adab.index'),
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
}
