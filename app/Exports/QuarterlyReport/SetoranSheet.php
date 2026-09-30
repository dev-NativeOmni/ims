<?php

namespace App\Exports\QuarterlyReport;

use App\Exports\QuarterlyReport\Concerns\CompactTableLayout;
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
    use CompactTableLayout, GradeBanding, PekanLabeling, SignatureBlock;

    private const REGULER_SUBCOLS = ['Surah', 'Ayat', 'Jumlah Baris', 'Nilai', 'Kehadiran'];

    /** @var int[] */
    private array $monthRows = [];

    /** @var array<int, string> */
    private array $gradeRows = [];

    /** @var int[] */
    private array $classRows = [];

    /** @var int[] */
    private array $headerTopRows = [];

    /** @var int[] baris header ke-3 & ke-4 tabel Ummi (gaya sama dengan sub-header) */
    private array $headerExtraRows = [];

    /** @var string[] daftar range merge cell, mis. "D5:H5" */
    private array $mergeRanges = [];

    public function __construct(
        private readonly array $halaqahData,
        private readonly bool $isTahfizhProgram,
        private readonly array $signatureContext = [],
    ) {}

    /** Kolom A (No) dibuat ringkas; baris judul (BULAN/KELAS/Kelas) di-merge selebar sheet. */
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

                    // Halaqoh Ummi (Kelas 10): tiap pekan dipecah Ummi & Mandiri, sama dengan Program Tahfizh.
                    if ($halaqah['has_ummi'] ?? false) {
                        $this->appendUmmiRegulerTable($rows, $row, $halaqah['monthly'][$mCode], $pekans, $pekanDates);
                        $this->appendSignatureBlock($rows, $row, $halaqah, ['B', 'C'], ['D', 'E']);

                        continue;
                    }

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
                    $this->addTable($headerTopRow, $row, 2);

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

    /** Kolom per pekan untuk halaqoh Ummi Program Reguler: 4 Ummi, Nilai, Kehadiran (tanpa Mandiri). */
    private const UMMI_PEKAN_COLS = 6;

    /**
     * Tabel satu bulan halaqoh Ummi Program Reguler, 3 baris header:
     * PEKAN N (hari, tanggal) | Ummi / Nilai / Kehadiran | Jilid, Halaman, Surah, Ayat, lalu Rekap
     * Kehadiran. Beda dengan Program Tahfizh, tidak ada kolom Mandiri. Pekan tanpa setoran (Izin/Sakit/Belum di input) ditulis sekali di
     * kolom setoran (digabung) dan di kolom Kehadiran.
     *
     * @param  int[]  $pekans
     */
    private function appendUmmiRegulerTable(array &$rows, int &$row, array $month, array $pekans, array $pekanDates): void
    {
        $width = self::UMMI_PEKAN_COLS;
        $col = fn (int $pekanIdx, int $offset) => Coordinate::stringFromColumnIndex(4 + $pekanIdx * $width + $offset);
        $rekapCol = Coordinate::stringFromColumnIndex(4 + count($pekans) * $width);

        $top = ++$row;
        $this->headerTopRows[] = $top;
        $this->headerExtraRows[] = $top + 2;

        $pekanRow = ['No', 'Nama Murid', 'Level'];
        $groupRow = ['', '', ''];
        $subRow = ['', '', ''];
        foreach ($pekans as $p) {
            $pekanRow = array_merge($pekanRow, [$this->pekanLabel($p, $pekanDates)], array_fill(0, $width - 1, ''));
            $groupRow = array_merge($groupRow, ['Ummi', '', '', '', 'Nilai', 'Kehadiran']);
            $subRow = array_merge($subRow, ['Jilid', 'Halaman', 'Surah', 'Ayat', '', '']);
        }
        $rows[] = array_merge($pekanRow, ['Rekap Kehadiran']);
        $rows[] = array_merge($groupRow, ['']);
        $rows[] = array_merge($subRow, ['']);
        $row += 2;

        foreach (['A', 'B', 'C', $rekapCol] as $c) {
            $this->mergeRanges[] = "{$c}{$top}:{$c}".($top + 2);
        }
        foreach (array_keys($pekans) as $i) {
            $this->mergeRanges[] = $col($i, 0).$top.':'.$col($i, $width - 1).$top;
            $this->mergeRanges[] = $col($i, 0).($top + 1).':'.$col($i, 3).($top + 1);
            $this->mergeRanges[] = $col($i, 4).($top + 1).':'.$col($i, 4).($top + 2);
            $this->mergeRanges[] = $col($i, 5).($top + 1).':'.$col($i, 5).($top + 2);
        }

        foreach ($month['reguler_records'] as $idx => $record) {
            $sPres = $month['presensi'][$record['student_id']] ?? ['hadir' => 0];
            $line = [$idx + 1, $record['name'], $record['level']];
            $row++;
            foreach ($pekans as $i => $p) {
                $pekan = $record['pekan'][$p];
                $parts = $pekan['parts'] ?? null;
                if ($pekan['kehadiran'] !== 'Hadir' || $parts === null) {
                    // Tidak ada setoran pekan ini: statusnya ditulis sekali, digabung selebar kolom setoran.
                    $status = $pekan['kehadiran'] === 'Hadir' ? '-' : $pekan['kehadiran'];
                    $line = array_merge($line, [$status], array_fill(0, $width - 2, ''), [$pekan['kehadiran']]);
                    $this->mergeRanges[] = $col($i, 0).$row.':'.$col($i, 4).$row;

                    continue;
                }
                $line = array_merge($line, array_values($parts['ummi']), [$pekan['nilai'], 'Hadir']);
            }
            $line[] = "{$sPres['hadir']}x Hadir";
            $rows[] = $line;
        }
        $this->addTable($top, $row, 3);

        $rows[] = [''];
        $row++;
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

                    // Halaqoh Ummi (Kelas 10): tiap hari dipecah Ummi (Jilid|Halaman|Surah|Ayat) & Mandiri (Surah|Ayat).
                    if ($halaqah['has_ummi'] ?? false) {
                        foreach ($this->activePekans($pekanDatesForClass) as $p) {
                            $this->appendUmmiPekanTable($rows, $row, $halaqah['monthly'][$mCode], $p, $pekanDatesForClass);
                        }
                        $this->appendSignatureBlock($rows, $row, $halaqah, ['B', 'C'], ['D', 'E']);

                        continue;
                    }

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
                        $this->addTable($headerTopRow, $row, 2);

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

    /** Kolom per hari untuk halaqoh Ummi: 4 kolom Ummi, 2 kolom Mandiri, 1 kolom Nilai. */
    private const UMMI_DAY_COLS = 7;

    /**
     * Satu tabel pekan halaqoh Ummi dengan 4 baris header:
     * PEKAN N | hari & tanggal | Ummi / Mandiri / Nilai | Jilid, Halaman, Surah, Ayat, Surah, Ayat.
     * Hari tanpa setoran (Izin/Sakit/Alpa/Belum di input) ditulis sekali, digabung selebar hari itu.
     */
    private function appendUmmiPekanTable(array &$rows, int &$row, array $month, int $p, array $pekanDates): void
    {
        $days = $pekanDates[$p];
        $width = count($days) * self::UMMI_DAY_COLS;
        $col = fn (int $dayIdx, int $offset) => Coordinate::stringFromColumnIndex(4 + $dayIdx * self::UMMI_DAY_COLS + $offset);

        $top = ++$row;
        $this->headerTopRows[] = $top;
        $this->headerExtraRows[] = $top + 2;
        $this->headerExtraRows[] = $top + 3;

        $rows[] = array_merge(['No', 'Nama Murid', 'Level', $this->pekanLabel($p, $pekanDates)], array_fill(0, $width - 1, ''));
        $dayRow = ['', '', ''];
        $groupRow = ['', '', ''];
        $subRow = ['', '', ''];
        foreach ($days as $day) {
            $dayRow = array_merge($dayRow, [$day['label']], array_fill(0, self::UMMI_DAY_COLS - 1, ''));
            $groupRow = array_merge($groupRow, ['Ummi', '', '', '', 'Mandiri', '', 'Nilai']);
            $subRow = array_merge($subRow, ['Jilid', 'Halaman', 'Surah', 'Ayat', 'Surah', 'Ayat', '']);
        }
        $rows[] = $dayRow;
        $rows[] = $groupRow;
        $rows[] = $subRow;
        $row += 3;

        foreach (['A', 'B', 'C'] as $c) {
            $this->mergeRanges[] = "{$c}{$top}:{$c}".($top + 3);
        }
        $this->mergeRanges[] = "D{$top}:".$col(count($days) - 1, self::UMMI_DAY_COLS - 1).$top;
        foreach (array_keys($days) as $i) {
            $this->mergeRanges[] = $col($i, 0).($top + 1).':'.$col($i, 6).($top + 1);
            $this->mergeRanges[] = $col($i, 0).($top + 2).':'.$col($i, 3).($top + 2);
            $this->mergeRanges[] = $col($i, 4).($top + 2).':'.$col($i, 5).($top + 2);
            $this->mergeRanges[] = $col($i, 6).($top + 2).':'.$col($i, 6).($top + 3);
        }

        foreach ($month['tahfizh_records'] as $idx => $record) {
            $line = [$idx + 1, $record['name'], $record['level']];
            $row++;
            foreach ($days as $i => $day) {
                $log = $record['pekan'][$p]['days'][$day['day']] ?? ['status' => 'kosong', 'surah' => '-'];
                $parts = $log['parts'] ?? null;
                if (($log['status'] ?? 'setoran') !== 'setoran' || $parts === null) {
                    $line = array_merge($line, [$log['surah'] ?? '-'], array_fill(0, self::UMMI_DAY_COLS - 1, ''));
                    $this->mergeRanges[] = $col($i, 0).$row.':'.$col($i, 6).$row;

                    continue;
                }
                $line = array_merge($line, array_values($parts['ummi']), array_values($parts['mandiri']), [$log['nilai']]);
            }
            $rows[] = $line;
        }
        $this->addTable($top, $row, 4);

        $rows[] = [''];
        $row++;
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

        foreach ($this->headerExtraRows as $r) {
            $styles[$r] = [
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
                $this->mergeTitleRows($sheet);
                $this->centerTables($sheet, ['B']);
                $this->applySignatureBlocks($sheet);
            },
        ];
    }
}
