<?php

namespace App\Exports\QuarterlyReport;

use App\Exports\QuarterlyReport\Concerns\GradeBanding;
use App\Exports\QuarterlyReport\Concerns\PekanLabeling;
use App\Exports\QuarterlyReport\Concerns\SignatureBlock;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Setoran" (Capaian Hafalan): grid per bulan > tingkat kelas > kelas/halaqoh,
 * dengan header gabungan "PEKAN N" (Surah, Ayat, Jumlah Baris, Nilai, Kehadiran) dan
 * "REKAPAN AKHIR BULAN" -- sama seperti sheet "CAPAIAN HAFALAN" di template sekolah.
 * Tahfizh memakai grid per hari pertemuan aktif per pekan. Pekan/hari libur tidak dijadikan kolom.
 */
class SetoranSheet implements FromArray, ShouldAutoSize, WithColumnWidths, WithEvents, WithStrictNullComparison, WithStyles, WithTitle
{
    use GradeBanding, PekanLabeling, SignatureBlock;

    private const REGULER_SUBCOLS = ['Surah', 'Ayat', 'Jumlah Baris', 'Nilai', 'Kehadiran'];

    /** @var int[] */
    private array $monthRows = [];

    /** @var array<int, string> */
    private array $gradeRows = [];

    /** @var int[] */
    private array $classRows = [];

    /** @var int[] */
    private array $headerTopRows = [];

    /** @var string[] daftar range merge cell, mis. "D5:H5" */
    private array $mergeRanges = [];

    public function __construct(
        private readonly array $halaqahData,
        private readonly bool $isTahfizhProgram,
        private readonly array $signatureContext = [],
    ) {}

    /** Kolom A (No) dibuat ringkas; baris judul (BULAN/KELAS/Kelas) cukup meluber ke kolom sebelah. */
    public function columnWidths(): array
    {
        return ['A' => 5];
    }

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
        $months = $this->halaqahData[0]['monthly'] ?? [];

        foreach ($months as $mCode => $firstMonth) {
            $rows[] = ["BULAN {$firstMonth['label']}"];
            $this->monthRows[] = ++$row;

            foreach ($this->groupByGrade($this->halaqahData) as $grade => $halaqahs) {
                $rows[] = ["KELAS {$grade}"];
                $this->gradeRows[++$row] = $this->gradeColor($grade);

                foreach ($halaqahs as $halaqah) {
                    $rows[] = ["Kelas: {$halaqah['class_room_name']}  |  Musyrif: {$halaqah['musyrif']}"];
                    $this->classRows[] = ++$row;

                    // Hanya pekan yang punya pertemuan aktif (pekan libur tidak dijadikan kolom).
                    $pekanDates = $halaqah['monthly'][$mCode]['pekan_dates'] ?? [];
                    $pekans = $this->activePekans($pekanDates);

                    $headerTopRow = ++$row;
                    $this->headerTopRows[] = $headerTopRow;
                    $rows[] = $this->regulerHeaderTop($pekans, $pekanDates);
                    $rows[] = $this->regulerHeaderSub(count($pekans));
                    $row++; // baris sub-header kedua

                    $this->mergeRanges[] = 'A'.$headerTopRow.':A'.($headerTopRow + 1);
                    $this->mergeRanges[] = 'B'.$headerTopRow.':B'.($headerTopRow + 1);
                    $this->mergeRanges[] = 'C'.$headerTopRow.':C'.($headerTopRow + 1);
                    foreach (array_keys($pekans) as $i) {
                        $start = Coordinate::stringFromColumnIndex(4 + $i * 5);
                        $end = Coordinate::stringFromColumnIndex(8 + $i * 5);
                        $this->mergeRanges[] = "{$start}{$headerTopRow}:{$end}{$headerTopRow}";
                    }
                    $rekapStart = 4 + count($pekans) * 5;
                    $this->mergeRanges[] = Coordinate::stringFromColumnIndex($rekapStart).$headerTopRow.':'.Coordinate::stringFromColumnIndex($rekapStart + 1).$headerTopRow;

                    $month = $halaqah['monthly'][$mCode];
                    foreach ($month['reguler_records'] as $idx => $record) {
                        $sPres = $month['presensi'][$record['student_id']] ?? ['hadir' => 0];
                        $isUmmi = ($record['ummi'] ?? null) !== null;

                        $line = [$idx + 1, $record['name'], $record['level']];
                        foreach ($pekans as $p) {
                            $pekan = $record['pekan'][$p];
                            if ($pekan['kehadiran'] !== 'Hadir') {
                                $line = array_merge($line, [$pekan['kehadiran'], '', '', '', $pekan['kehadiran']]);
                            } else {
                                // Ummi: Jilid, Halaman, Surah & Ayat sudah digabung di label setoran.
                                $line = $isUmmi && isset($pekan['setoran'])
                                    ? array_merge($line, [$pekan['setoran'], '-', '-', $pekan['nilai'], 'Hadir'])
                                    : array_merge($line, [$pekan['surah'], $pekan['ayat'], $isUmmi ? '-' : $pekan['baris'], $pekan['nilai'], 'Hadir']);
                            }
                        }
                        // Ummi tidak punya Capaian Baris -- ketuntasannya dinilai dari Jilid|Halaman.
                        $line[] = $isUmmi ? '-' : "{$record['total_lines']} Baris";
                        $line[] = "{$sPres['hadir']}x Hadir";

                        $rows[] = $line;
                        $row++;
                    }

                    $rows[] = [''];
                    $row++;
                    // Kolom kanan sengaja dekat dengan kolom kiri (bukan di ujung tabel pekan
                    // yang lebar) supaya tanda tangan Kepala Sekolah & Guru Pengampu berdampingan.
                    $this->appendSignatureBlock($rows, $row, $halaqah, ['B', 'C'], ['D', 'E']);
                }
            }
        }

        return $rows;
    }

    /** @param  int[]  $pekans */
    private function regulerHeaderTop(array $pekans, array $pekanDates): array
    {
        $row = ['No', 'Nama Murid', 'Level'];
        foreach ($pekans as $p) {
            $row = array_merge($row, [$this->pekanLabel($p, $pekanDates), '', '', '', '']);
        }

        return array_merge($row, ['REKAPAN AKHIR BULAN', '']);
    }

    private function regulerHeaderSub(int $pekanCount): array
    {
        $row = ['', '', ''];
        for ($i = 0; $i < $pekanCount; $i++) {
            $row = array_merge($row, self::REGULER_SUBCOLS);
        }

        return array_merge($row, ['Capaian Baris', 'Rekap Kehadiran']);
    }

    private function buildTahfizhGrid(): array
    {
        $rows = [];
        $row = 0;
        $months = $this->halaqahData[0]['monthly'] ?? [];

        foreach ($months as $mCode => $firstMonth) {
            $rows[] = ["BULAN {$firstMonth['label']}"];
            $this->monthRows[] = ++$row;

            foreach ($this->groupByGrade($this->halaqahData) as $grade => $halaqahs) {
                $rows[] = ["KELAS {$grade}"];
                $this->gradeRows[++$row] = $this->gradeColor($grade);

                foreach ($halaqahs as $halaqah) {
                    $rows[] = ["Kelas: {$halaqah['class_room_name']}  |  Musyrif: {$halaqah['musyrif']}"];
                    $this->classRows[] = ++$row;

                    $pekanDatesForClass = $halaqah['monthly'][$mCode]['pekan_dates'] ?? [];

                    // Hanya pekan & hari pertemuan aktif (pekan/hari libur tidak dijadikan kolom), sama dengan web.
                    foreach ($this->activePekans($pekanDatesForClass) as $p) {
                        $days = $pekanDatesForClass[$p];
                        $dayCount = count($days);

                        $headerTopRow = ++$row;
                        $this->headerTopRows[] = $headerTopRow;
                        $rows[] = array_merge(['No', 'Nama Murid', 'Level', $this->pekanLabel($p, $pekanDatesForClass)], array_fill(0, $dayCount - 1, ''), ['Rekap']);
                        $rows[] = array_merge(['', '', ''], array_column($days, 'label'), ['Baris']);
                        $row++;

                        $this->mergeRanges[] = 'A'.$headerTopRow.':A'.($headerTopRow + 1);
                        $this->mergeRanges[] = 'B'.$headerTopRow.':B'.($headerTopRow + 1);
                        $this->mergeRanges[] = 'C'.$headerTopRow.':C'.($headerTopRow + 1);
                        if ($dayCount > 1) {
                            $this->mergeRanges[] = 'D'.$headerTopRow.':'.Coordinate::stringFromColumnIndex(3 + $dayCount).$headerTopRow;
                        }

                        $month = $halaqah['monthly'][$mCode];
                        foreach ($month['tahfizh_records'] as $idx => $record) {
                            $wRecord = $record['pekan'][$p];
                            $isUmmi = ($record['ummi'] ?? null) !== null;
                            $line = [$idx + 1, $record['name'], $record['level']];

                            foreach ($days as $day) {
                                $line[] = $this->tahfizhCell($wRecord['days'][$day['day']] ?? ['status' => 'kosong', 'surah' => '-'], $isUmmi);
                            }

                            // Ummi tidak punya Capaian Baris -- ketuntasannya dinilai dari Jilid|Halaman.
                            $line[] = $isUmmi ? '-' : "{$wRecord['week_lines']} Baris";
                            $rows[] = $line;
                            $row++;
                        }

                        $rows[] = [''];
                        $row++;
                    }

                    // Kolom kanan didekatkan ke kolom kiri (bukan di kolom Kamis/Jumat/Rekap)
                    // supaya tanda tangan Kepala Sekolah & Guru Pengampu berdampingan.
                    $this->appendSignatureBlock($rows, $row, $halaqah, ['B', 'C'], ['D', 'E']);
                }
            }
        }

        return $rows;
    }

    private function tahfizhCell(array $day, bool $isUmmi = false): string
    {
        // Libur / Belum di input / Izin / Sakit / Alpa: cukup statusnya.
        if (($day['status'] ?? 'setoran') !== 'setoran') {
            return $day['surah'];
        }

        $lines = $isUmmi ? '' : "{$day['baris']} Brs, ";

        return "{$day['surah']} ({$lines}Nilai {$day['nilai']})";
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        foreach ($this->monthRows as $r) {
            $styles[$r] = [
                'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1E3A8A']],
            ];
        }

        foreach ($this->gradeRows as $r => $color) {
            $styles[$r] = [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => $color]],
            ];
        }

        foreach ($this->classRows as $r) {
            $styles[$r] = ['font' => ['bold' => true, 'italic' => true]];
        }

        foreach ($this->headerTopRows as $r) {
            $styles[$r] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E5E7EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            $styles[$r + 1] = [
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F3F4F6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
        }

        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                foreach ($this->mergeRanges as $range) {
                    $sheet->mergeCells($range);
                }
                $this->applySignatureBlocks($sheet);
            },
        ];
    }
}
