<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMurajaahRecordRequest;
use App\Http\Requests\UpdateMurajaahRecordRequest;
use App\Models\ClassRoom;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MurajaahRecordController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $murajaahRecords = MurajaahRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->when($user->hasRole('teacher'), function ($query) use ($user) {
                $query->where('teacher_id', $user->teacherProfile?->id);
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('class_room_id', $request->integer('class_room_id'));
                });
            })
            ->when($request->filled('student_id'), function ($query) use ($request) {
                $query->where('student_id', $request->integer('student_id'));
            })
            ->when($request->filled('surah_id'), function ($query) use ($request) {
                $query->where('surah_id', $request->integer('surah_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->latest('reviewed_at')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('murajaah-records.index', array_merge(
            [
                'murajaahRecords' => $murajaahRecords,
            ],
            $this->formData($user)
        ));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', MurajaahRecord::class);

        return view('murajaah-records.create', $this->formData($request->user()));
    }

    public function store(StoreMurajaahRecordRequest $request): RedirectResponse
    {
        $this->authorize('create', MurajaahRecord::class);

        $validated = $request->validated();
        $surahStartId = (int) $validated['surah_id'];
        $surahEndId = (int) ($validated['surah_end_id'] ?? $surahStartId);

        if ($surahStartId === $surahEndId) {
            unset($validated['surah_end_id']);
            MurajaahRecord::query()->create($validated);
        } else {
            $surahStart = Surah::findOrFail($surahStartId);
            $surahEnd = Surah::findOrFail($surahEndId);

            $surahs = Surah::whereBetween('number', [$surahStart->number, $surahEnd->number])
                ->orderBy('number')
                ->get();

            DB::transaction(function () use ($surahs, $surahStart, $surahEnd, $validated) {
                foreach ($surahs as $surah) {
                    $recordData = $validated;
                    unset($recordData['surah_end_id']);
                    $recordData['surah_id'] = $surah->id;

                    if ($surah->id === $surahStart->id) {
                        $recordData['ayah_start'] = $validated['ayah_start'];
                        $recordData['ayah_end'] = $surah->total_ayah;
                    } elseif ($surah->id === $surahEnd->id) {
                        $recordData['ayah_start'] = 1;
                        $recordData['ayah_end'] = $validated['ayah_end'];
                    } else {
                        $recordData['ayah_start'] = 1;
                        $recordData['ayah_end'] = $surah->total_ayah;
                    }

                    MurajaahRecord::query()->create($recordData);
                }
            });
        }

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil ditambahkan.');
    }

    public function show(MurajaahRecord $murajaahRecord): View
    {
        $this->authorize('view', $murajaahRecord);

        $murajaahRecord->load([
            'student.classRoom.program',
            'teacher.user',
            'surah',
        ]);

        return view('murajaah-records.show', [
            'murajaahRecord' => $murajaahRecord,
        ]);
    }

    public function edit(Request $request, MurajaahRecord $murajaahRecord): View
    {
        $this->authorize('update', $murajaahRecord);

        return view('murajaah-records.edit', array_merge(
            [
                'murajaahRecord' => $murajaahRecord,
            ],
            $this->formData($request->user())
        ));
    }

    public function update(UpdateMurajaahRecordRequest $request, MurajaahRecord $murajaahRecord): RedirectResponse
    {
        $this->authorize('update', $murajaahRecord);

        $murajaahRecord->update($request->validated());

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil diperbarui.');
    }

    public function destroy(MurajaahRecord $murajaahRecord): RedirectResponse
    {
        $this->authorize('delete', $murajaahRecord);

        $murajaahRecord->delete();

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil dihapus.');
    }

    private function formData(User $user): array
    {
        $students = Student::query()
            ->with([
                'classRoom.program',
                'teacher.user',
            ])
            ->where('status', 'active')
            ->when($user->hasRole('teacher'), function ($query) use ($user) {
                $query->where('teacher_id', $user->teacherProfile?->id);
            })
            ->orderBy('name')
            ->get();

        $teachers = TeacherProfile::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->sortBy(fn (TeacherProfile $teacher) => $teacher->user?->name)
            ->values();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        return [
            'students' => $students,
            'teachers' => $teachers,
            'surahs' => $surahs,
            'classRooms' => $classRooms,
        ];
    }
}
