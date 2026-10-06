<?php

namespace App\Http\Controllers;

use App\Services\DapodikImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Impor Dapodik: unggah Excel -> pratinjau kecocokan -> simpan. Isi file (tanpa NIK) disimpan di
 * sesi hanya sampai disimpan/dibatalkan; berkasnya sendiri tidak disimpan.
 */
class DapodikImportController extends Controller
{
    private const SESSION_KEY = 'dapodik_import_rows';

    public const FIELD_LABELS = [
        'dapodik_nis' => 'NIS', 'dapodik_nisn' => 'NISN', 'dapodik_rombel' => 'Rombel',
        'birth_place' => 'Tempat lahir', 'birth_date' => 'Tgl lahir',
    ];

    public function __construct(private readonly DapodikImportService $importer) {}

    public function index(): View
    {
        $rows = session(self::SESSION_KEY);
        $preview = $rows ? $this->importer->match($rows) : null;

        if ($preview) {
            // Pilihan manual untuk baris tak cocok: 15 murid aktif belum terpasang dengan nama paling mirip
            // (dibatasi supaya halaman tetap ringan bila banyak yang tidak cocok).
            foreach ($preview['unmatched'] as &$row) {
                $row['suggestions'] = $preview['missing']
                    ->sortByDesc(function ($student) use ($row) {
                        similar_text(strtolower($student->name), strtolower($row['name']), $percent);

                        return $percent;
                    })
                    ->take(15)
                    ->values();
            }
            unset($row);
        }

        return view('students.dapodik-import', [
            'preview' => $preview,
            'fieldLabels' => self::FIELD_LABELS,
        ]);
    }

    public function preview(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], ['file.mimes' => 'File harus berformat Excel (.xlsx / .xls).']);

        try {
            $rows = $this->importer->parse($request->file('file')->getRealPath());
        } catch (Throwable $e) {
            return back()->with('error', 'File tidak dapat dibaca: '.$e->getMessage());
        }

        session([self::SESSION_KEY => $rows]);

        return redirect()->route('students.dapodik.index');
    }

    public function apply(Request $request): RedirectResponse
    {
        $rows = session(self::SESSION_KEY);
        if (! $rows) {
            return redirect()->route('students.dapodik.index')->with('error', 'Pratinjau sudah tidak ada. Unggah ulang file Dapodik.');
        }

        $validated = $request->validate([
            'skip' => ['nullable', 'array'], 'skip.*' => ['integer'],
            'manual' => ['nullable', 'array'], 'manual.*' => ['nullable', 'integer', 'exists:students,id'],
        ]);
        $skip = array_flip($validated['skip'] ?? []);
        $preview = $this->importer->match($rows);

        // Pasangan otomatis dihitung ulang di server; dari form hanya baris yang dikecualikan & pilihan manual.
        $assignments = collect($preview['matched'])
            ->reject(fn ($match) => isset($skip[$match['index']]))
            ->map(fn ($match) => ['student_id' => $match['student_id'], 'row' => $match])
            ->values();
        $taken = $assignments->pluck('student_id')->flip();
        foreach ($preview['unmatched'] as $row) {
            $studentId = (int) ($validated['manual'][$row['index']] ?? 0);
            if ($studentId > 0 && ! $taken->has($studentId)) {
                $assignments->push(['student_id' => $studentId, 'row' => $row]);
                $taken->put($studentId, true);
            }
        }

        $updated = $this->importer->apply($assignments->all());
        session()->forget(self::SESSION_KEY);

        return redirect()->route('students.dapodik.index')->with(
            'success',
            "Data Dapodik tersimpan: {$updated} murid diperbarui dari {$assignments->count()} murid yang dicocokkan."
        );
    }

    public function cancel(): RedirectResponse
    {
        session()->forget(self::SESSION_KEY);

        return redirect()->route('students.dapodik.index');
    }
}
