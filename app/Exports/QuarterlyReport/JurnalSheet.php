<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Jurnal": jurnal tatap muka per bulan per halaqah, sama dengan tab
 * "Jurnal" pada halaman Laporan Triwulan.
 */
class JurnalSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly array $halaqahData) {}

    public function title(): string
    {
        return 'Jurnal';
    }

    public function headings(): array
    {
        return ['Halaqah (Musyrif)', 'Bulan', 'Tanggal / Pekan', 'Materi', 'Jumlah Murid Hadir', 'Paraf'];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->halaqahData as $halaqah) {
            foreach ($halaqah['monthly'] as $month) {
                foreach ($month['jurnal'] as $entry) {
                    $rows[] = [
                        $halaqah['musyrif'],
                        $month['label'],
                        $entry['tanggal'],
                        $entry['materi'],
                        $entry['jumlah_murid'],
                        $entry['paraf'],
                    ];
                }
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
