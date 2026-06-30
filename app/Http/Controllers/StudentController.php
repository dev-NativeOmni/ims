<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $classRooms = ClassRoom::query()
            ->with('program')
            ->orderBy('name')
            ->get();

        $students = Student::query()
            ->with([
                'user',
                'classRoom.program',
                'teacher.user',
                'parents.user',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->where('class_room_id', $request->integer('class_room_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('students.index', compact('students', 'classRooms'));
    }

    public function create(): View
    {
        return view('students.create', $this->formData());
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = Student::create($this->studentPayload($validated));

        $this->syncParents($student, $validated);

        return redirect()
            ->route('students.index')
            ->with('success', 'Data santri berhasil ditambahkan.');
    }

    public function show(Student $student): View
    {
        $student->load([
            'user',
            'classRoom.program',
            'teacher.user',
            'parents.user',
        ]);

        $hafalanRecords = $student->hafalanRecords()
            ->with([
                'surah',
                'teacher.user',
            ])
            ->latest('submitted_at')
            ->latest()
            ->paginate(10);

        return view('students.show', compact('student', 'hafalanRecords'));
    }

    public function edit(Student $student): View
    {
        $student->load('parents');

        return view('students.edit', array_merge(
            ['student' => $student],
            $this->formData($student)
        ));
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        $student->update($this->studentPayload($validated));

        $this->syncParents($student, $validated);

        return redirect()
            ->route('students.index')
            ->with('success', 'Data santri berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->parents()->detach();
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('success', 'Data santri berhasil dihapus.');
    }

    private function formData(?Student $student = null): array
    {
        $classRooms = ClassRoom::query()
            ->with('program')
            ->orderBy('name')
            ->get();

        $teachers = TeacherProfile::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->sortBy(fn (TeacherProfile $teacher) => $teacher->user?->name);

        $parents = ParentProfile::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->sortBy(fn (ParentProfile $parent) => $parent->user?->name);

        $studentUsers = User::query()
            ->with('role')
            ->whereHas('role', function ($query) {
                $query->where('name', 'student');
            })
            ->where('status', 'active')
            ->where(function ($query) use ($student) {
                $query->whereDoesntHave('studentProfile');

                if ($student?->user_id) {
                    $query->orWhere('id', $student->user_id);
                }
            })
            ->orderBy('name')
            ->get();

        return compact('classRooms', 'teachers', 'parents', 'studentUsers');
    }

    private function studentPayload(array $validated): array
    {
        return Arr::only($validated, [
            'user_id',
            'class_room_id',
            'teacher_id',
            'name',
            'student_number',
            'gender',
            'birth_date',
            'status',
        ]);
    }

    private function syncParents(Student $student, array $validated): void
    {
        $parentIds = collect($validated['parent_ids'] ?? [])
            ->filter()
            ->unique()
            ->values();

        $parentRelations = $validated['parent_relations'] ?? [];

        $syncData = $parentIds
            ->mapWithKeys(function ($parentId) use ($parentRelations) {
                return [
                    (int) $parentId => [
                        'relation' => $parentRelations[$parentId] ?? null,
                    ],
                ];
            })
            ->all();

        $student->parents()->sync($syncData);
    }

    public function export(): StreamedResponse
    {
        $students = Student::query()
            ->with([
                'user',
                'classRoom',
                'teacher.user',
                'parents.user',
            ])
            ->get();

        $fileName = 'daftar-santri-' . now()->format('Ymd-His') . '.xlsx';
        
        $tempFile = @tempnam(sys_get_temp_dir(), 'export_xlsx');
        if (! $tempFile) {
            abort(500, 'Gagal membuat file sementara.');
        }

        $headers = [
            'Nama Santri',
            'Nomor Induk',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Status',
            'Kelas',
            'Username Guru',
            'Username Santri',
            'Username Orangtua',
            'Hubungan Orangtua',
        ];

        $data = [];
        foreach ($students as $student) {
            $data[] = [
                $student->name,
                $student->student_number,
                $student->gender,
                $student->birth_date ? $student->birth_date->toDateString() : '',
                $student->status,
                $student->classRoom?->name,
                $student->teacher?->user?->username,
                $student->user?->username,
                $student->parents->map(fn($p) => $p->user?->username)->implode(','),
                $student->parents->map(fn($p) => $p->pivot?->relation)->implode(','),
            ];
        }

        \App\Services\SimpleXlsxWriter::write($tempFile, $headers, $data);

        return response()->streamDownload(function () use ($tempFile) {
            readfile($tempFile);
            @unlink($tempFile);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:4096',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = [];

        if ($extension === 'xlsx') {
            try {
                $rows = \App\Services\SimpleXlsxReader::read($filePath);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Gagal membaca berkas Excel: ' . $e->getMessage());
            }
        } else {
            // Fallback to CSV parser
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return redirect()->back()->with('error', 'Gagal membuka berkas.');
            }

            // Read the first line to check for sep=; instruction (Excel helper)
            $firstLine = fgets($handle);
            $cleanFirstLine = $firstLine !== false ? preg_replace('/[\x{FEFF}\x{200B}]/u', '', $firstLine) : '';
            
            $separator = ',';
            if ($firstLine !== false) {
                $trimmedFirstLine = trim(strtolower($cleanFirstLine));
                if (str_starts_with($trimmedFirstLine, 'sep=')) {
                    $parts = explode('=', $trimmedFirstLine);
                    if (isset($parts[1]) && ! empty(trim($parts[1]))) {
                        $separator = trim($parts[1])[0];
                    }
                } else {
                    rewind($handle);
                    // Auto-detect separator if not specified
                    $cleanFirstLineForDetect = preg_replace('/[\x{FEFF}\x{200B}]/u', '', $firstLine);
                    if (str_contains($cleanFirstLineForDetect, ';') && ! str_contains($cleanFirstLineForDetect, ',')) {
                        $separator = ';';
                    }
                }
            }

            while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Berkas kosong atau tidak valid.');
        }

        // The first row is the header
        $header = array_shift($rows);

        // Clean headers (remove BOM or spaces)
        $header = array_map(function ($h) {
            $h = preg_replace('/[\x{FEFF}\x{200B}]/u', '', $h); // strip BOM
            $h = preg_replace('/\s+/', ' ', $h); // normalize spaces
            return trim(strtolower($h));
        }, $header);

        // Map header column names to indexes
        $map = [
            'nama_santri' => array_search('nama santri', $header),
            'nomor_induk' => array_search('nomor induk', $header),
            'jenis_kelamin' => array_search('jenis kelamin', $header),
            'tanggal_lahir' => array_search('tanggal lahir', $header),
            'status' => array_search('status', $header),
            'kelas' => array_search('kelas', $header),
            'username_guru' => array_search('username guru', $header),
            'username_santri' => array_search('username santri', $header),
            'username_orangtua' => array_search('username orangtua', $header),
            'hubungan_orangtua' => array_search('hubungan orangtua', $header),
        ];

        // English fallbacks / Legacy Email mappings
        if ($map['nama_santri'] === false) $map['nama_santri'] = array_search('name', $header);
        if ($map['nomor_induk'] === false) $map['nomor_induk'] = array_search('student_number', $header);
        if ($map['jenis_kelamin'] === false) $map['jenis_kelamin'] = array_search('gender', $header);
        if ($map['tanggal_lahir'] === false) $map['tanggal_lahir'] = array_search('birth_date', $header);
        if ($map['status'] === false) $map['status'] = array_search('status', $header);
        if ($map['kelas'] === false) $map['kelas'] = array_search('class_room', $header);
        
        if ($map['username_guru'] === false) $map['username_guru'] = array_search('teacher_username', $header);
        if ($map['username_guru'] === false) $map['username_guru'] = array_search('email guru', $header);
        if ($map['username_guru'] === false) $map['username_guru'] = array_search('teacher_email', $header);
        
        if ($map['username_santri'] === false) $map['username_santri'] = array_search('student_username', $header);
        if ($map['username_santri'] === false) $map['username_santri'] = array_search('email santri', $header);
        if ($map['username_santri'] === false) $map['username_santri'] = array_search('student_email', $header);
        
        if ($map['username_orangtua'] === false) $map['username_orangtua'] = array_search('parent_usernames', $header);
        if ($map['username_orangtua'] === false) $map['username_orangtua'] = array_search('email orangtua', $header);
        if ($map['username_orangtua'] === false) $map['username_orangtua'] = array_search('parent_emails', $header);
        
        if ($map['hubungan_orangtua'] === false) $map['hubungan_orangtua'] = array_search('parent_relations', $header);

        if ($map['nama_santri'] === false) {
            return redirect()->back()->with('error', 'Format berkas tidak valid. Harus memiliki kolom "Nama Santri".');
        }

        $importedCount = 0;
        $updatedCount = 0;

        $defaultPasswordHash = Hash::make('password123');

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                if (empty($row) || count($row) < 1 || is_null($row[0])) {
                    continue;
                }

                $name = $row[$map['nama_santri']] ?? '';
                if (empty(trim($name))) {
                    continue;
                }

                $studentNumber = isset($map['nomor_induk']) && $map['nomor_induk'] !== false ? trim($row[$map['nomor_induk']] ?? '') : null;
                $gender = isset($map['jenis_kelamin']) && $map['jenis_kelamin'] !== false ? strtolower(trim($row[$map['jenis_kelamin']] ?? '')) : 'male';
                if (! in_array($gender, ['male', 'female'])) {
                    $gender = 'male';
                }

                $birthDate = isset($map['tanggal_lahir']) && $map['tanggal_lahir'] !== false ? trim($row[$map['tanggal_lahir']] ?? '') : null;
                if (! empty($birthDate)) {
                    if (is_numeric($birthDate) && (int)$birthDate > 10000 && (int)$birthDate < 100000) {
                        try {
                            $birthDate = \Carbon\Carbon::createFromTimestamp(($birthDate - 25569) * 86400)->toDateString();
                        } catch (\Exception $e) {
                            $birthDate = null;
                        }
                    } else {
                        try {
                            $birthDate = \Carbon\Carbon::parse($birthDate)->toDateString();
                        } catch (\Exception $e) {
                            $birthDate = null;
                        }
                    }
                } else {
                    $birthDate = null;
                }

                $status = isset($map['status']) && $map['status'] !== false ? strtolower(trim($row[$map['status']] ?? '')) : 'active';
                if (! in_array($status, ['active', 'inactive', 'graduated'])) {
                    $status = 'active';
                }

                $classRoomId = null;
                if (isset($map['kelas']) && $map['kelas'] !== false) {
                    $className = trim($row[$map['kelas']] ?? '');
                    if (! empty($className)) {
                        $classRoomId = ClassRoom::query()->where('name', $className)->value('id');
                    }
                }

                $teacherId = null;
                if (isset($map['username_guru']) && $map['username_guru'] !== false) {
                    $teacherUsername = trim($row[$map['username_guru']] ?? '');
                    if (str_contains($teacherUsername, '@')) {
                        $teacherUsername = explode('@', $teacherUsername)[0];
                    }
                    if (! empty($teacherUsername)) {
                        $teacherId = TeacherProfile::query()
                            ->whereHas('user', function ($q) use ($teacherUsername) {
                                $q->where('username', $teacherUsername);
                            })
                            ->value('id');
                    }
                }

                $studentUserId = null;
                if (isset($map['username_santri']) && $map['username_santri'] !== false) {
                    $studentUsername = trim($row[$map['username_santri']] ?? '');
                    if (str_contains($studentUsername, '@')) {
                        $studentUsername = explode('@', $studentUsername)[0];
                    }
                    if (! empty($studentUsername)) {
                        $studentUser = User::query()->where('username', $studentUsername)->first();
                        if (! $studentUser) {
                            $studentRole = Role::where('name', 'student')->first();
                            if ($studentRole) {
                                $studentUser = User::create([
                                    'role_id' => $studentRole->id,
                                    'name' => $name,
                                    'username' => $studentUsername,
                                    'password' => $defaultPasswordHash,
                                    'plain_password' => 'password123',
                                    'status' => 'active',
                                ]);
                            }
                        }
                        if ($studentUser) {
                            $studentUserId = $studentUser->id;
                        }
                    }
                }

                $student = null;
                if (! empty($studentNumber)) {
                    $student = Student::query()->where('student_number', $studentNumber)->first();
                }

                if (! $student && ! empty($studentUserId)) {
                    $student = Student::query()->where('user_id', $studentUserId)->first();
                }

                if ($student) {
                    // Update only non-null values from import to prevent overwriting existing data with null
                    $updatePayload = [];
                    if (! empty($name)) $updatePayload['name'] = $name;
                    if (! empty($gender)) $updatePayload['gender'] = $gender;
                    if (! empty($status)) $updatePayload['status'] = $status;
                    if (! empty($studentNumber)) $updatePayload['student_number'] = $studentNumber;

                    if (! is_null($birthDate)) $updatePayload['birth_date'] = $birthDate;
                    if (! is_null($classRoomId)) $updatePayload['class_room_id'] = $classRoomId;
                    if (! is_null($teacherId)) $updatePayload['teacher_id'] = $teacherId;
                    if (! is_null($studentUserId)) $updatePayload['user_id'] = $studentUserId;

                    $student->update($updatePayload);
                    $updatedCount++;
                } else {
                    $payload = [
                        'name' => $name,
                        'gender' => $gender,
                        'birth_date' => $birthDate,
                        'status' => $status,
                        'class_room_id' => $classRoomId,
                        'teacher_id' => $teacherId,
                        'user_id' => $studentUserId,
                        'student_number' => $studentNumber,
                    ];
                    $student = Student::create($payload);
                    $importedCount++;
                }

                // Handle Parents sync
                if (isset($map['username_orangtua']) && $map['username_orangtua'] !== false) {
                    $parentUsernamesStr = trim($row[$map['username_orangtua']] ?? '');
                    $parentRelationsStr = isset($map['hubungan_orangtua']) && $map['hubungan_orangtua'] !== false ? trim($row[$map['hubungan_orangtua']] ?? '') : '';

                    if (! empty($parentUsernamesStr)) {
                        $usernames = preg_split('/[;,]/', $parentUsernamesStr);
                        $relations = preg_split('/[;,]/', $parentRelationsStr);

                        $syncData = [];

                        foreach ($usernames as $index => $username) {
                            $username = trim($username);
                            if (str_contains($username, '@')) {
                                $username = explode('@', $username)[0];
                            }
                            if (empty($username)) {
                                continue;
                            }

                            // Look up ParentProfile, or auto-create it if user exists with parent role
                            $parentProfile = ParentProfile::query()
                                ->whereHas('user', function ($q) use ($username) {
                                    $q->where('username', $username);
                                })
                                ->first();

                            if (! $parentProfile) {
                                // Try to find the User and create a ParentProfile automatically
                                $parentUser = User::query()
                                    ->where('username', $username)
                                    ->whereHas('role', function ($q) {
                                        $q->where('name', 'parent');
                                    })
                                    ->first();

                                if (! $parentUser) {
                                    $existingUser = User::where('username', $username)->first();
                                    if (! $existingUser) {
                                        $parentRole = Role::where('name', 'parent')->first();
                                        if ($parentRole) {
                                            $parentUser = User::create([
                                                'role_id' => $parentRole->id,
                                                'name' => ucwords(str_replace(['.', '_', '-'], ' ', $username)),
                                                'username' => $username,
                                                'password' => $defaultPasswordHash,
                                                'plain_password' => 'password123',
                                                'status' => 'active',
                                            ]);
                                        }
                                    } else if ($existingUser->hasRole('parent')) {
                                        $parentUser = $existingUser;
                                    }
                                }

                                if ($parentUser) {
                                    $parentProfile = ParentProfile::create([
                                        'user_id' => $parentUser->id,
                                        'phone'   => null,
                                        'address' => null,
                                    ]);
                                }
                            }

                            if ($parentProfile) {
                                $relation = isset($relations[$index]) && trim($relations[$index]) !== ''
                                    ? trim($relations[$index])
                                    : 'Wali';
                                $syncData[$parentProfile->id] = ['relation' => $relation];
                            }
                        }

                        if (! empty($syncData)) {
                            $student->parents()->sync($syncData);
                        }
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengimpor data: ' . $e->getMessage());
        }

        return redirect()
            ->route('students.index')
            ->with('success', "Impor selesai. {$importedCount} data santri ditambahkan, {$updatedCount} data diperbarui.");
    }
}