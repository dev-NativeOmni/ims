<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Jurnal": jurnal tatap muka per bulan per halaqah, dikelompokkan dengan
 * baris judul bagian sama seperti tab "Jurnal" pada halaman Laporan Triwulan.
 */
class JurnalSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /** @var int[] */
    private array $sectionRows = [];

    /** @var int[] */
    private array $headerRows = [];

    public function __construct(private readonly array $halaqahData) {}

    public function title(): string
    {
        return 'Jurnal';
    }

    public function array(): array
    {
        $rows = [];
        $row = 0;

        foreach ($this->halaqahData as $halaqah) {
            $className = $halaqah['class_room_name'] ?? '-';

            foreach ($halaqah['monthly'] as $month) {
                $rows[] = ["{$className} — {$halaqah['musyrif']} — Bulan {$month['label']}"];
                $this->sectionRows[] = ++$row;

                $rows[] = ['No', 'Hari / Tanggal', 'Materi', 'Jumlah Murid Hadir', 'Paraf'];
                $this->headerRows[] = ++$row;

                foreach ($month['jurnal'] as $jIdx => $entry) {
                    $rows[] = [
                        $jIdx + 1,
                        $entry['tanggal'],
                        $entry['materi'],
                        $entry['jumlah_murid'],
                        $entry['paraf'],
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
