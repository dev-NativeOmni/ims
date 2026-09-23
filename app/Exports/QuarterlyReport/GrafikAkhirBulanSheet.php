<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Grafik Akhir Bulan": ketuntasan capaian baris per murid per bulan
 * (Capaian Baris vs Target Baris), sama dengan grafik "Grafik Capaian Bulan ..."
 * di file template sekolah -- satu baris per murid per bulan, bukan grafik visual,
 * supaya bisa difilter/di-pivot di Excel.
 */
class GrafikAkhirBulanSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly array $halaqahData) {}

    public function title(): string
    {
        return 'Grafik Akhir Bulan';
    }

    public function headings(): array
    {
        return ['Kelas', 'Halaqah (Musyrif)', 'Bulan', 'Nama Murid', 'Capaian Baris', 'Target Baris', 'Keterangan'];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->halaqahData as $halaqah) {
            $className = $halaqah['class_room_name'] ?? '-';

            foreach ($halaqah['monthly'] as $month) {
                $records = $month['tahfizh_records'] ?: $month['reguler_records'];

                foreach ($records as $record) {
                    $rows[] = [
                        $className,
                        $halaqah['musyrif'],
                        $month['label'],
                        $record['name'],
                        $record['total_lines'],
                        $record['target_lines'],
                        $record['is_tuntas'] ? '✅ Tuntas' : '❌ Tidak Tuntas',
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
