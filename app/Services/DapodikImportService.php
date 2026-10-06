<?php

namespace App\Services;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Impor identitas murid dari Excel ekspor Dapodik (mis. "ROMBEL DAPODIK ALL JENJANG").
 *
 * Kolom dikenali dari judulnya (Nama, NIPD/NIS, NISN, Tempat Lahir, Tanggal Lahir, Rombel), di sheet
 * mana pun; baris yang sama di beberapa sheet hanya dihitung sekali. NIK & kolom lain tidak dibaca.
 * Murid dicocokkan berurutan: NISN tersimpan, nomor induk aplikasi = NIPD/NISN, nama + tanggal lahir,
 * lalu nama saja (hanya bila unik). Yang diperbarui: dapodik_nis/nisn/rombel, birth_place, birth_date.
 */
class DapodikImportService
{
    /** Judul kolom (huruf kecil, tanpa tanda baca) => kunci internal. */
    private const HEADERS = [
        'nama' => 'name', 'nama peserta didik' => 'name', 'nama siswa' => 'name',
        'nipd' => 'nis', 'nis' => 'nis', 'nomor induk' => 'nis',
        'nisn' => 'nisn',
        'tempat lahir' => 'birth_place',
        'tanggal lahir' => 'birth_date', 'tgl lahir' => 'birth_date',
        'rombel saat ini' => 'rombel', 'rombel' => 'rombel', 'rombongan belajar' => 'rombel', 'kelas' => 'rombel',
    ];

    public const FIELDS = ['dapodik_nis', 'dapodik_nisn', 'dapodik_rombel', 'birth_place', 'birth_date'];

    /**
     * Baca baris murid dari file Excel.
     *
     * @return array<int, array{name: string, nis: ?string, nisn: ?string, birth_place: ?string, birth_date: ?string, rombel: ?string}>
     *
     * @throws \RuntimeException bila kolom Nama & NISN/NIPD tidak ditemukan
     */
    public function parse(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);

        $rows = [];
        foreach ($book->getAllSheets() as $sheet) {
            $columns = null;
            foreach ($sheet->toArray(null, false, false, false) as $cells) {
                if ($columns === null) {
                    $columns = $this->headerColumns($cells);

                    continue;
                }

                $row = $this->row($cells, $columns);
                if ($row === null) {
                    continue;
                }
                $key = $row['nisn'] ?: 'nis:'.$row['nis'];
                $rows[$key] ??= $row;
            }
        }

        if ($rows === []) {
            throw new \RuntimeException('Kolom Nama dan NISN/NIPD tidak ditemukan, atau file tidak berisi data murid.');
        }

        return array_values($rows);
    }

    /**
     * @return array<string, int>|null kunci internal => indeks kolom, null bila baris ini bukan judul
     */
    private function headerColumns(array $cells): ?array
    {
        $columns = [];
        foreach ($cells as $index => $cell) {
            $label = Str::of((string) $cell)->lower()->replaceMatches('/[^a-z ]/', ' ')->squish()->toString();
            if (isset(self::HEADERS[$label]) && ! isset($columns[self::HEADERS[$label]])) {
                $columns[self::HEADERS[$label]] = $index;
            }
        }

        return isset($columns['name']) && (isset($columns['nisn']) || isset($columns['nis'])) ? $columns : null;
    }

    private function row(array $cells, array $columns): ?array
    {
        $value = fn (string $key) => isset($columns[$key]) ? $cells[$columns[$key]] ?? null : null;
        $text = fn (string $key) => ($v = trim((string) $value($key))) === '' ? null : $v;

        $name = $text('name');
        $nisn = $this->nisn($value('nisn'));
        $nis = $text('nis');
        if ($name === null || ($nisn === null && $nis === null)) {
            return null;
        }

        return [
            'name' => Str::squish($name),
            'nis' => $nis,
            'nisn' => $nisn,
            'birth_place' => $text('birth_place') ? Str::squish($text('birth_place')) : null,
            'birth_date' => $this->date($value('birth_date')),
            'rombel' => $text('rombel') ? Str::squish($text('rombel')) : null,
        ];
    }

    /** NISN 10 digit; angka dari Excel yang kehilangan nol di depan dilengkapi lagi. */
    private function nisn(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', is_float($value) ? sprintf('%.0f', $value) : (string) $value);

        return $digits === '' ? null : str_pad($digits, 10, '0', STR_PAD_LEFT);
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            }
            $value = trim((string) $value);
            foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'] as $format) {
                $date = Carbon::createFromFormat('!'.$format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->toDateString();
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Cocokkan baris Dapodik ke murid aplikasi.
     *
     * @return array{matched: array<int, array>, unmatched: array<int, array>, missing: Collection<int, Student>}
     *                                                                                                            matched: baris + student_id + method + changes; unmatched: baris Dapodik tanpa pasangan;
     *                                                                                                            missing: murid aktif aplikasi yang tidak ada di file
     */
    public function match(array $rows): array
    {
        $students = Student::query()->with('classRoom')->get();
        $byNisn = $students->filter(fn ($s) => $s->dapodik_nisn)->keyBy('dapodik_nisn');
        $byNumber = $students->filter(fn ($s) => $s->student_number)->groupBy(fn ($s) => self::numberKey($s->student_number));
        $byNameDate = $students->filter(fn ($s) => $s->birth_date)->groupBy(fn ($s) => self::nameKey($s->name).'|'.$s->birth_date->toDateString());
        $byName = $students->groupBy(fn ($s) => self::nameKey($s->name));
        $unique = fn ($group) => $group && $group->count() === 1 ? $group->first() : null;

        $matched = [];
        $unmatched = [];
        $used = [];
        foreach ($rows as $index => $row) {
            $candidates = [
                'NISN' => $row['nisn'] ? $byNisn->get($row['nisn']) : null,
                'NIPD' => $this->byNumber($byNumber, $row, $unique),
                'Nama & tgl lahir' => $row['birth_date'] ? $unique($byNameDate->get(self::nameKey($row['name']).'|'.$row['birth_date'])) : null,
                'Nama' => $unique($byName->get(self::nameKey($row['name']))),
            ];

            $student = null;
            $method = null;
            foreach ($candidates as $label => $candidate) {
                if ($candidate && ! isset($used[$candidate->id])) {
                    [$student, $method] = [$candidate, $label];
                    break;
                }
            }

            if (! $student) {
                $unmatched[] = $row + ['index' => $index];

                continue;
            }

            $used[$student->id] = true;
            $matched[] = $row + ['index' => $index, 'student_id' => $student->id, 'method' => $method, 'changes' => $this->changes($student, $row), 'student' => $student];
        }

        $missing = $students->where('status', 'active')->reject(fn ($s) => isset($used[$s->id]))->sortBy('name')->values();

        return compact('matched', 'unmatched', 'missing');
    }

    private function byNumber(Collection $byNumber, array $row, \Closure $unique): ?Student
    {
        $nis = (string) $row['nis'];
        $keys = array_filter([$nis, Str::afterLast($nis, '-'), $row['nisn']]);
        foreach (array_unique(array_map(fn ($k) => self::numberKey($k), $keys)) as $key) {
            if ($key !== '' && ($student = $unique($byNumber->get($key)))) {
                return $student;
            }
        }

        return null;
    }

    /**
     * Perubahan yang akan disimpan: kolom => [lama, baru] (hanya yang berbeda dan ada isinya di Dapodik).
     *
     * @return array<string, array{0: ?string, 1: string}>
     */
    public function changes(Student $student, array $row): array
    {
        $new = [
            'dapodik_nis' => $row['nis'],
            'dapodik_nisn' => $row['nisn'],
            'dapodik_rombel' => $row['rombel'],
            'birth_place' => $row['birth_place'],
            'birth_date' => $row['birth_date'],
        ];
        $old = fn (string $field) => $field === 'birth_date' ? $student->birth_date?->toDateString() : $student->{$field};

        return collect($new)
            ->filter(fn ($value, $field) => $value !== null && (string) $old($field) !== $value)
            ->map(fn ($value, $field) => [$old($field), $value])
            ->all();
    }

    /**
     * Simpan data Dapodik ke murid yang dipilih.
     *
     * @param  array<int, array{student_id: int, row: array}>  $assignments
     * @return int jumlah murid yang diperbarui
     */
    public function apply(array $assignments): int
    {
        return DB::transaction(function () use ($assignments) {
            $updated = 0;
            $students = Student::query()->whereIn('id', array_column($assignments, 'student_id'))->get()->keyBy('id');
            foreach ($assignments as $assignment) {
                $student = $students->get($assignment['student_id']);
                if (! $student) {
                    continue;
                }
                $changes = $this->changes($student, $assignment['row']);
                $student->fill(array_map(fn ($change) => $change[1], $changes) + ['dapodik_synced_at' => now()])->save();
                $updated += $changes !== [] ? 1 : 0;
            }

            return $updated;
        });
    }

    private static function nameKey(string $name): string
    {
        return Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z ]/', ' ')->squish()->toString();
    }

    private static function numberKey(string $number): string
    {
        return strtolower(preg_replace('/[^0-9a-z]/i', '', $number));
    }
}
