<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\AdabRecord;
use App\Models\StudentReport;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\HafalanTarget;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentReportController extends Controller
{
    public function __construct(
        protected StudentProgressService $progressService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        $visibleStudentQuery = $this->progressService->visibleStudentQuery($user);

        if ($request->filled('class_room_id')) {
            $visibleStudentQuery->where('class_room_id', $request->integer('class_room_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $visibleStudentQuery->where('name', 'like', "%{$search}%");
        }

        $students = $visibleStudentQuery->with(['classRoom'])->orderBy('name')->paginate(15)->withQueryString();
        
        $classRooms = ClassRoom::query()->orderBy('name')->get();

        return view('reports.digital-report-index', compact('students', 'classRooms'));
    }

    public function show(Student $student, Request $request)
    {
        $user = $request->user();
        
        // Authorize
        $canView = $this->progressService->visibleStudentQuery($user)
            ->where('id', $student->id)
            ->exists();
        abort_unless($canView, 403);

        $student->load(['classRoom.program', 'teacher.user', 'parents.user']);

        // Academic settings (default to 2025/2026 and semester 1)
        $academicYear = $request->input('academic_year', '2025/2026');
        $semester = $request->integer('semester', 1);

        // 1. TAHFIZH PROGRESS
        $progress = $this->progressService->calculate($student);
        $totalSetoran = HafalanRecord::where('student_id', $student->id)->where('status', 'passed')->count();
        $totalMurajaah = MurajaahRecord::where('student_id', $student->id)->where('status', 'passed')->count();

        // 2. ADAB AVERAGES (aspects)
        $adabRecords = AdabRecord::where('student_id', $student->id)->get();
        $avgAllah = 0; $avgRasul = 0; $avgSosial = 0; $avgQuran = 0; $avgTotal = 0;
        if ($adabRecords->isNotEmpty()) {
            $avgAllah = round(($adabRecords->avg(fn($r) => ($r->q1+$r->q2+$r->q3+$r->q4+$r->q5)/5)) * 100, 1);
            $avgRasul = round(($adabRecords->avg(fn($r) => ($r->q6+$r->q7+$r->q8+$r->q9+$r->q10)/5)) * 100, 1);
            $avgSosial = round(($adabRecords->avg(fn($r) => ($r->q11+$r->q12+$r->q13+$r->q14+$r->q15)/5)) * 100, 1);
            $avgQuran = round(($adabRecords->whereNotNull('mentor_score')->avg('mentor_score') ?? 0), 1);
            $avgTotal = round($adabRecords->avg('total_score'), 1);
        }

        // 3. TANSE DISCIPLINE & AWARDS
        $violations = StudentPoint::where('student_id', $student->id)->where('type', 'violation')->get();
        $rewards = StudentPoint::where('student_id', $student->id)->where('type', 'reward')->get();

        // 4. GET REPORT RECORD
        $report = StudentReport::firstOrCreate([
            'student_id' => $student->id,
            'academic_year' => $academicYear,
            'semester' => $semester,
        ], [
            'status' => 'draft',
        ]);

        $canEditNotes = $user->hasAnyRole(['super_admin', 'admin', 'teacher']) && $report->status !== 'locked';

        return view('reports.digital-report', compact(
            'student',
            'academicYear',
            'semester',
            'progress',
            'totalSetoran',
            'totalMurajaah',
            'avgAllah',
            'avgRasul',
            'avgSosial',
            'avgQuran',
            'avgTotal',
            'violations',
            'rewards',
            'report',
            'canEditNotes'
        ));
    }

    public function update(Request $request, Student $student)
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['super_admin', 'admin', 'teacher']), 403);

        $validated = $request->validate([
            'academic_year' => 'required|string',
            'semester' => 'required|integer|in:1,2',
            'teacher_notes' => 'nullable|string',
            'status' => 'required|string|in:draft,published,locked',
        ]);

        $report = StudentReport::updateOrCreate([
            'student_id' => $student->id,
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
        ], [
            'teacher_notes' => $validated['teacher_notes'],
            'status' => $validated['status'],
            'created_by' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Catatan rapor digital berhasil diperbarui.');
    }

    public function print(Student $student, Request $request)
    {
        $user = $request->user();
        $canView = $this->progressService->visibleStudentQuery($user)
            ->where('id', $student->id)
            ->exists();
        abort_unless($canView, 403);

        $academicYear = $request->input('academic_year', '2025/2026');
        $semester = $request->integer('semester', 1);

        $data = $this->getReportData($student, $academicYear, $semester);

        return view('reports.digital-report-print', $data);
    }

    public function printClass(ClassRoom $classRoom, Request $request)
    {
        $user = $request->user();
        
        $visibleStudentIds = $this->progressService->visibleStudentQuery($user)
            ->where('class_room_id', $classRoom->id)
            ->pluck('id');

        $students = Student::whereIn('id', $visibleStudentIds)->orderBy('name')->get();
        
        if ($students->isEmpty()) {
            abort(404, 'Tidak ada santri di kelas ini yang dapat Anda akses.');
        }

        $academicYear = $request->input('academic_year', '2025/2026');
        $semester = $request->integer('semester', 1);

        $reportsData = [];
        foreach ($students as $student) {
            $reportsData[] = $this->getReportData($student, $academicYear, $semester);
        }

        return view('reports.digital-report-class-print', compact('classRoom', 'reportsData', 'academicYear', 'semester'));
    }

    private function getReportData(Student $student, string $academicYear, int $semester): array
    {
        $student->load(['classRoom.program', 'teacher.user', 'parents.user']);

        // Tahfizh
        $progress = $this->progressService->calculate($student);
        $hafalanRecords = HafalanRecord::with('surah')->where('student_id', $student->id)->where('status', 'passed')->latest()->limit(5)->get();
        $murajaahRecords = MurajaahRecord::with('surah')->where('student_id', $student->id)->where('status', 'passed')->latest()->limit(5)->get();
        $targetRecords = HafalanTarget::with('surah')->where('student_id', $student->id)->orderBy('target_date', 'asc')->limit(5)->get();

        foreach ($targetRecords as $target) {
            $matchingRecord = HafalanRecord::where('student_id', $student->id)
                ->where('surah_id', $target->surah_id)
                ->where('status', 'passed')
                ->where('ayah_start', '<=', $target->ayah_start)
                ->where('ayah_end', '>=', $target->ayah_end)
                ->latest()
                ->first();
            
            if (!$matchingRecord) {
                $matchingRecord = HafalanRecord::where('student_id', $student->id)
                    ->where('surah_id', $target->surah_id)
                    ->where('status', 'passed')
                    ->latest()
                    ->first();
            }
            
            $target->matching_record = $matchingRecord;
        }

        $tahfizhExams = \App\Models\TahfizhExam::with('surah')
            ->where('student_id', $student->id)
            ->latest('exam_date')
            ->latest()
            ->limit(5)
            ->get();

        // Adab
        $adabRecords = AdabRecord::where('student_id', $student->id)->get();
        $avgAllah = 0; $avgRasul = 0; $avgSosial = 0; $avgQuran = 0; $avgTotal = 0;
        if ($adabRecords->isNotEmpty()) {
            $avgAllah = round(($adabRecords->avg(fn($r) => ($r->q1+$r->q2+$r->q3+$r->q4+$r->q5)/5)) * 100, 1);
            $avgRasul = round(($adabRecords->avg(fn($r) => ($r->q6+$r->q7+$r->q8+$r->q9+$r->q10)/5)) * 100, 1);
            $avgSosial = round(($adabRecords->avg(fn($r) => ($r->q11+$r->q12+$r->q13+$r->q14+$r->q15)/5)) * 100, 1);
            $avgQuran = round(($adabRecords->whereNotNull('mentor_score')->avg('mentor_score') ?? 0), 1);
            $avgTotal = round($adabRecords->avg('total_score'), 1);
        }

        // Tanse
        $violations = StudentPoint::where('student_id', $student->id)->where('type', 'violation')->get();
        $rewards = StudentPoint::where('student_id', $student->id)->where('type', 'reward')->get();

        $report = StudentReport::where([
            'student_id' => $student->id,
            'academic_year' => $academicYear,
            'semester' => $semester,
        ])->first();

        return compact(
            'student',
            'academicYear',
            'semester',
            'progress',
            'hafalanRecords',
            'murajaahRecords',
            'targetRecords',
            'tahfizhExams',
            'avgAllah',
            'avgRasul',
            'avgSosial',
            'avgQuran',
            'avgTotal',
            'violations',
            'rewards',
            'report'
        );
    }
}
