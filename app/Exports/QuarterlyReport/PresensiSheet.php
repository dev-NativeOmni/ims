<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Presensi": grid per halaqoh/kelas sama seperti tab "Presensi" di layar --
 * Reguler satu tabel per bulan (murid x Pekan 1-5), Tahfizh satu tabel per halaqoh
 * (murid x 12 pertemuan x 3 bulan), bukan baris datar per kejadian.
 */
class PresensiSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    private const STATUS_MAP = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa', '-' => '-'];

    /** @var int[] baris judul bagian (kelas/halaqoh/bulan), dibuat tebal + latar abu-abu tua. */
    private array $sectionRows = [];

    /** @var int[] baris judul kolom, dibuat tebal + latar abu-abu muda. */
    private array $headerRows = [];

    public function __construct(
        private readonly array $halaqahData,
        private readonly bool $isTahfizhProgram,
    ) {}

    public function title(): string
    {
        return 'Presensi';
    }

    public function array(): array
    {
        return $this->isTahfizhProgram ? $this->buildTahfizhGrid() : $this->buildRegulerGrid();
    }

    private function buildRegulerGrid(): array
    {
        $rows = [];
        $row = 0;

        foreach ($this->halaqahData as $halaqah) {
            $className = $halaqah['class_room_name'] ?? '-';

            foreach ($halaqah['monthly'] as $month) {
                $rows[] = ["{$className} — {$halaqah['musyrif']} — Bulan {$month['label']}"];
                $this->sectionRows[] = ++$row;

                $rows[] = ['No', 'Nama Murid', 'Pekan 1', 'Pekan 2', 'Pekan 3', 'Pekan 4', 'Pekan 5', 'Hadir', 'Izin', 'Sakit', 'Alpa'];
                $this->headerRows[] = ++$row;

                foreach ($halaqah['students'] as $idx => $student) {
                    $sPres = $month['presensi'][$student->id] ?? null;
                    if (! $sPres) {
                        continue;
                    }

                    $rows[] = [
                        $idx + 1,
                        $student->name,
                        $sPres['pekan'][1] ?? '-',
                        $sPres['pekan'][2] ?? '-',
                        $sPres['pekan'][3] ?? '-',
                        $sPres['pekan'][4] ?? '-',
                        $sPres['pekan'][5] ?? '-',
                        $sPres['hadir'],
                        $sPres['izin'],
                        $sPres['sakit'],
                        $sPres['alpa'],
                    ];
                    $row++;
                }

                $rows[] = [''];
                $row++;
            }
        }

        return $rows;
    }

    private function buildTahfizhGrid(): array
    {
        $rows = [];
        $row = 0;

        foreach ($this->halaqahData as $halaqah) {
            $className = $halaqah['class_room_name'] ?? '-';
            $months = $halaqah['months'] ?? [];

            $rows[] = ["{$className} — {$halaqah['musyrif']}"];
            $this->sectionRows[] = ++$row;

            $header = ['No', 'Nama Murid'];
            foreach ($months as $mName) {
                for ($day = 1; $day <= 12; $day++) {
                    $header[] = "{$mName} P{$day}";
                }
                $header[] = "{$mName} S";
                $header[] = "{$mName} I";
                $header[] = "{$mName} A";
            }
            $rows[] = $header;
            $this->headerRows[] = ++$row;

            foreach ($halaqah['students'] as $idx => $student) {
                $line = [$idx + 1, $student->name];

                foreach ($months as $mName) {
                    $sPres = $halaqah['presensi'][$student->id][$mName] ?? null;
                    for ($day = 1; $day <= 12; $day++) {
                        $code = $sPres['days'][$day] ?? '-';
                        $line[] = self::STATUS_MAP[$code] ?? $code;
                    }
                    $line[] = $sPres['sakit'] ?? 0;
                    $line[] = $sPres['izin'] ?? 0;
                    $line[] = $sPres['alpa'] ?? 0;
                }

                $rows[] = $line;
                $row++;
            }

            $rows[] = [''];
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
