<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRoomRequest;
use App\Http\Requests\UpdateClassRoomRequest;
use App\Models\ClassRoom;
use App\Models\Program;
use App\Services\SimpleXlsxReader;
use App\Services\SimpleXlsxWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassRoomController extends Controller
{
    public function index(Request $request): View
    {
        $programs = Program::query()
            ->orderBy('name')
            ->get();

        $classRooms = ClassRoom::query()
            ->with('program')
            ->withCount('students')
            ->when($request->filled('program_id'), function ($query) use ($request) {
                $query->where('program_id', $request->integer('program_id'));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('class-rooms.index', compact('classRooms', 'programs'));
    }

    public function create(): View
    {
        $programs = Program::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('class-rooms.create', compact('programs'));
    }

    public function store(StoreClassRoomRequest $request): RedirectResponse
    {
        ClassRoom::create($request->validated());

        return redirect()
            ->route('class-rooms.index')
            ->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function show(ClassRoom $classRoom): View
    {
        $classRoom->load('program')->loadCount('students');

        $students = $classRoom->students()
            ->latest()
            ->paginate(10);

        return view('class-rooms.show', compact('classRoom', 'students'));
    }

    public function edit(ClassRoom $classRoom): View
    {
        $programs = Program::query()
            ->orderBy('name')
            ->get();

        return view('class-rooms.edit', compact('classRoom', 'programs'));
    }

    public function update(UpdateClassRoomRequest $request, ClassRoom $classRoom): RedirectResponse
    {
        $classRoom->update($request->validated());

        return redirect()
            ->route('class-rooms.index')
            ->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        if ($classRoom->students()->exists()) {
            return back()->with('error', 'Kelas tidak bisa dihapus karena masih memiliki santri.');
        }

        $classRoom->delete();

        return redirect()
            ->route('class-rooms.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Excel Export
    // -------------------------------------------------------------------------
    public function export(): StreamedResponse
    {
        $classRooms = ClassRoom::query()
            ->with('program')
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $headers = ['Nama Kelas', 'Level', 'Program', 'Jumlah Santri'];
        $data = [];

        foreach ($classRooms as $classRoom) {
            $data[] = [
                $classRoom->name,
                $classRoom->level ?? '',
                $classRoom->program?->name ?? '',
                $classRoom->students_count,
            ];
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'classrooms_export_').'.xlsx';
        $fileName = 'kelas_'.now()->format('Ymd_His').'.xlsx';

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
            'nama' => $col('nama kelas') ?? $col('nama') ?? $col('name'),
            'level' => $col('level'),
            'program' => $col('program') ?? $col('nama program'),
        ];

        if ($map['nama'] === null) {
            return redirect()->back()->with('error', 'Format tidak valid. Kolom "Nama Kelas" wajib ada.');
        }

        $imported = 0;
        $updated = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $name = trim((string) ($row[$map['nama']] ?? ''));
                if ($name === '') {
                    continue;
                }

                $level = $map['level'] !== null ? trim((string) ($row[$map['level']] ?? '')) : null;

                $programId = null;
                if ($map['program'] !== null) {
                    $programName = trim((string) ($row[$map['program']] ?? ''));
                    if ($programName !== '') {
                        $programId = Program::where('name', $programName)->value('id');
                    }
                }

                $existing = ClassRoom::where('name', $name)
                    ->when($programId, fn ($q) => $q->where('program_id', $programId))
                    ->first();

                if ($existing) {
                    $payload = [];
                    if ($level !== null && $level !== '') {
                        $payload['level'] = $level;
                    }
                    if ($programId !== null) {
                        $payload['program_id'] = $programId;
                    }
                    if (! empty($payload)) {
                        $existing->update($payload);
                    }
                    $updated++;
                } else {
                    ClassRoom::create([
                        'name' => $name,
                        'level' => $level ?: null,
                        'program_id' => $programId,
                    ]);
                    $imported++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal mengimpor: '.$e->getMessage());
        }

        return redirect()->route('class-rooms.index')
            ->with('success', "Impor selesai. {$imported} kelas ditambahkan, {$updated} diperbarui.");
    }
}
