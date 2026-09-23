<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Presensi": satu baris per murid per pertemuan/pekan. Program Tahfizh
 * memakai grid 12 pertemuan per bulan (H/S/I/A/-), program Reguler memakai
 * status per pekan (Hadir/Izin/Sakit/Alpa/Libur/Belum di input).
 */
class PresensiSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    private const STATUS_MAP = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa', '-' => '-'];

    public function __construct(
        private readonly array $halaqahData,
        private readonly bool $isTahfizhProgram,
    ) {}

    public function title(): string
    {
        return 'Presensi';
    }

    public function headings(): array
    {
        return ['Kelas', 'Halaqah (Musyrif)', 'Nama Murid', 'Bulan', 'Pertemuan', 'Status'];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->halaqahData as $halaqah) {
            $className = $halaqah['class_room_name'] ?? '-';

            if ($this->isTahfizhProgram) {
                foreach ($halaqah['students'] as $student) {
                    foreach ($halaqah['presensi'][$student->id] ?? [] as $monthLabel => $data) {
                        foreach ($data['days'] as $meetingNo => $code) {
                            $rows[] = [
                                $className,
                                $halaqah['musyrif'],
                                $student->name,
                                $monthLabel,
                                "Pertemuan {$meetingNo}",
                                self::STATUS_MAP[$code] ?? $code,
                            ];
                        }
                    }
                }
            } else {
                foreach ($halaqah['monthly'] as $month) {
                    foreach ($halaqah['students'] as $student) {
                        foreach ($month['presensi'][$student->id]['pekan'] ?? [] as $p => $status) {
                            $rows[] = [
                                $className,
                                $halaqah['musyrif'],
                                $student->name,
                                $month['label'],
                                "Pekan {$p}",
                                $status,
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
