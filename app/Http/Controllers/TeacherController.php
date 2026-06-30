<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $teachers = TeacherProfile::query()
            ->with('user')
            ->withCount('students')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('employee_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('teachers.index', compact('teachers'));
    }

    public function create(): View
    {
        return view('teachers.create');
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $teacherRole = Role::where('name', 'teacher')->firstOrFail();

            $user = User::create([
                'role_id'        => $teacherRole->id,
                'name'           => $validated['name'],
                'username'       => $validated['username'],
                'password'       => Hash::make($validated['password']),
                'plain_password' => $validated['password'],
                'status'         => $validated['status'],
            ]);

            TeacherProfile::create([
                'user_id'         => $user->id,
                'employee_number' => $validated['employee_number'] ?? null,
                'phone'           => $validated['phone'] ?? null,
            ]);
        });

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function show(TeacherProfile $teacher): View
    {
        $teacher->load([
            'user',
            'students.classRoom.program',
        ])->loadCount('students');

        return view('teachers.show', compact('teacher'));
    }

    public function edit(TeacherProfile $teacher): View
    {
        $teacher->load('user');

        return view('teachers.edit', compact('teacher'));
    }

    public function update(UpdateTeacherRequest $request, TeacherProfile $teacher): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $teacher) {
            $userData = [
                'name'     => $validated['name'],
                'username' => $validated['username'],
                'status'   => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                $userData['password']       = Hash::make($validated['password']);
                $userData['plain_password'] = $validated['password'];
            }

            $teacher->user()->update($userData);

            $teacher->update([
                'employee_number' => $validated['employee_number'] ?? null,
                'phone'           => $validated['phone'] ?? null,
            ]);
        });

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(TeacherProfile $teacher): RedirectResponse
    {
        if ($teacher->students()->exists()) {
            return back()->with('error', 'Guru tidak bisa dihapus karena masih memiliki santri bimbingan.');
        }

        DB::transaction(function () use ($teacher) {
            $user = $teacher->user;
            $teacher->delete();
            if ($user) {
                $user->delete();
            }
        });

        return redirect()
            ->route('teachers.index')
            ->with('success', 'Data guru berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Excel Export
    // -------------------------------------------------------------------------
    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $teachers = TeacherProfile::query()
            ->with('user')
            ->withCount('students')
            ->orderBy('id')
            ->get();

        $headers = ['Nama', 'Username', 'Nomor Pegawai', 'Telepon', 'Status', 'Jumlah Santri'];
        $data    = [];

        foreach ($teachers as $teacher) {
            $data[] = [
                $teacher->user?->name            ?? '',
                $teacher->user?->username        ?? '',
                $teacher->employee_number        ?? '',
                $teacher->phone                  ?? '',
                $teacher->user?->status          ?? '',
                $teacher->students_count,
            ];
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'teachers_export_') . '.xlsx';
        $fileName = 'guru_' . now()->format('Ymd_His') . '.xlsx';

        \App\Services\SimpleXlsxWriter::write($tempFile, $headers, $data);

        return response()->streamDownload(function () use ($tempFile) {
            readfile($tempFile);
            @unlink($tempFile);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // -------------------------------------------------------------------------
    // Excel Import
    // -------------------------------------------------------------------------
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:4096',
        ]);

        $file      = $request->file('file');
        $filePath  = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        /** @var list<list<string|null>> $rows */
        $rows = [];

        if ($extension === 'xlsx') {
            try {
                $rows = \App\Services\SimpleXlsxReader::read($filePath);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Gagal membaca berkas Excel: ' . $e->getMessage());
            }
        } else {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return redirect()->back()->with('error', 'Gagal membuka berkas.');
            }
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Berkas kosong atau tidak valid.');
        }

        $header = array_shift($rows);
        $header = array_map(
            fn ($h): string => trim(strtolower((string) preg_replace('/[\x{FEFF}\x{200B}]/u', '', (string) $h))),
            (array) $header
        );

        $col = static function (string $name) use ($header): ?int {
            $v = array_search($name, $header, true);
            return $v !== false ? (int) $v : null;
        };

        /** @var array<string, int|null> $map */
        $map = [
            'nama'            => $col('nama')          ?? $col('name'),
            'username'        => $col('username'),
            'nomor_pegawai'   => $col('nomor pegawai') ?? $col('employee_number'),
            'telepon'         => $col('telepon')       ?? $col('phone'),
            'status'          => $col('status'),
        ];

        if ($map['nama'] === null || $map['username'] === null) {
            return redirect()->back()->with('error', 'Format tidak valid. Kolom "Nama" dan "Username" wajib ada.');
        }

        $imported = 0;
        $updated  = 0;

        $teacherRole = Role::where('name', 'teacher')->first();

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $name     = trim((string) ($row[$map['nama']]     ?? ''));
                $username = trim((string) ($row[$map['username']] ?? ''));

                if ($name === '' || $username === '') {
                    continue;
                }

                $status = $map['status'] !== null
                    ? strtolower(trim((string) ($row[$map['status']] ?? '')))
                    : 'active';
                if (! in_array($status, ['active', 'inactive'], true)) {
                    $status = 'active';
                }

                $employeeNumber = $map['nomor_pegawai'] !== null ? trim((string) ($row[$map['nomor_pegawai']] ?? '')) : null;
                $phone          = $map['telepon']       !== null ? trim((string) ($row[$map['telepon']]       ?? '')) : null;

                $existingUser = User::where('username', $username)->first();

                if ($existingUser) {
                    // Update user fields
                    $existingUser->update([
                        'name'   => $name,
                        'status' => $status,
                    ]);

                    // Update or create teacher profile
                    $profile = $existingUser->teacherProfile;
                    if ($profile) {
                        $profilePayload = [];
                        if ($employeeNumber !== null && $employeeNumber !== '') {
                            $profilePayload['employee_number'] = $employeeNumber;
                        }
                        if ($phone !== null && $phone !== '') {
                            $profilePayload['phone'] = $phone;
                        }
                        if (! empty($profilePayload)) {
                            $profile->update($profilePayload);
                        }
                    } else {
                        if ($teacherRole && $existingUser->role_id === $teacherRole->id) {
                            TeacherProfile::create([
                                'user_id'         => $existingUser->id,
                                'employee_number' => $employeeNumber ?: null,
                                'phone'           => $phone ?: null,
                            ]);
                        }
                    }
                    $updated++;
                } else {
                    if (! $teacherRole) {
                        continue;
                    }
                    $newUser = User::create([
                        'role_id'        => $teacherRole->id,
                        'name'           => $name,
                        'username'       => $username,
                        'password'       => Hash::make('password123'),
                        'plain_password' => 'password123',
                        'status'         => $status,
                    ]);
                    TeacherProfile::create([
                        'user_id'         => $newUser->id,
                        'employee_number' => $employeeNumber ?: null,
                        'phone'           => $phone ?: null,
                    ]);
                    $imported++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengimpor: ' . $e->getMessage());
        }

        return redirect()->route('teachers.index')
            ->with('success', "Impor selesai. {$imported} guru ditambahkan, {$updated} diperbarui.");
    }
}