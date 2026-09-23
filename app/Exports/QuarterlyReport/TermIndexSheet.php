<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Term-Indeks": rekap akhir triwulan per murid, sama persis dengan tabel
 * di tab "Term / Indeks (DNS)" pada halaman Laporan Triwulan.
 */
class TermIndexSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly array $halaqahData) {}

    public function title(): string
    {
        return 'Term-Indeks';
    }

    public function headings(): array
    {
        return [
            'Kelas', 'Halaqah (Musyrif)', 'No', 'Nama Murid', 'Level',
            'Target Surah', 'Target Ayat', 'Capaian Surah', 'Capaian Ayat',
            'Capaian Baris', 'Target Baris', 'Ketercapaian',
            'Alpa', 'Izin', 'Sakit', 'Pelanggaran',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->halaqahData as $halaqah) {
            $no = 1;
            foreach ($halaqah['term_records'] as $row) {
                $rows[] = [
                    $halaqah['class_room_name'] ?? '-',
                    $halaqah['musyrif'],
                    $no++,
                    $row['name'],
                    $row['level'],
                    $row['target_surah'],
                    $row['target_ayat'],
                    $row['capaian_surah'],
                    $row['capaian_ayat'],
                    $row['total_lines'],
                    $row['target_lines'],
                    $row['is_tuntas'] ? 'Tuntas' : 'Tidak Tuntas',
                    $row['alpa'],
                    $row['izin'],
                    $row['sakit'],
                    $row['pelanggaran'],
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
