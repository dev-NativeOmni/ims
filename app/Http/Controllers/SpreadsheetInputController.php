<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Surah;
use App\Models\HafalanRecord;
use App\Models\UmmiRecord;
use App\Models\Attendance;
use App\Models\TeacherProfile;
use App\Services\UserAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SpreadsheetInputController extends Controller
{
    public function index(Request $request, UserAccessService $accessService): View
    {
        Gate::authorize('create', HafalanRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        // Get classes matching user scope
        $classRoomIds = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->pluck('class_room_id')
            ->filter()
            ->unique()
            ->values();

        $classRooms = ClassRoom::query()
            ->with('program')
            ->when($classRoomIds->isNotEmpty(), fn($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $selectedClassId = $request->input('class_room_id');
        if (!$selectedClassId && $classRooms->isNotEmpty()) {
            $selectedClassId = $classRooms->first()->id;
        }

        $selectedClass = $classRooms->firstWhere('id', $selectedClassId);

        // Get selected month (default to current month)
        $selectedMonth = $request->input('month', date('Y-m'));

        // Parse month dates (Monday to Friday only)
        $year = (int) date('Y', strtotime($selectedMonth . '-01'));
        $month = (int) date('m', strtotime($selectedMonth . '-01'));
        
        $dates = [];
        $daysInMonth = (int) date('t', strtotime($selectedMonth . '-01'));
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $time = mktime(0, 0, 0, $month, $day, $year);
            $dayOfWeek = date('N', $time);
            if ($dayOfWeek <= 5) {
                $dates[] = date('Y-m-d', $time);
            }
        }

        $students = collect();
        $attendancesMap = [];
        $hafalanRecordsMap = [];
        $ummiRecordsMap = [];

        if ($selectedClassId) {
            $students = Student::query()
                ->with(['classRoom.program', 'teacher.user'])
                ->where('class_room_id', $selectedClassId)
                ->whereIn('id', $visibleStudentIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            $studentIds = $students->pluck('id')->toArray();
            $startDate = $selectedMonth . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            // Load Attendances
            $attendances = Attendance::query()
                ->whereIn('student_id', $studentIds)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            foreach ($attendances as $att) {
                $dateStr = $att->tanggal instanceof \Carbon\Carbon ? $att->tanggal->toDateString() : $att->tanggal;
                $attendancesMap[$att->student_id][$dateStr] = $att->status;
            }

            // Load HafalanRecords
            $hafalanRecords = HafalanRecord::query()
                ->whereIn('student_id', $studentIds)
                ->whereBetween('submitted_at', [$startDate, $endDate])
                ->get();

            foreach ($hafalanRecords as $record) {
                $dateStr = $record->submitted_at instanceof \Carbon\Carbon ? $record->submitted_at->toDateString() : $record->submitted_at;
                $hafalanRecordsMap[$record->student_id][$dateStr][] = [
                    'id' => $record->id,
                    'surah_id' => $record->surah_id,
                    'ayah_start' => $record->ayah_start,
                    'ayah_end' => $record->ayah_end,
                    'score' => $record->score,
                    'status' => $record->status,
                    'submission_type' => $record->submission_type,
                ];
            }

            // Load UmmiRecords
            $ummiRecords = UmmiRecord::query()
                ->whereIn('student_id', $studentIds)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            foreach ($ummiRecords as $record) {
                $dateStr = $record->tanggal instanceof \Carbon\Carbon ? $record->tanggal->toDateString() : $record->tanggal;
                $ummiRecordsMap[$record->student_id][$dateStr]['tatap_muka'] = $record->tatap_muka;
                $ummiRecordsMap[$record->student_id][$dateStr]['ummi_jilid'] = $record->ummi_jilid;
                $ummiRecordsMap[$record->student_id][$dateStr]['ummi_halaman'] = $record->ummi_halaman;
                $ummiRecordsMap[$record->student_id][$dateStr]['materi'] = $record->materi;
                $ummiRecordsMap[$record->student_id][$dateStr]['nilai'] = $record->nilai;

                if ($record->hafalan_surah_id) {
                    $ummiRecordsMap[$record->student_id][$dateStr]['hafalans'][] = [
                        'id' => $record->id,
                        'surah_id' => $record->hafalan_surah_id,
                        'ayah' => $record->hafalan_ayah,
                    ];
                }
            }
        }

        $surahs = Surah::query()->orderBy('number')->get();

        return view('spreadsheet-input.index', [
            'classRooms' => $classRooms,
            'selectedClassId' => $selectedClassId,
            'selectedClass' => $selectedClass,
            'selectedMonth' => $selectedMonth,
            'dates' => $dates,
            'students' => $students,
            'surahs' => $surahs,
            'attendancesMap' => $attendancesMap,
            'hafalanRecordsMap' => $hafalanRecordsMap,
            'ummiRecordsMap' => $ummiRecordsMap,
        ]);
    }

    public function save(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', HafalanRecord::class);

        $validated = $request->validate([
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'month' => ['required', 'string'],
            'type' => ['required', Rule::in(['hafalan', 'ummi'])],
            'records' => ['nullable', 'array'],
        ]);

        $classRoomId = (int)$validated['class_room_id'];
        $type = $validated['type'];

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        DB::transaction(function () use ($request, $classRoomId, $type, $visibleStudentIds) {
            foreach ($request->input('records', []) as $studentId => $studentData) {
                $studentId = (int)$studentId;
                if (!$visibleStudentIds->contains($studentId)) {
                    continue; // Skip student without access
                }

                $student = Student::findOrFail($studentId);
                $teacherId = $this->resolveTeacherId($request, $student);
                if (!$teacherId) {
                    continue; // Skip student without teacher profile
                }

                foreach ($studentData['dates'] ?? [] as $date => $cellData) {
                    $attendance = $cellData['attendance'] ?? null;

                    // 1. Save Attendance
                    if (filled($attendance)) {
                        Attendance::updateOrCreate(
                            ['student_id' => $studentId, 'tanggal' => $date, 'class_room_id' => $classRoomId],
                            ['status' => $attendance]
                        );
                    } else {
                        Attendance::where('student_id', $studentId)
                            ->where('tanggal', $date)
                            ->where('class_room_id', $classRoomId)
                            ->delete();
                    }

                    // If not present, clear any records for this student on this date
                    if ($attendance !== 'hadir') {
                        HafalanRecord::where('student_id', $studentId)
                            ->where('submitted_at', $date)
                            ->delete();
                        UmmiRecord::where('student_id', $studentId)
                            ->where('tanggal', $date)
                            ->delete();
                        continue;
                    }

                    // 2. Save Setoran (Hafalan / UMMI)
                    if ($type === 'hafalan') {
                        $existingRecordIds = HafalanRecord::where('student_id', $studentId)
                            ->where('submitted_at', $date)
                            ->pluck('id')
                            ->toArray();

                        $processedRecordIds = [];

                        foreach ($cellData['hafalans'] ?? [] as $hafalanData) {
                            if (empty($hafalanData['surah_id']) || empty($hafalanData['ayah_start']) || empty($hafalanData['ayah_end'])) {
                                continue;
                            }

                            // Calculate lines count
                            $surah = Surah::find($hafalanData['surah_id']);
                            $baris = 0.0;
                            if ($surah) {
                                $baris = \App\Http\Controllers\ReportController::calculateLines(
                                    $surah->number,
                                    (int)$hafalanData['ayah_start'],
                                    (int)$hafalanData['ayah_end'],
                                    $surah->total_ayah
                                );
                            }

                            $dataToSave = [
                                'student_id' => $studentId,
                                'teacher_id' => $teacherId,
                                'surah_id' => $hafalanData['surah_id'],
                                'ayah_start' => $hafalanData['ayah_start'],
                                'ayah_end' => $hafalanData['ayah_end'],
                                'score' => $hafalanData['score'] ?? null,
                                'status' => $hafalanData['status'] ?? 'passed',
                                'submission_type' => $hafalanData['submission_type'] ?? 'new',
                                'submitted_at' => $date,
                                'baris' => $baris,
                            ];

                            $recordId = $hafalanData['id'] ?? null;
                            if ($recordId && in_array((int)$recordId, $existingRecordIds)) {
                                HafalanRecord::where('id', $recordId)->update($dataToSave);
                                $processedRecordIds[] = (int)$recordId;
                            } else {
                                $newRec = HafalanRecord::create($dataToSave);
                                $processedRecordIds[] = $newRec->id;
                            }
                        }

                        $toDeleteIds = array_diff($existingRecordIds, $processedRecordIds);
                        if (!empty($toDeleteIds)) {
                            HafalanRecord::whereIn('id', $toDeleteIds)->delete();
                        }

                    } elseif ($type === 'ummi') {
                        $existingRecordIds = UmmiRecord::where('student_id', $studentId)
                            ->where('tanggal', $date)
                            ->pluck('id')
                            ->toArray();

                        $processedRecordIds = [];

                        $ummiJilid = $cellData['ummi_jilid'] ?? null;
                        $ummiHalaman = $cellData['ummi_halaman'] ?? null;
                        $materi = $cellData['materi'] ?? null;
                        $nilai = $cellData['nilai'] ?? null;
                        $tatapMuka = $cellData['tatap_muka'] ?? 1;

                        $hasUmmiFields = filled($ummiJilid) || filled($ummiHalaman) || filled($materi) || filled($nilai);
                        $hafalansList = $cellData['hafalans'] ?? [];

                        if (!$hasUmmiFields && empty($hafalansList)) {
                            UmmiRecord::where('student_id', $studentId)
                                ->where('tanggal', $date)
                                ->delete();
                            continue;
                        }

                        if (empty($hafalansList)) {
                            $dataToSave = [
                                'student_id' => $studentId,
                                'teacher_id' => $teacherId,
                                'tatap_muka' => $tatapMuka,
                                'tanggal' => $date,
                                'hafalan_surah_id' => null,
                                'hafalan_ayah' => null,
                                'baris' => null,
                                'ummi_jilid' => $ummiJilid,
                                'ummi_halaman' => $ummiHalaman,
                                'materi' => $materi,
                                'nilai' => $nilai,
                                'disimak_guru' => 'Ya',
                                'disimak_ortu' => 'Ya',
                            ];

                            if (!empty($existingRecordIds)) {
                                $firstId = $existingRecordIds[0];
                                UmmiRecord::where('id', $firstId)->update($dataToSave);
                                $processedRecordIds[] = $firstId;
                            } else {
                                $newRec = UmmiRecord::create($dataToSave);
                                $processedRecordIds[] = $newRec->id;
                            }
                        } else {
                            foreach ($hafalansList as $hafalanData) {
                                if (empty($hafalanData['surah_id']) || empty($hafalanData['ayah'])) {
                                    continue;
                                }

                                $surah = Surah::find($hafalanData['surah_id']);
                                $baris = 0.0;
                                if ($surah) {
                                    $clean = str_replace(' ', '', $hafalanData['ayah']);
                                    if (str_contains($clean, '-')) {
                                        $parts = explode('-', $clean);
                                        $start = (int) $parts[0];
                                        $end = (int) $parts[1];
                                    } else {
                                        $start = (int) $clean;
                                        $end = (int) $clean;
                                    }
                                    if ($start > 0 && $end >= $start) {
                                        $baris = \App\Http\Controllers\ReportController::calculateLines(
                                            $surah->number,
                                            $start,
                                            $end,
                                            $surah->total_ayah
                                        );
                                    }
                                }

                                $dataToSave = [
                                    'student_id' => $studentId,
                                    'teacher_id' => $teacherId,
                                    'tatap_muka' => $tatapMuka,
                                    'tanggal' => $date,
                                    'hafalan_surah_id' => $hafalanData['surah_id'],
                                    'hafalan_ayah' => $hafalanData['ayah'],
                                    'baris' => $baris,
                                    'ummi_jilid' => $ummiJilid,
                                    'ummi_halaman' => $ummiHalaman,
                                    'materi' => $materi,
                                    'nilai' => $nilai,
                                    'disimak_guru' => 'Ya',
                                    'disimak_ortu' => 'Ya',
                                ];

                                $recordId = $hafalanData['id'] ?? null;
                                if ($recordId && in_array((int)$recordId, $existingRecordIds)) {
                                    UmmiRecord::where('id', $recordId)->update($dataToSave);
                                    $processedRecordIds[] = (int)$recordId;
                                } else {
                                    $unprocessedIds = array_diff($existingRecordIds, $processedRecordIds);
                                    if (!empty($unprocessedIds)) {
                                        $reuseId = array_shift($unprocessedIds);
                                        UmmiRecord::where('id', $reuseId)->update($dataToSave);
                                        $processedRecordIds[] = $reuseId;
                                    } else {
                                        $newRec = UmmiRecord::create($dataToSave);
                                        $processedRecordIds[] = $newRec->id;
                                    }
                                }
                            }
                        }

                        $toDeleteIds = array_diff($existingRecordIds, $processedRecordIds);
                        if (!empty($toDeleteIds)) {
                            UmmiRecord::whereIn('id', $toDeleteIds)->delete();
                        }
                    }
                }
            }
        });

        return redirect()
            ->route('spreadsheet-input.index', [
                'class_room_id' => $classRoomId,
                'month' => $validated['month'],
            ])
            ->with('success', 'Perubahan data kelas berhasil disimpan.');
    }

    private function resolveTeacherId(Request $request, Student $student): ?int
    {
        $user = $request->user();

        if ($user?->hasRole('teacher')) {
            return TeacherProfile::query()
                ->where('user_id', $user->id)
                ->value('id');
        }

        return $student->teacher_id;
    }
}
