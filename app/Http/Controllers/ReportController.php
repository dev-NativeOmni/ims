<?php

namespace App\Http\Controllers;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Services\UserAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, UserAccessService $accessService): View
    {
        $user = $request->user();
        $visibleStudentIds = $accessService->visibleStudentIds($user);

        $students = Student::query()
            ->with(['classRoom', 'program'])
            ->whereIn('id', $visibleStudentIds)
            ->orderBy('name')
            ->get();

        $selectedStudent = null;
        $hafalanRecords = collect();
        $murajaahRecords = collect();
        $hafalanTargets = collect();

        if ($request->filled('student_id')) {
            $selectedStudent = Student::query()
                ->with(['classRoom', 'program', 'teacher.user', 'parents.user'])
                ->whereIn('id', $visibleStudentIds)
                ->findOrFail($request->student_id);

            Gate::authorize('view', $selectedStudent);

            $hafalanRecords = HafalanRecord::query()
                ->with(['surah', 'teacher.user'])
                ->where('student_id', $selectedStudent->id)
                ->latest('submitted_at')
                ->latest()
                ->get();

            $murajaahRecords = MurajaahRecord::query()
                ->with(['surah', 'teacher.user'])
                ->where('student_id', $selectedStudent->id)
                ->latest('reviewed_at')
                ->latest()
                ->get();

            $hafalanTargets = HafalanTarget::query()
                ->with(['surah', 'teacher.user'])
                ->where('student_id', $selectedStudent->id)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('target_date')
                ->get();
        }

        $summary = [
            'total_students' => $students->count(),
            'total_hafalan' => $hafalanRecords->count(),
            'total_murajaah' => $murajaahRecords->count(),
            'active_targets' => $hafalanTargets->where('status', 'active')->count(),
            'completed_targets' => $hafalanTargets->where('status', 'completed')->count(),
            'average_hafalan_score' => round((float) $hafalanRecords->avg('score'), 2),
            'average_murajaah_score' => round((float) $murajaahRecords->avg('overall_score'), 2),
        ];

        return view('reports.index', compact(
            'students',
            'selectedStudent',
            'hafalanRecords',
            'murajaahRecords',
            'hafalanTargets',
            'summary'
        ));
    }

    public function student(Student $student): View
    {
        Gate::authorize('view', $student);

        $student->load(['classRoom', 'program', 'teacher.user', 'parents.user']);

        $hafalanRecords = HafalanRecord::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest('submitted_at')
            ->latest()
            ->get();

        $murajaahRecords = MurajaahRecord::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest('reviewed_at')
            ->latest()
            ->get();

        $hafalanTargets = HafalanTarget::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('target_date')
            ->get();

        $summary = [
            'total_hafalan' => $hafalanRecords->count(),
            'total_murajaah' => $murajaahRecords->count(),
            'active_targets' => $hafalanTargets->where('status', 'active')->count(),
            'completed_targets' => $hafalanTargets->where('status', 'completed')->count(),
            'average_hafalan_score' => round((float) $hafalanRecords->avg('score'), 2),
            'average_murajaah_score' => round((float) $murajaahRecords->avg('overall_score'), 2),
        ];

        return view('reports.student', compact(
            'student',
            'hafalanRecords',
            'murajaahRecords',
            'hafalanTargets',
            'summary'
        ));
    }

    public function exportCsv(Request $request, UserAccessService $accessService): StreamedResponse
    {
        $user = $request->user();
        $visibleStudentIds = $accessService->visibleStudentIds($user);

        $studentId = $request->integer('student_id');

        if ($studentId > 0) {
            abort_unless($visibleStudentIds->contains($studentId), 403);
        }

        $fileName = 'hafizplus-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($visibleStudentIds, $studentId) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Jenis',
                'Santri',
                'Kelas',
                'Surah',
                'Ayat Mulai',
                'Ayat Akhir',
                'Status',
                'Nilai',
                'Tanggal',
                'Guru',
                'Catatan',
            ]);

            HafalanRecord::query()
                ->with(['student.classRoom', 'surah', 'teacher.user'])
                ->whereIn('student_id', $visibleStudentIds)
                ->when($studentId > 0, fn ($query) => $query->where('student_id', $studentId))
                ->orderBy('student_id')
                ->orderBy('submitted_at')
                ->chunkById(100, function ($records) use ($handle) {
                    foreach ($records as $record) {
                        fputcsv($handle, [
                            'Hafalan',
                            $record->student?->name,
                            $record->student?->classRoom?->name,
                            $record->surah?->name_latin,
                            $record->ayah_start,
                            $record->ayah_end,
                            $record->status_label ?? $record->status,
                            $record->score,
                            $record->submitted_at?->format('Y-m-d'),
                            $record->teacher?->user?->name,
                            $record->notes,
                        ]);
                    }
                });

            MurajaahRecord::query()
                ->with(['student.classRoom', 'surah', 'teacher.user'])
                ->whereIn('student_id', $visibleStudentIds)
                ->when($studentId > 0, fn ($query) => $query->where('student_id', $studentId))
                ->orderBy('student_id')
                ->orderBy('reviewed_at')
                ->chunkById(100, function ($records) use ($handle) {
                    foreach ($records as $record) {
                        fputcsv($handle, [
                            'Murajaah',
                            $record->student?->name,
                            $record->student?->classRoom?->name,
                            $record->surah?->name_latin,
                            $record->ayah_start,
                            $record->ayah_end,
                            $record->status_label ?? $record->status,
                            $record->overall_score,
                            $record->reviewed_at?->format('Y-m-d'),
                            $record->teacher?->user?->name,
                            $record->notes,
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}