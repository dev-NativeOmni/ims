<?php

namespace App\Http\Controllers;

use App\Models\HafalanRecord;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Services\UmmiTatapMukaService;
use App\Services\UserAccessService;
use App\Support\UmmiAyah;
use App\Support\UmmiBook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuickInputController extends Controller
{
    public function storeUmmi(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', HafalanRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        if (! $request->filled('class_room_id') && $request->filled('student_id')) {
            $student = Student::query()->find((int) $request->input('student_id'));
            if ($student) {
                $request->merge(['class_room_id' => $student->class_room_id]);
            }
        }

        $validator = Validator::make($request->all(), [
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            // TM dihitung otomatis dari urutan pertemuan Ummi di triwulan (UmmiTatapMukaService).
            'tatap_muka' => ['nullable', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
            'hafalan_surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'hafalan_ayah' => ['nullable', 'string', 'max:100'],
            'hafalan_surah_ids' => ['nullable', 'array'],
            'hafalan_surah_ids.*' => ['nullable', 'integer', 'exists:surahs,id'],
            'hafalan_ayahs' => ['nullable', 'array'],
            'hafalan_ayahs.*' => ['nullable', 'string', 'max:100'],
            'hafalan_baris' => ['nullable', 'array'],
            'hafalan_baris.*' => ['nullable', 'numeric', 'min:0'],
            ...UmmiBook::rules(),
            'materi' => ['nullable', 'string', 'max:255'],
            'nilai' => ['nullable', 'string', 'max:50'],
            'disimak_guru' => ['required', Rule::in(['Ya', 'Tidak'])],
            'disimak_ortu' => ['required', Rule::in(['Ya', 'Tidak'])],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'student_scores' => ['nullable', 'array'],
            'student_notes' => ['nullable', 'array'],
        ]);

        $validator->after(function ($validator) use ($request, $visibleStudentIds) {
            UmmiBook::checkPages($validator, $request->all());

            // Ayat hafalan tidak boleh melebihi jumlah ayat surah (lihat App\Support\UmmiAyah).
            $ayahPairs = $request->has('hafalan_surah_ids')
                ? collect((array) $request->input('hafalan_surah_ids', []))->map(fn ($sid, $idx) => [$sid, $request->input("hafalan_ayahs.{$idx}")])
                : [[$request->input('hafalan_surah_id'), $request->input('hafalan_ayah')]];
            if ($ayahError = UmmiAyah::firstError($ayahPairs)) {
                $validator->errors()->add('hafalan_ayahs', $ayahError);
            }

            $classRoomId = (int) $request->input('class_room_id');
            $hasAccess = Student::query()
                ->where('class_room_id', $classRoomId)
                ->whereIn('id', $visibleStudentIds)
                ->where('status', 'active')
                ->exists();

            if (! $hasAccess) {
                $validator->errors()->add('class_room_id', 'Kelas halaqoh tidak memiliki murid aktif atau Anda tidak memiliki akses ke kelas ini.');
            }
        });

        $validated = $validator->validate();

        $classRoomId = (int) $validated['class_room_id'];
        $selectedStudentIds = $request->input('student_ids');

        if ($selectedStudentIds === null) {
            if ($request->filled('student_id')) {
                $selectedStudentIds = [$request->integer('student_id')];
                $students = Student::query()
                    ->whereIn('id', $selectedStudentIds)
                    ->where('class_room_id', $classRoomId)
                    ->whereIn('id', $visibleStudentIds)
                    ->where('status', 'active')
                    ->get();
            } else {
                $students = Student::query()
                    ->where('class_room_id', $classRoomId)
                    ->whereIn('id', $visibleStudentIds)
                    ->where('status', 'active')
                    ->get();
            }
        } else {
            if (empty($selectedStudentIds)) {
                return back()
                    ->withInput()
                    ->with('error', 'Silakan pilih minimal satu murid untuk diinput.');
            }
            $students = Student::query()
                ->whereIn('id', array_map('intval', $selectedStudentIds))
                ->where('class_room_id', $classRoomId)
                ->whereIn('id', $visibleStudentIds)
                ->where('status', 'active')
                ->get();
        }

        if ($students->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'Tidak ada murid aktif di kelas halaqoh yang dipilih.');
        }

        $hafalans = [];
        if ($request->has('hafalan_surah_ids')) {
            $surahIds = $request->input('hafalan_surah_ids');
            $ayahs = $request->input('hafalan_ayahs');
            $baris = $request->input('hafalan_baris', []);
            foreach ($surahIds as $idx => $sid) {
                if (! empty($sid)) {
                    $hafalans[] = [
                        'surah_id' => (int) $sid,
                        'ayah' => $ayahs[$idx] ?? null,
                        'baris' => isset($baris[$idx]) && $baris[$idx] !== '' ? (float) $baris[$idx] : null,
                    ];
                }
            }
        } elseif ($request->filled('hafalan_surah_id')) {
            $hafalans[] = [
                'surah_id' => (int) $request->input('hafalan_surah_id'),
                'ayah' => $request->input('hafalan_ayah'),
                'baris' => $request->filled('hafalan_baris') ? (float) $request->input('hafalan_baris') : null,
            ];
        }

        $studentScores = $request->input('student_scores', []);
        $studentNotes = $request->input('student_notes', []);

        // Kiriman ganda (klik dua kali / kirim ulang) untuk kelas & tanggal yang sama diproses bergantian,
        // dan satu murid hanya punya satu sesi Ummi per tanggal (sama dengan Spreadsheet Input):
        // sesi yang sudah ada diperbarui, surah yang identik tidak ditambahkan lagi.
        Cache::lock("ummi-store:{$classRoomId}:{$validated['tanggal']}", 30)->block(15, fn () => DB::transaction(function () use ($students, $hafalans, $validated, $request, $studentScores, $studentNotes) {
            foreach ($students as $student) {
                $teacherId = $this->resolveTeacherId($request, $student);
                if (! $teacherId) {
                    continue; // Skip student without teacher profile
                }

                $individualScore = ! empty($studentScores[$student->id]) ? $studentScores[$student->id] : ($validated['nilai'] ?? null);
                $individualNote = ! empty($studentNotes[$student->id]) ? $studentNotes[$student->id] : ($validated['keterangan'] ?? null);

                $header = [
                    'teacher_id' => $teacherId,
                    'ummi_jilid' => $validated['ummi_jilid'] ?? null,
                    'ummi_halaman' => UmmiBook::halamanFromInput($validated),
                    'materi' => $validated['materi'] ?? null,
                    'nilai' => $individualScore,
                    'disimak_guru' => $validated['disimak_guru'],
                    'disimak_ortu' => $validated['disimak_ortu'],
                    'keterangan' => $individualNote,
                ];

                $ummiRecord = UmmiRecord::query()
                    ->where('student_id', $student->id)
                    ->whereDate('tanggal', $validated['tanggal'])
                    ->orderBy('id')
                    ->first();

                if ($ummiRecord) {
                    $ummiRecord->update($header);
                } else {
                    $ummiRecord = UmmiRecord::query()->create($header + [
                        'student_id' => $student->id,
                        'tatap_muka' => $validated['tatap_muka'] ?? 1, // dinomori ulang setelah simpan
                        'tanggal' => $validated['tanggal'],
                    ]);
                }

                $existingLines = $ummiRecord->surahs()->get(['surah_id', 'hafalan_ayah', 'sort_order']);
                $nextOrder = (int) $existingLines->max('sort_order') + ($existingLines->isEmpty() ? 0 : 1);
                foreach ($hafalans as $hafalan) {
                    $identical = $existingLines->contains(fn ($l) => (int) $l->surah_id === (int) $hafalan['surah_id']
                        && str_replace(' ', '', (string) $l->hafalan_ayah) === str_replace(' ', '', (string) $hafalan['ayah']));
                    if ($identical) {
                        continue;
                    }
                    $ummiRecord->surahs()->create([
                        'surah_id' => $hafalan['surah_id'],
                        'hafalan_ayah' => $hafalan['ayah'],
                        'baris' => $hafalan['baris'],
                        'sort_order' => $nextOrder++,
                    ]);
                }
            }
        }));

        app(UmmiTatapMukaService::class)->renumber($students->map(fn ($s) => [$s->id, $validated['tanggal']])->all());

        return redirect()
            ->route('ummi.riwayat')
            ->with('success', 'Catatan Tahsin UMMI berhasil disimpan untuk seluruh kelas.');
    }

    private function resolveTeacherId(Request $request, Student $student): ?int
    {
        $user = $request->user();
        $teacherId = null;

        if ($user?->teacherProfile?->id) {
            $teacherId = (int) $user->teacherProfile->id;
        } elseif ($user?->hasRole('teacher')) {
            $tId = TeacherProfile::query()->where('user_id', $user->id)->value('id');
            if ($tId) {
                $teacherId = (int) $tId;
            }
        }

        if (! $teacherId && $student->teacher_id) {
            $teacherId = (int) $student->teacher_id;
        }

        if (! $teacherId) {
            $teacherId = (int) TeacherProfile::query()->value('id');
        }

        if ($teacherId && ! $student->teacher_id) {
            $student->update(['teacher_id' => $teacherId]);
        }

        return $teacherId;
    }
}
