<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\ParentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemNotification;

class StudentPointController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('role');
        $query = StudentPoint::with(['student.classRoom', 'logger']);

        // Filter based on role
        $visibleStudentIds = collect();
        if ($user->hasRole('student')) {
            $student = Student::where('user_id', $user->id)->first();
            $query->where('student_id', $student?->id ?? 0);
            if ($student) $visibleStudentIds->push($student->id);
        } elseif ($user->hasRole('parent')) {
            $parent = ParentProfile::where('user_id', $user->id)->with('students')->first();
            $studentIds = $parent?->students->pluck('id')->toArray() ?? [];
            $query->whereIn('student_id', $studentIds);
            $visibleStudentIds = collect($studentIds);
        } else {
            $visibleStudentIds = Student::where('status', 'active')->pluck('id');
        }

        $classRooms = collect();
        if ($visibleStudentIds->isNotEmpty()) {
            $classRoomIds = Student::whereIn('id', $visibleStudentIds)->pluck('class_room_id')->filter()->unique()->values();
            $classRooms = \App\Models\ClassRoom::query()
                ->when($classRoomIds->isNotEmpty(), fn($q) => $q->whereIn('id', $classRoomIds))
                ->orderBy('name')
                ->get();
        }

        // Apply filters
        if ($request->filled('class_room_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('class_room_id', $request->integer('class_room_id'));
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Sorting & pagination
        $points = $query->orderBy('date', 'desc')->paginate(15)->withQueryString();

        // Calculate statistics
        $statsQuery = StudentPoint::query();
        if ($user->hasRole('student')) {
            $student = Student::where('user_id', $user->id)->first();
            $statsQuery->where('student_id', $student?->id ?? 0);
        } elseif ($user->hasRole('parent')) {
            $parent = ParentProfile::where('user_id', $user->id)->with('students')->first();
            $studentIds = $parent?->students->pluck('id')->toArray() ?? [];
            $statsQuery->whereIn('student_id', $studentIds);
        }

        $totalViolations = (clone $statsQuery)->where('type', 'violation')->sum('points');
        $totalRewards = (clone $statsQuery)->where('type', 'reward')->sum('points');

        // Extended dashboard statistics
        $violationsByCategory = (clone $statsQuery)
            ->where('type', 'violation')
            ->selectRaw('category, count(*) as count, sum(points) as points')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        $violationsByLocation = (clone $statsQuery)
            ->where('type', 'violation')
            ->selectRaw('location, count(*) as count, sum(points) as points')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $rewardsByLevel = (clone $statsQuery)
            ->where('type', 'reward')
            ->selectRaw('achievement_level, count(*) as count, sum(points) as points')
            ->whereNotNull('achievement_level')
            ->groupBy('achievement_level')
            ->get()
            ->keyBy('achievement_level');

        // Top Students (Violations vs Rewards)
        $topAchievers = Student::query()
            ->select('students.id', 'students.name')
            ->join('student_points', 'students.id', '=', 'student_points.student_id')
            ->where('student_points.type', 'reward')
            ->selectRaw('sum(student_points.points) as total_points')
            ->groupBy('students.id', 'students.name')
            ->orderByDesc('total_points')
            ->limit(5)
            ->get();

        $topViolators = Student::query()
            ->select('students.id', 'students.name')
            ->join('student_points', 'students.id', '=', 'student_points.student_id')
            ->where('student_points.type', 'violation')
            ->selectRaw('sum(student_points.points) as total_points')
            ->groupBy('students.id', 'students.name')
            ->orderByDesc('total_points')
            ->limit(5)
            ->get();

        return view('student-points.index', [
            'points' => $points,
            'classRooms' => $classRooms,
            'totalViolations' => $totalViolations,
            'totalRewards' => $totalRewards,
            'violationsByCategory' => $violationsByCategory,
            'violationsByLocation' => $violationsByLocation,
            'rewardsByLevel' => $rewardsByLevel,
            'topAchievers' => $topAchievers,
            'topViolators' => $topViolators,
            'canManage' => $user->hasAnyRole(['super_admin', 'admin', 'tanse']),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeManagement();

        $students = Student::where('status', 'active')->with('classRoom')->orderBy('name')->get();
        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = \App\Models\ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();
        $selectedStudentId = $request->input('student_id');

        return view('student-points.create', compact('students', 'classRooms', 'selectedStudentId'));
    }

    public function store(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:violation,reward',
            'points' => 'required|integer|min:1|max:1000',
            'category' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sanction' => 'nullable|string',
            'achievement_type' => 'nullable|in:academic,non-academic',
            'achievement_level' => 'nullable|in:school,district,province,national',
            'location' => 'nullable|string|max:255',
            'date' => 'required|date',
        ]);

        $validated['logged_by'] = Auth::id();

        $point = StudentPoint::create($validated);

        if ($point->type === 'violation') {
            $this->checkThresholds($point->student_id);
        }

        return redirect()
            ->route('student-points.index')
            ->with('success', 'Catatan poin kedisiplinan berhasil ditambahkan.');
    }

    public function edit(StudentPoint $studentPoint)
    {
        $this->authorizeManagement();

        $students = Student::where('status', 'active')->with('classRoom')->orderBy('name')->get();
        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = \App\Models\ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        return view('student-points.edit', compact('studentPoint', 'students', 'classRooms'));
    }

    public function update(Request $request, StudentPoint $studentPoint)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:violation,reward',
            'points' => 'required|integer|min:1|max:1000',
            'category' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sanction' => 'nullable|string',
            'achievement_type' => 'nullable|in:academic,non-academic',
            'achievement_level' => 'nullable|in:school,district,province,national',
            'location' => 'nullable|string|max:255',
            'date' => 'required|date',
        ]);

        $studentPoint->update($validated);

        if ($studentPoint->type === 'violation') {
            $this->checkThresholds($studentPoint->student_id);
        }

        return redirect()
            ->route('student-points.index')
            ->with('success', 'Catatan poin kedisiplinan berhasil diperbarui.');
    }

    public function destroy(StudentPoint $studentPoint)
    {
        $this->authorizeManagement();

        $studentPoint->delete();

        return redirect()
            ->route('student-points.index')
            ->with('success', 'Catatan poin kedisiplinan berhasil dihapus.');
    }

    private function checkThresholds(int $studentId): void
    {
        $totalViolations = StudentPoint::where('student_id', $studentId)->where('type', 'violation')->sum('points');
        $thresholds = [50, 100, 150, 200];
        
        foreach ($thresholds as $threshold) {
            if ($totalViolations >= $threshold) {
                $uniqueHash = "student_points_warning_{$studentId}_{$threshold}";
                
                $student = Student::with(['parents.user', 'teacher.user', 'user'])->find($studentId);
                if (!$student) continue;

                $usersToNotify = collect();
                if ($student->user) $usersToNotify->push($student->user);
                foreach ($student->parents as $parent) {
                    if ($parent->user) $usersToNotify->push($parent->user);
                }
                if ($student->teacher && $student->teacher->user) {
                    $usersToNotify->push($student->teacher->user);
                }

                foreach ($usersToNotify as $user) {
                    $userSpecificHash = $uniqueHash . '_' . $user->id;
                    if (!SystemNotification::where('unique_hash', $userSpecificHash)->exists()) {
                        SystemNotification::create([
                            'user_id' => $user->id,
                            'created_by' => Auth::id(),
                            'unique_hash' => $userSpecificHash,
                            'title' => 'Ambang Batas Pelanggaran Terlampaui',
                            'message' => "Poin pelanggaran santri {$student->name} telah mencapai {$totalViolations} (Ambang batas: {$threshold} poin). Sanksi dan pembinaan diperlukan.",
                            'type' => 'warning',
                            'action_url' => '/student-points',
                            'published_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    private function authorizeManagement()
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['super_admin', 'admin', 'tanse'])) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola poin kedisiplinan.');
        }
    }
}
