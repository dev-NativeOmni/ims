<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParentRequest;
use App\Http\Requests\UpdateParentRequest;
use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\SimpleXlsxReader;
use App\Services\SimpleXlsxWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParentController extends Controller
{
    public function index(Request $request): View
    {
        $parents = ParentProfile::query()
            ->with(['user', 'students'])
            ->withCount('students')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('parents.index', compact('parents'));
    }

    public function create(): View
    {
        return view('parents.create');
    }

    public function store(StoreParentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $parentRole = Role::where('name', 'parent')->firstOrFail();

            $user = User::create([
                'role_id' => $parentRole->id,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'plain_password' => $validated['password'],
                'status' => $validated['status'],
            ]);

            ParentProfile::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);
        });

        return redirect()
            ->route('parents.index')
            ->with('success', 'Data orangtua/wali berhasil ditambahkan.');
    }

    public function show(ParentProfile $parent): View
    {
        $parent->load([
            'user',
            'students.classRoom.program',
            'students.teacher.user',
        ])->loadCount('students');

        return view('parents.show', compact('parent'));
    }

    public function edit(ParentProfile $parent): View
    {
        $parent->load('user');

        return view('parents.edit', compact('parent'));
    }

    public function update(UpdateParentRequest $request, ParentProfile $parent): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $parent) {
            $userData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'status' => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
                $userData['plain_password'] = $validated['password'];
            }

            $parent->user()->update($userData);

            $parent->update([
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);
        });

        return redirect()
            ->route('parents.index')
            ->with('success', 'Data orangtua/wali berhasil diperbarui.');
    }

    public function destroy(ParentProfile $parent): RedirectResponse
    {
        if ($parent->students()->exists()) {
            return back()->with('error', 'Orangtua/wali tidak bisa dihapus karena masih terhubung dengan santri.');
        }

        DB::transaction(function () use ($parent) {
            $user = $parent->user;
            $parent->delete();
            if ($user) {
                $user->delete();
            }
        });

        return redirect()
            ->route('parents.index')
            ->with('success', 'Data orangtua/wali berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Excel Export
    // -------------------------------------------------------------------------
    public function export(): StreamedResponse
    {
        $parents = ParentProfile::query()
            ->with('user')
            ->withCount('students')
            ->orderBy('id')
            ->get();

        $headers = ['Nama', 'Username', 'Telepon', 'Alamat', 'Status', 'Jumlah Santri'];
        $data = [];

        foreach ($parents as $parent) {
            $data[] = [
                $parent->user?->name ?? '',
                $parent->user?->username ?? '',
                $parent->phone ?? '',
                $parent->address ?? '',
                $parent->user?->status ?? '',
                $parent->students_count,
            ];
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'parents_export_').'.xlsx';
        $fileName = 'orangtua_'.now()->format('Ymd_His').'.xlsx';

        SimpleXlsxWriter::write($tempFile, $headers, $data);

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

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        /** @var list<list<string|null>> $rows */
        $rows = [];

        if ($extension === 'xlsx') {
            try {
                $rows = SimpleXlsxReader::read($filePath);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Gagal membaca berkas Excel: '.$e->getMessage());
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
            'nama' => $col('nama') ?? $col('name'),
            'username' => $col('username'),
            'telepon' => $col('telepon') ?? $col('phone'),
            'alamat' => $col('alamat') ?? $col('address'),
            'status' => $col('status'),
        ];

        if ($map['nama'] === null || $map['username'] === null) {
            return redirect()->back()->with('error', 'Format tidak valid. Kolom "Nama" dan "Username" wajib ada.');
        }

        $imported = 0;
        $updated = 0;
        $parentRole = Role::where('name', 'parent')->first();

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $name = trim((string) ($row[$map['nama']] ?? ''));
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

                $telepon = $map['telepon'] !== null ? trim((string) ($row[$map['telepon']] ?? '')) : null;
                $alamat = $map['alamat'] !== null ? trim((string) ($row[$map['alamat']] ?? '')) : null;

                $existingUser = User::where('username', $username)->first();

                if ($existingUser) {
                    $existingUser->update([
                        'name' => $name,
                        'status' => $status,
                    ]);

                    $profile = $existingUser->parentProfile;
                    if ($profile) {
                        $profilePayload = [];
                        if ($telepon !== null && $telepon !== '') {
                            $profilePayload['phone'] = $telepon;
                        }
                        if ($alamat !== null && $alamat !== '') {
                            $profilePayload['address'] = $alamat;
                        }
                        if (! empty($profilePayload)) {
                            $profile->update($profilePayload);
                        }
                    } else {
                        if ($parentRole && $existingUser->role_id === $parentRole->id) {
                            ParentProfile::create([
                                'user_id' => $existingUser->id,
                                'phone' => $telepon ?: null,
                                'address' => $alamat ?: null,
                            ]);
                        }
                    }
                    $updated++;
                } else {
                    if (! $parentRole) {
                        continue;
                    }
                    $newUser = User::create([
                        'role_id' => $parentRole->id,
                        'name' => $name,
                        'username' => $username,
                        'password' => Hash::make('password123'),
                        'plain_password' => 'password123',
                        'status' => $status,
                    ]);
                    ParentProfile::create([
                        'user_id' => $newUser->id,
                        'phone' => $telepon ?: null,
                        'address' => $alamat ?: null,
                    ]);
                    $imported++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal mengimpor: '.$e->getMessage());
        }

        return redirect()->route('parents.index')
            ->with('success', "Impor selesai. {$imported} orangtua ditambahkan, {$updated} diperbarui.");
    }
}
