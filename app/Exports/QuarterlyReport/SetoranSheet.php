<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Setoran": grid capaian setoran per halaqoh sama seperti tab "Setoran" di
 * layar -- Reguler satu tabel per bulan (murid x Pekan 1-5), Tahfizh satu tabel
 * per bulan per pekan (murid x Senin-Jumat), bukan baris datar per kejadian.
 */
class SetoranSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    private const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    /** @var int[] */
    private array $sectionRows = [];

    /** @var int[] */
    private array $headerRows = [];

    public function __construct(
        private readonly array $halaqahData,
        private readonly bool $isTahfizhProgram,
    ) {}

    public function title(): string
    {
        return 'Setoran';
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

                $rows[] = ['No', 'Nama Murid', 'Level', 'Pekan 1', 'Pekan 2', 'Pekan 3', 'Pekan 4', 'Pekan 5', 'Total Baris', 'Hadir', 'Izin', 'Sakit', 'Alpa'];
                $this->headerRows[] = ++$row;

                foreach ($month['reguler_records'] as $idx => $record) {
                    $sPres = $month['presensi'][$record['student_id']] ?? ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];

                    $rows[] = [
                        $idx + 1,
                        $record['name'],
                        $record['level'],
                        $this->regulerCell($record['pekan'][1]),
                        $this->regulerCell($record['pekan'][2]),
                        $this->regulerCell($record['pekan'][3]),
                        $this->regulerCell($record['pekan'][4]),
                        $this->regulerCell($record['pekan'][5]),
                        "{$record['total_lines']} Baris",
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

    private function regulerCell(array $pekan): string
    {
        if ($pekan['kehadiran'] !== 'Hadir') {
            return $pekan['kehadiran'];
        }

        return trim("{$pekan['surah']} {$pekan['ayat']} ({$pekan['baris']} Brs, Nilai {$pekan['nilai']})");
    }

    private function buildTahfizhGrid(): array
    {
        $rows = [];
        $row = 0;

        foreach ($this->halaqahData as $halaqah) {
            $className = $halaqah['class_room_name'] ?? '-';

            foreach ($halaqah['monthly'] as $month) {
                for ($p = 1; $p <= 5; $p++) {
                    $rows[] = ["{$className} — {$halaqah['musyrif']} — Bulan {$month['label']} — Pekan {$p}"];
                    $this->sectionRows[] = ++$row;

                    $rows[] = array_merge(['No', 'Nama Murid', 'Level'], self::DAYS, ['Total Baris', 'Nilai']);
                    $this->headerRows[] = ++$row;

                    foreach ($month['tahfizh_records'] as $idx => $record) {
                        $wRecord = $record['pekan'][$p];
                        $line = [$idx + 1, $record['name'], $record['level']];

                        foreach (self::DAYS as $dayName) {
                            $line[] = $this->tahfizhCell($wRecord['days'][$dayName]);
                        }

                        $line[] = "{$wRecord['week_lines']} Baris";
                        $line[] = 'A';
                        $rows[] = $line;
                        $row++;
                    }

                    $rows[] = [''];
                    $row++;
                }
            }
        }

        return $rows;
    }

    private function tahfizhCell(array $day): string
    {
        if (in_array($day['surah'], ['Libur', 'Belum di input'], true) || $day['baris'] == 0) {
            return $day['surah'];
        }

        $ayat = $day['ayat_start'] !== '' ? "{$day['ayat_start']}-{$day['ayat_end']} " : '';

        return trim("{$day['surah']} {$ayat}({$day['baris']} Brs, Nilai {$day['nilai']})");
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
