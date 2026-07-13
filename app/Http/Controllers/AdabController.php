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

        // Get all visible student IDs for dashboard statistics
        $allVisibleStudentIds = (clone $studentQuery)->pluck('id');
        $adabStats = AdabRecord::whereIn('student_id', $allVisibleStudentIds)->get();

        $avgAllah = 0; $avgRasul = 0; $avgSosial = 0; $avgQuran = 0;
        if ($adabStats->isNotEmpty()) {
            $avgAllah = round(($adabStats->avg(fn($r) => ($r->q1 + $r->q2 + $r->q3 + $r->q4 + $r->q5) / 5)) * 100, 1);
            $avgRasul = round(($adabStats->avg(fn($r) => ($r->q6 + $r->q7 + $r->q8 + $r->q9 + $r->q10) / 5)) * 100, 1);
            $avgSosial = round(($adabStats->avg(fn($r) => ($r->q11 + $r->q12 + $r->q13 + $r->q14 + $r->q15) / 5)) * 100, 1);
            $avgQuran = round(($adabStats->whereNotNull('mentor_score')->avg('mentor_score') ?? 0), 1);
        }

        $classRankings = ClassRoom::query()
            ->join('students', 'class_rooms.id', '=', 'students.class_room_id')
            ->join('adab_records', 'students.id', '=', 'adab_records.student_id')
            ->selectRaw('class_rooms.name, avg(adab_records.total_score) as avg_score')
            ->groupBy('class_rooms.id', 'class_rooms.name')
            ->orderByDesc('avg_score')
            ->limit(5)
            ->get();

        return view('adab.index', compact('students', 'classRooms', 'isAdmin', 'isSupervisor', 'today', 'avgAllah', 'avgRasul', 'avgSosial', 'avgQuran', 'classRankings'));
    }

    public function create(Student $student): View|RedirectResponse
    {
        $user = Auth::user();
        
        // Authorize: Student themselves, Admin/Supervisor, or student's teacher
        $isOwn = $user->hasRole('student') && $student->user_id === $user->id;
        $isManager = $user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'pendamping_adab']);
        $isTeacher = $user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id;
        
        abort_unless($isOwn || $isManager || $isTeacher, 403, 'Anda tidak memiliki akses untuk mengisi kuisioner adab santri ini.');

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
        
        // Authorize: Student themselves, Admin/Supervisor, or student's teacher
        $isOwn = $user->hasRole('student') && $student->user_id === $user->id;
        $isManager = $user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'pendamping_adab']);
        $isTeacher = $user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id;
        
        abort_unless($isOwn || $isManager || $isTeacher, 403, 'Anda tidak memiliki akses untuk mengisi kuisioner adab santri ini.');

        $rules = [];
        for ($i = 1; $i <= 15; $i++) {
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
        for ($i = 1; $i <= 15; $i++) {
            $sum += (int) $validated["q{$i}"];
        }
        // Student score is out of 50
        $studentScore = round(($sum / 15) * 50, 1);
        $totalScore = $studentScore;

        AdabRecord::create(array_merge($validated, [
            'student_id' => $student->id,
            'evaluator_id' => Auth::id(),
            'assessment_date' => $today,
            'total_score' => $totalScore,
        ]));

        return redirect()
            ->route('adab.show', $student)
            ->with('success', 'Kuisioner Adab hari ini berhasil disimpan dengan nilai mandiri ' . $studentScore . '/50.');
    }

    public function show(Student $student): View
    {
        $user = Auth::user();
        $visible = false;

        // Check if student is visible to user
        if ($user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'pendamping_adab'])) {
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
            ->with(['evaluator', 'mentor'])
            ->orderBy('assessment_date', 'desc')
            ->paginate(10);

        // Calculate average per question (0 to 1 scaling, multiply by 100 for percentage)
        $allRecords = AdabRecord::where('student_id', $student->id)->get();
        
        $averages = [];
        $totalAverage = 0;
        $mentorAverage = 0;
        
        if ($allRecords->isNotEmpty()) {
            for ($i = 1; $i <= 15; $i++) {
                // Average of 1 and 0 is the fraction of "Yes" responses
                $averages["q{$i}"] = round($allRecords->avg("q{$i}"), 2);
            }
            for ($i = 16; $i <= 20; $i++) {
                $averages["q{$i}"] = 0;
            }
            $totalAverage = round($allRecords->avg('total_score'), 1);
            $mentorAverage = round(($allRecords->whereNotNull('mentor_score')->avg('mentor_score') ?? 0), 1);
        }

        return view('adab.show', compact('student', 'adabRecords', 'averages', 'totalAverage', 'mentorAverage'));
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

    public function storeMentorScore(Request $request, Student $student, AdabRecord $adabRecord): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'pendamping_adab']), 403, 'Hanya pendamping adab atau admin yang dapat memberi nilai.');

        $validated = $request->validate([
            'mentor_score' => 'required|integer|min:0|max:100',
        ]);

        $sum = 0;
        for ($i = 1; $i <= 15; $i++) {
            $sum += (int) $adabRecord->{"q{$i}"};
        }
        $studentScore = round(($sum / 15) * 50, 1);
        $mentorWeightedScore = $validated['mentor_score'] * 0.5;
        $totalScore = round($studentScore + $mentorWeightedScore, 1);

        $adabRecord->update([
            'mentor_score' => $validated['mentor_score'],
            'mentor_id' => $user->id,
            'total_score' => $totalScore,
        ]);

        return redirect()
            ->route('adab.show', $student)
            ->with('success', 'Nilai pendamping adab berhasil disimpan: ' . $validated['mentor_score'] . '/100. Total skor menjadi: ' . $totalScore . '.');
    }
}
