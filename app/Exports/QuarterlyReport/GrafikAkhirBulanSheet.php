<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Grafik Akhir Bulan": ketuntasan capaian baris per murid per bulan
 * (Capaian Baris vs Target Baris), dikelompokkan dengan baris judul bagian sama
 * seperti tab "Grafik Akhir Bulan" di layar.
 */
class GrafikAkhirBulanSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /** @var int[] */
    private array $sectionRows = [];

    /** @var int[] */
    private array $headerRows = [];

    public function __construct(private readonly array $halaqahData) {}

    public function title(): string
    {
        return 'Grafik Akhir Bulan';
    }

    public function array(): array
    {
        $rows = [];
        $row = 0;

        foreach ($this->halaqahData as $halaqah) {
            $className = $halaqah['class_room_name'] ?? '-';

            foreach ($halaqah['monthly'] as $month) {
                $records = $month['tahfizh_records'] ?: $month['reguler_records'];
                $tuntasCount = collect($records)->where('is_tuntas', true)->count();
                $total = count($records);
                $percent = $total > 0 ? round(($tuntasCount / $total) * 100) : 0;

                $rows[] = ["{$className} — {$halaqah['musyrif']} — Bulan {$month['label']} ({$tuntasCount}/{$total} Tuntas, {$percent}%)"];
                $this->sectionRows[] = ++$row;

                $rows[] = ['No', 'Nama Murid', 'Capaian Baris', 'Target Baris', 'Keterangan'];
                $this->headerRows[] = ++$row;

                foreach ($records as $idx => $record) {
                    $rows[] = [
                        $idx + 1,
                        $record['name'],
                        $record['total_lines'],
                        $record['target_lines'],
                        $record['is_tuntas'] ? '✅ Tuntas' : '❌ Tidak Tuntas',
                    ];
                    $row++;
                }

                $rows[] = [];
                $row++;
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        foreach ($this->sectionRows as $r) {
            $styles[$r] = [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
            ];
        }

        foreach ($this->headerRows as $r) {
            $styles[$r] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
        }

        return $styles;
    }
}
