<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Term-Indeks": rekap akhir triwulan per murid per halaqoh, dikelompokkan
 * dengan baris judul bagian sama seperti tab "Term / Indeks (DNS)" di layar.
 */
class TermIndexSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    /** @var int[] */
    private array $sectionRows = [];

    /** @var int[] */
    private array $headerRows = [];

    public function __construct(private readonly array $halaqahData) {}

    public function title(): string
    {
        return 'Term-Indeks';
    }

    public function array(): array
    {
        $rows = [];
        $row = 0;

        foreach ($this->halaqahData as $halaqah) {
            $className = $halaqah['class_room_name'] ?? '-';

            $rows[] = ["{$className} — {$halaqah['musyrif']}"];
            $this->sectionRows[] = ++$row;

            $rows[] = [
                'No', 'Nama Murid', 'Level',
                'Target Surah', 'Target Ayat', 'Capaian Surah', 'Capaian Ayat',
                'Capaian Baris', 'Target Baris', 'Ketercapaian',
                'Alpa', 'Izin', 'Sakit', 'Pelanggaran',
            ];
            $this->headerRows[] = ++$row;

            foreach ($halaqah['term_records'] as $idx => $termRow) {
                $rows[] = [
                    $idx + 1,
                    $termRow['name'],
                    $termRow['level'],
                    $termRow['target_surah'],
                    $termRow['target_ayat'],
                    $termRow['capaian_surah'],
                    $termRow['capaian_ayat'],
                    $termRow['total_lines'],
                    $termRow['target_lines'],
                    $termRow['is_tuntas'] ? 'Tuntas' : 'Tidak Tuntas',
                    $termRow['alpa'],
                    $termRow['izin'],
                    $termRow['sakit'],
                    $termRow['pelanggaran'],
                ];
                $row++;
            }

            $rows[] = [];
            $row++;
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
