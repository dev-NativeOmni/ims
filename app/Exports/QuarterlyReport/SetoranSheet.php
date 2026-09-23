<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Setoran": capaian setoran per murid per pekan (Reguler) atau per hari
 * dalam pekan (Tahfizh), sama dengan tab "Setoran" pada halaman Laporan Triwulan.
 */
class SetoranSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly array $halaqahData,
        private readonly bool $isTahfizhProgram,
    ) {}

    public function title(): string
    {
        return 'Setoran';
    }

    public function headings(): array
    {
        return ['Halaqah (Musyrif)', 'Bulan', 'Nama Murid', 'Level', 'Pekan', 'Hari', 'Surah / Keterangan', 'Ayat', 'Baris', 'Nilai'];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->halaqahData as $halaqah) {
            foreach ($halaqah['monthly'] as $month) {
                $records = $this->isTahfizhProgram ? $month['tahfizh_records'] : $month['reguler_records'];

                foreach ($records as $record) {
                    foreach ($record['pekan'] as $p => $pekanData) {
                        if ($this->isTahfizhProgram) {
                            foreach ($pekanData['days'] as $dayName => $day) {
                                $rows[] = [
                                    $halaqah['musyrif'],
                                    $month['label'],
                                    $record['name'],
                                    $record['level'],
                                    "Pekan {$p}",
                                    $dayName,
                                    $day['surah'],
                                    '',
                                    $day['baris'],
                                    $day['nilai'],
                                ];
                            }
                        } else {
                            $rows[] = [
                                $halaqah['musyrif'],
                                $month['label'],
                                $record['name'],
                                $record['level'],
                                "Pekan {$p}",
                                '',
                                $pekanData['surah'],
                                $pekanData['ayat'],
                                $pekanData['baris'],
                                $pekanData['nilai'],
                            ];
                        }
                    }
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
