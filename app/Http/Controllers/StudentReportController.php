<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\AdabRecord;
use App\Models\AdabMentorAssessment;
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

        $report = StudentReport::firstOrCreate([
            'student_id' => $student->id,
            'academic_year' => $academicYear,
            'semester' => $semester,
        ], [
            'status' => 'draft',
        ]);

        $data = $this->getReportData($student, $academicYear, $semester);
        $data['report'] = $report;

        $totalSetoran = HafalanRecord::where('student_id', $student->id)->where('status', 'passed')->count();
        $totalMurajaah = MurajaahRecord::where('student_id', $student->id)->where('status', 'passed')->count();

        $canEditNotes = $user->hasAnyRole(['super_admin', 'admin', 'teacher']) && $report->status !== 'locked';

        return view('reports.digital-report', array_merge(
            $data,
            [
                'totalSetoran' => $totalSetoran,
                'totalMurajaah' => $totalMurajaah,
                'canEditNotes' => $canEditNotes,
            ]
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
            'tahfizh_target_term' => 'nullable|string|max:255',
            'status' => 'required|string|in:draft,published,locked',
        ]);

        $report = StudentReport::updateOrCreate([
            'student_id' => $student->id,
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
        ], [
            'teacher_notes' => $validated['teacher_notes'],
            'tahfizh_target_term' => $validated['tahfizh_target_term'] ?? null,
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

        $report = StudentReport::where([
            'student_id' => $student->id,
            'academic_year' => $academicYear,
            'semester' => $semester,
        ])->first();

        // Compute Tahfizh Level and targets
        $tahfizhLevelLabel = $student->tahfizh_level_label;
        $termTargetText = '';
        if ($report && $report->tahfizh_target_term) {
            $termTargetText = $report->tahfizh_target_term;
        } else {
            $levelBaris = match ($student->tahfizh_level) {
                'tahsin' => 3,
                'reguler' => 5,
                'akselerasi' => 7,
                'ummi' => null,
                default => 5,
            };

            if ($levelBaris === null) {
                $termTargetText = 'Metode Bacaan Ummi';
            } else {
                $meetingFrequency = $student->classRoom?->program?->meeting_frequency ?? 'setiap hari';
                $meetings = ($meetingFrequency === 'seminggu sekali') ? 4 : 20;
                $totalTargetBaris = $levelBaris * $meetings;
                
                $termTargetText = "Target: {$levelBaris} baris/pertemuan x {$meetings} pertemuan = {$totalTargetBaris} baris/bulan";
            }
        }

        // Compute Capaian Terakhir
        $latestCapaianText = '';
        $latestCapaianNotes = '';

        if ($student->tahfizh_level === 'ummi') {
            $latestUmmi = \App\Models\UmmiRecord::with('surah')
                ->where('student_id', $student->id)
                ->latest('tanggal')
                ->latest()
                ->first();

            if ($latestUmmi) {
                $parts = [];
                if ($latestUmmi->ummi_jilid) {
                    $parts[] = $latestUmmi->ummi_jilid . ($latestUmmi->ummi_halaman ? ' Hal. ' . $latestUmmi->ummi_halaman : '');
                }
                if ($latestUmmi->hafalan_surah_id) {
                    $parts[] = 'Hafalan QS. ' . $latestUmmi->surah?->name_latin . ($latestUmmi->hafalan_ayah ? ' Ayat ' . $latestUmmi->hafalan_ayah : '');
                }
                $latestCapaianText = implode(', ', $parts);
                if ($latestUmmi->nilai) {
                    $latestCapaianText .= ' [Nilai: ' . $latestUmmi->nilai . ']';
                }
                $latestCapaianNotes = $latestUmmi->keterangan;
            } else {
                $latestCapaianText = 'Belum ada catatan UMMI.';
            }
        } else {
            $latestHafalan = HafalanRecord::with('surah')
                ->where('student_id', $student->id)
                ->where('status', 'passed')
                ->latest('submitted_at')
                ->latest()
                ->first();

            if ($latestHafalan) {
                $latestCapaianText = 'QS. ' . $latestHafalan->surah?->name_latin . ' (Ayat ' . $latestHafalan->ayah_start . '-' . $latestHafalan->ayah_end . ')';
                $latestCapaianNotes = $latestHafalan->notes;
            } else {
                $latestCapaianText = 'Belum ada data setoran.';
            }
        }

        // Adab
        $adabRecords = AdabRecord::where('student_id', $student->id)->get();
        $avgAllah = 0; $avgTeman = 0; $avgBelajar = 0; $avgLingkungan = 0; $avgQuran = 0; $avgTotal = 0;
        if ($adabRecords->isNotEmpty()) {
            $sums = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
            $counts = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
            foreach ($adabRecords as $r) {
                if (!empty($r->answers)) {
                    foreach ($sums as $catIdx => $v) {
                        $catAnswers = $r->answers["cat_{$catIdx}"] ?? [];
                        foreach ($catAnswers as $ans) {
                            $sums[$catIdx] += $ans ? 1 : 0;
                            $counts[$catIdx]++;
                        }
                    }
                }
            }
            $avgAllah = $counts[0] > 0 ? round(($sums[0] / $counts[0]) * 100, 1) : 0;
            $avgTeman = $counts[1] > 0 ? round(($sums[1] / $counts[1]) * 100, 1) : 0;
            $avgBelajar = $counts[2] > 0 ? round(($sums[2] / $counts[2]) * 100, 1) : 0;
            $avgLingkungan = $counts[3] > 0 ? round(($sums[3] / $counts[3]) * 100, 1) : 0;

            $avgQuran = round(AdabMentorAssessment::where('student_id', $student->id)->avg('mentor_score') ?? 0, 1);

            $studentAvg = $adabRecords->avg('student_score') ?? 0;
            $mentorAvg = AdabMentorAssessment::where('student_id', $student->id)->avg('mentor_score');
            if ($mentorAvg !== null) {
                $avgTotal = round(($studentAvg * 0.5) + ($mentorAvg * 0.5), 1);
            } else {
                $avgTotal = round($studentAvg, 1);
            }
        }

        // Tanse
        $violations = StudentPoint::where('student_id', $student->id)->where('type', 'violation')->get();
        $rewards = StudentPoint::where('student_id', $student->id)->where('type', 'reward')->get();

        return compact(
            'student',
            'academicYear',
            'semester',
            'progress',
            'hafalanRecords',
            'murajaahRecords',
            'targetRecords',
            'tahfizhExams',
            'tahfizhLevelLabel',
            'termTargetText',
            'latestCapaianText',
            'latestCapaianNotes',
            'avgAllah',
            'avgTeman',
            'avgBelajar',
            'avgLingkungan',
            'avgQuran',
            'avgTotal',
            'violations',
            'rewards',
            'report'
        );
    }
}
