<?php

namespace App\Http\Controllers;

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
            default => redirect()->route('login')->withErrors([
                'email' => 'Role akun belum valid.',
            ]),
        };
    }

    public function superAdmin(): View
    {
        return view('dashboards.admin', [
            'title' => 'Super Admin Dashboard',
            'subtitle' => 'Monitoring penuh seluruh data HafizPlus.',
            'stats' => $this->dashboardService->adminStats(),
        ]);
    }

    public function admin(): View
    {
        return view('dashboards.admin', [
            'title' => 'Admin Dashboard',
            'subtitle' => 'Monitoring operasional santri, guru, hafalan, dan murajaah.',
            'stats' => $this->dashboardService->adminStats(),
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
}