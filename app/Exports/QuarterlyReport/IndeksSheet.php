<?php

namespace App\Exports\QuarterlyReport;

use App\Models\Surah;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Indeks": daftar referensi 114 surah Al-Qur'an (statis, sama untuk semua
 * laporan) -- dipakai musyrif sebagai acuan nomor surah & jumlah ayat, sama seperti
 * sheet "INDEKS" di file template sekolah.
 */
class IndeksSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function title(): string
    {
        return 'Indeks';
    }

    public function headings(): array
    {
        return ['No', 'Surah', 'Bahasa Arab', 'Jumlah Ayat', 'Juz'];
    }

    public function array(): array
    {
        return Surah::getAllCached()->map(fn (Surah $surah) => [
            $surah->number,
            $surah->name_latin,
            $surah->name_ar,
            $surah->total_ayah,
            $surah->juz_start === $surah->juz_end
                ? (string) $surah->juz_start
                : "{$surah->juz_start}-{$surah->juz_end}",
        ])->all();
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
