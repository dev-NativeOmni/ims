<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\ParentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentPointController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('role');
        $query = StudentPoint::with(['student.classRoom', 'logger']);

        // Filter based on role
        if ($user->hasRole('student')) {
            $student = Student::where('user_id', $user->id)->first();
            $query->where('student_id', $student?->id ?? 0);
        } elseif ($user->hasRole('parent')) {
            $parent = ParentProfile::where('user_id', $user->id)->with('students')->first();
            $studentIds = $parent?->students->pluck('id')->toArray() ?? [];
            $query->whereIn('student_id', $studentIds);
        }

        // Apply filters
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

        return view('student-points.index', [
            'points' => $points,
            'totalViolations' => $totalViolations,
            'totalRewards' => $totalRewards,
            'canManage' => $user->hasAnyRole(['super_admin', 'admin', 'tanse']),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeManagement();

        $students = Student::where('status', 'active')->orderBy('name')->get();
        $selectedStudentId = $request->input('student_id');

        return view('student-points.create', compact('students', 'selectedStudentId'));
    }

    public function store(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:violation,reward',
            'points' => 'required|integer|min:1|max:1000',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $validated['logged_by'] = Auth::id();

        StudentPoint::create($validated);

        return redirect()
            ->route('student-points.index')
            ->with('success', 'Catatan poin kedisiplinan berhasil ditambahkan.');
    }

    public function edit(StudentPoint $studentPoint)
    {
        $this->authorizeManagement();

        $students = Student::where('status', 'active')->orderBy('name')->get();

        return view('student-points.edit', compact('studentPoint', 'students'));
    }

    public function update(Request $request, StudentPoint $studentPoint)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:violation,reward',
            'points' => 'required|integer|min:1|max:1000',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $studentPoint->update($validated);

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

    private function authorizeManagement()
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['super_admin', 'admin', 'tanse'])) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola poin kedisiplinan.');
        }
    }
}
