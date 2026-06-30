<?php

namespace App\Http\Controllers;

use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdabController extends Controller
{
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

        // Determine which students this user can view
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

        // Apply classroom filter
        if ($request->filled('class_room_id')) {
            $studentQuery->where('class_room_id', $request->integer('class_room_id'));
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $studentQuery->where('name', 'like', "%{$search}%");
        }

        $students = $studentQuery->orderBy('name')->paginate(10)->withQueryString();

        $today = now()->toDateString();

        // Load today's record and calculate average Adab score for each student
        foreach ($students as $student) {
            $student->today_record = AdabRecord::where('student_id', $student->id)
                ->where('assessment_date', $today)
                ->first();
            $student->average_adab_score = AdabRecord::where('student_id', $student->id)->avg('total_score') ?? 0;
        }

        return view('adab.index', compact('students', 'classRooms', 'isAdmin', 'isSupervisor', 'today'));
    }

    public function create(Student $student): View|RedirectResponse
    {
        $user = Auth::user();
        
        // Authorize: Only student themselves, or Admin/Supervisor
        $isOwn = $user->hasRole('student') && $student->user_id === $user->id;
        $isManager = $user->hasAnyRole(['super_admin', 'admin', 'supervisor']);
        
        abort_unless($isOwn || $isManager, 403, 'Anda tidak memiliki akses untuk mengisi kuisioner adab santri ini.');

        // Check if already filled today
        $today = now()->toDateString();
        $alreadyFilled = AdabRecord::where('student_id', $student->id)
            ->where('assessment_date', $today)
            ->exists();
            
        if ($alreadyFilled) {
            return redirect()
                ->route('adab.show', $student)
                ->with('error', 'Kuisioner adab hari ini sudah diisi.');
        }

        return view('adab.create', compact('student'));
    }

    public function store(Request $request, Student $student): RedirectResponse
    {
        $user = Auth::user();
        
        // Authorize: Only student themselves, or Admin/Supervisor
        $isOwn = $user->hasRole('student') && $student->user_id === $user->id;
        $isManager = $user->hasAnyRole(['super_admin', 'admin', 'supervisor']);
        
        abort_unless($isOwn || $isManager, 403, 'Anda tidak memiliki akses untuk mengisi kuisioner adab santri ini.');

        $rules = [];
        for ($i = 1; $i <= 20; $i++) {
            $rules["q{$i}"] = 'required|boolean';
        }
        $rules['notes'] = 'nullable|string|max:1000';

        $validated = $request->validate($rules);

        $today = now()->toDateString();
        
        // Prevent double filling
        $exists = AdabRecord::where('student_id', $student->id)
            ->where('assessment_date', $today)
            ->exists();
            
        if ($exists) {
            return redirect()
                ->route('adab.show', $student)
                ->with('error', 'Anda sudah mengisi kuisioner adab hari ini.');
        }

        $sum = 0;
        for ($i = 1; $i <= 20; $i++) {
            $sum += (int) $validated["q{$i}"];
        }
        $totalScore = $sum * 5; // 20 * 5 = 100 points maximum

        AdabRecord::create(array_merge($validated, [
            'student_id' => $student->id,
            'evaluator_id' => Auth::id(),
            'assessment_date' => $today,
            'total_score' => $totalScore,
        ]));

        return redirect()
            ->route('adab.show', $student)
            ->with('success', 'Kuisioner Adab hari ini berhasil disimpan dengan nilai ' . $totalScore . '/100.');
    }

    public function show(Student $student): View
    {
        $user = Auth::user();
        $visible = false;

        // Check if student is visible to user
        if ($user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
            $visible = true;
        } elseif ($user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id) {
            $visible = true;
        } elseif ($user->hasRole('parent') && $student->parents->contains($user->parentProfile?->id)) {
            $visible = true;
        } elseif ($user->hasRole('student') && $student->user_id === $user->id) {
            $visible = true;
        }

        abort_unless($visible, 403, 'Anda tidak memiliki akses ke data adab santri ini.');

        $student->load(['classRoom', 'teacher.user']);
        $adabRecords = AdabRecord::where('student_id', $student->id)
            ->with('evaluator')
            ->orderBy('assessment_date', 'desc')
            ->paginate(10);

        // Calculate average per question (0 to 1 scaling, multiply by 100 for percentage)
        $allRecords = AdabRecord::where('student_id', $student->id)->get();
        
        $averages = [];
        $totalAverage = 0;
        
        if ($allRecords->isNotEmpty()) {
            for ($i = 1; $i <= 20; $i++) {
                // Average of 1 and 0 is the fraction of "Yes" responses
                $averages["q{$i}"] = round($allRecords->avg("q{$i}"), 2);
            }
            $totalAverage = round($allRecords->avg('total_score'), 1);
        }

        return view('adab.show', compact('student', 'adabRecords', 'averages', 'totalAverage'));
    }

    public function destroy(AdabRecord $adabRecord): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
            abort(403, 'Hanya Koordinator Keagamaan atau Admin yang dapat menghapus penilaian.');
        }

        $student = $adabRecord->student;
        $adabRecord->delete();

        return redirect()
            ->route('adab.show', $student)
            ->with('success', 'Penilaian adab berhasil dihapus.');
    }
}
