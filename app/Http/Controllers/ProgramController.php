<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgramController extends Controller
{
    public function index(): View
    {
        $programs = Program::query()
            ->withCount('classRooms')
            ->latest()
            ->paginate(10);

        return view('programs.index', compact('programs'));
    }

    public function create(): View
    {
        return view('programs.create');
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        Program::create($request->validated());

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program berhasil ditambahkan.');
    }

    public function show(Program $program): View
    {
        $program->loadCount('classRooms');

        $classRooms = $program->classRooms()
            ->withCount('students')
            ->latest()
            ->paginate(10);

        return view('programs.show', compact('program', 'classRooms'));
    }

    public function edit(Program $program): View
    {
        return view('programs.edit', compact('program'));
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->validated());

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program berhasil diperbarui.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        if ($program->classRooms()->exists()) {
            return back()->with('error', 'Program tidak bisa dihapus karena masih memiliki kelas.');
        }

        $program->delete();

        return redirect()
            ->route('programs.index')
            ->with('success', 'Program berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Excel Export
    // -------------------------------------------------------------------------
    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $programs = Program::query()->withCount('classRooms')->orderBy('name')->get();

        $headers = ['Nama Program', 'Deskripsi', 'Status', 'Jumlah Kelas'];
        $data    = [];

        foreach ($programs as $program) {
            $data[] = [
                $program->name,
                $program->description ?? '',
                $program->status,
                $program->class_rooms_count,
            ];
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'programs_export_') . '.xlsx';
        $fileName = 'programs_' . now()->format('Ymd_His') . '.xlsx';

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
            'nama'      => $col('nama program') ?? $col('nama') ?? $col('name'),
            'deskripsi' => $col('deskripsi')    ?? $col('description'),
            'status'    => $col('status'),
        ];

        if ($map['nama'] === null) {
            return redirect()->back()->with('error', 'Format tidak valid. Kolom "Nama Program" wajib ada.');
        }

        $imported = 0;
        $updated  = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $name = trim((string) ($row[$map['nama']] ?? ''));
                if ($name === '') {
                    continue;
                }

                $status = $map['status'] !== null
                    ? strtolower(trim((string) ($row[$map['status']] ?? '')))
                    : 'active';
                if (! in_array($status, ['active', 'inactive'], true)) {
                    $status = 'active';
                }

                $deskripsi = $map['deskripsi'] !== null ? trim((string) ($row[$map['deskripsi']] ?? '')) : null;

                $existing = Program::where('name', $name)->first();
                if ($existing) {
                    $existing->update([
                        'description' => $deskripsi ?: $existing->description,
                        'status'      => $status,
                    ]);
                    $updated++;
                } else {
                    Program::create([
                        'name'        => $name,
                        'description' => $deskripsi,
                        'status'      => $status,
                    ]);
                    $imported++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengimpor: ' . $e->getMessage());
        }

        return redirect()->route('programs.index')
            ->with('success', "Impor selesai. {$imported} program ditambahkan, {$updated} diperbarui.");
    }
}