<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $hafalanQuery = $this->filteredHafalanQuery($request, $user);
        $murajaahQuery = $this->filteredMurajaahQuery($request, $user);

        $hafalanRecords = (clone $hafalanQuery)
            ->latest('submitted_at')
            ->latest()
            ->paginate(10, ['*'], 'hafalan_page')
            ->withQueryString();

        $murajaahRecords = (clone $murajaahQuery)
            ->latest('reviewed_at')
            ->latest()
            ->paginate(10, ['*'], 'murajaah_page')
            ->withQueryString();

        $summary = [
            'total_hafalan' => (clone $hafalanQuery)->count(),
            'total_murajaah' => (clone $murajaahQuery)->count(),

            'hafalan_passed' => (clone $hafalanQuery)
                ->where('status', 'passed')
                ->count(),

            'murajaah_passed' => (clone $murajaahQuery)
                ->where('status', 'passed')
                ->count(),

            'hafalan_need_attention' => (clone $hafalanQuery)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count(),

            'murajaah_need_attention' => (clone $murajaahQuery)
                ->whereIn('status', ['repeat', 'needs_improvement'])
                ->count(),

            'average_hafalan_score' => round((float) (clone $hafalanQuery)
                ->whereNotNull('score')
                ->avg('score'), 2),

            'average_murajaah_score' => round((float) (clone $murajaahQuery)
                ->whereNotNull('overall_score')
                ->avg('overall_score'), 2),
        ];

        return view('reports.index', array_merge(
            [
                'hafalanRecords' => $hafalanRecords,
                'murajaahRecords' => $murajaahRecords,
                'summary' => $summary,
            ],
            $this->filterData($user)
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();

        $hafalanRecords = $this->filteredHafalanQuery($request, $user)
            ->latest('submitted_at')
            ->latest()
            ->get();

        $murajaahRecords = $this->filteredMurajaahQuery($request, $user)
            ->latest('reviewed_at')
            ->latest()
            ->get();

        $fileName = 'laporan-hafizplus-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($hafalanRecords, $murajaahRecords) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Tipe',
                'Tanggal',
                'Nama Santri',
                'Nomor Santri',
                'Program',
                'Kelas',
                'Guru',
                'Surah',
                'Rentang Ayat',
                'Jenis/Nilai',
                'Status',
                'Catatan',
            ]);

            foreach ($hafalanRecords as $record) {
                fputcsv($handle, [
                    'Hafalan',
                    $record->submitted_at?->format('Y-m-d'),
                    $record->student?->name,
                    $record->student?->student_number,
                    $record->student?->classRoom?->program?->name,
                    $record->student?->classRoom?->name,
                    $record->teacher?->user?->name,
                    $record->surah?->name_latin,
                    $record->ayah_start . ' - ' . $record->ayah_end,
                    $record->submission_type_label . ' / ' . ($record->score ?? '-'),
                    $record->status_label,
                    $record->notes,
                ]);
            }

            foreach ($murajaahRecords as $record) {
                fputcsv($handle, [
                    'Murajaah',
                    $record->reviewed_at?->format('Y-m-d'),
                    $record->student?->name,
                    $record->student?->student_number,
                    $record->student?->classRoom?->program?->name,
                    $record->student?->classRoom?->name,
                    $record->teacher?->user?->name,
                    $record->surah?->name_latin,
                    $record->ayah_start . ' - ' . $record->ayah_end,
                    'Overall: ' . ($record->overall_score ?? '-')
                        . ' | Kelancaran: ' . ($record->fluency_score ?? '-')
                        . ' | Tajwid: ' . ($record->tajwid_score ?? '-')
                        . ' | Makhraj: ' . ($record->makhraj_score ?? '-'),
                    $record->status_label,
                    $record->notes,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filteredHafalanQuery(Request $request, User $user): Builder
    {
        return HafalanRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->when($user->hasRole('teacher'), function ($query) use ($user) {
                $teacherId = $user->teacherProfile?->id;

                $query->where('teacher_id', $teacherId);
            })
            ->when($request->filled('student_id'), function ($query) use ($request) {
                $query->where('student_id', $request->integer('student_id'));
            })
            ->when($request->filled('teacher_id') && ! $request->user()->hasRole('teacher'), function ($query) use ($request) {
                $query->where('teacher_id', $request->integer('teacher_id'));
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->whereHas('student', function ($studentQuery) use ($request) {
                    $studentQuery->where('class_room_id', $request->integer('class_room_id'));
                });
            })
            ->when($request->filled('surah_id'), function ($query) use ($request) {
                $query->where('surah_id', $request->integer('surah_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('submitted_at', '>=', $request->date('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('submitted_at', '<=', $request->date('date_to'));
            });
    }

    private function filteredMurajaahQuery(Request $request, User $user): Builder
    {
        return MurajaahRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->when($user->hasRole('teacher'), function ($query) use ($user) {
                $teacherId = $user->teacherProfile?->id;

                $query->where('teacher_id', $teacherId);
            })
            ->when($request->filled('student_id'), function ($query) use ($request) {
                $query->where('student_id', $request->integer('student_id'));
            })
            ->when($request->filled('teacher_id') && ! $request->user()->hasRole('teacher'), function ($query) use ($request) {
                $query->where('teacher_id', $request->integer('teacher_id'));
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->whereHas('student', function ($studentQuery) use ($request) {
                    $studentQuery->where('class_room_id', $request->integer('class_room_id'));
                });
            })
            ->when($request->filled('surah_id'), function ($query) use ($request) {
                $query->where('surah_id', $request->integer('surah_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('reviewed_at', '>=', $request->date('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('reviewed_at', '<=', $request->date('date_to'));
            });
    }

    private function filterData(User $user): array
    {
        $studentsQuery = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->where('status', 'active')
            ->orderBy('name');

        if ($user->hasRole('teacher')) {
            $studentsQuery->where('teacher_id', $user->teacherProfile?->id);
        }

        return [
            'students' => $studentsQuery->get(),

            'classRooms' => ClassRoom::query()
                ->with('program')
                ->orderBy('name')
                ->get(),

            'teachers' => TeacherProfile::query()
                ->with('user')
                ->whereHas('user', function ($query) {
                    $query->where('status', 'active');
                })
                ->get()
                ->sortBy(fn (TeacherProfile $teacher) => $teacher->user?->name),

            'surahs' => Surah::query()
                ->orderBy('number')
                ->get(),
        ];
    }
}