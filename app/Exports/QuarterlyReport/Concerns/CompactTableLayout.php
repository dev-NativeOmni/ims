<?php

namespace App\Exports\QuarterlyReport\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Tata letak ringkas sheet Laporan Triwulan:
 * - Baris judul (BULAN / KELAS / Kelas: ...) di-merge selebar sheet supaya teks & warnanya tidak
 *   terpotong di kolom No yang sempit (sel merge juga tidak ikut dihitung auto-size).
 * - Isi tabel rata tengah, kecuali kolom teks tertentu (mis. Nama Murid) yang tetap rata kiri.
 * - Garis tabel (borderTables) di semua sel tiap tabel.
 *
 * Sheet pemakai mengisi $monthRows, $gradeRows (baris => warna), $classRows, dan mencatat tiap
 * tabel lewat addTable().
 */
trait CompactTableLayout
{
    /** @var array<int, array{start: int, end: int, header: int, last: ?string}> */
    private array $tables = [];

    /**
     * Catat satu tabel: baris header pertama, baris terakhir, jumlah baris header, dan (opsional)
     * kolom terakhir tabel -- default kolom terisi terjauh di baris-baris tabel.
     */
    private function addTable(int $start, int $end, int $headerRows, ?string $lastColumn = null): void
    {
        if ($end >= $start) {
            $this->tables[] = ['start' => $start, 'end' => $end, 'header' => $headerRows, 'last' => $lastColumn];
        }
    }

    private function mergeTitleRows(Worksheet $sheet): void
    {
        $lastColumn = $sheet->getHighestDataColumn();
        $titleRows = array_merge($this->monthRows ?? [], array_keys($this->gradeRows ?? []), $this->classRows ?? []);

        foreach ($titleRows as $r) {
            $sheet->mergeCells("A{$r}:{$lastColumn}{$r}");
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }
    }

    /** Garis tipis di semua sel setiap tabel (header sampai baris terakhir). */
    private function borderTables(Worksheet $sheet): void
    {
        foreach ($this->tables as $table) {
            $sheet->getStyle("A{$table['start']}:{$this->tableLastColumn($sheet, $table)}{$table['end']}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('9CA3AF');
        }
    }

    private function tableLastColumn(Worksheet $sheet, array $table): string
    {
        return $table['last'] ?? Coordinate::stringFromColumnIndex(max(1, ...array_map(
            fn (int $r) => Coordinate::columnIndexFromString($sheet->getHighestDataColumn($r)),
            range($table['start'], $table['end'])
        )));
    }

    /** @param  string[]  $leftColumns  kolom isi yang tetap rata kiri, mis. ['B'] untuk Nama Murid */
    private function centerTables(Worksheet $sheet, array $leftColumns): void
    {
        foreach ($this->tables as $table) {
            $lastColumn = $this->tableLastColumn($sheet, $table);

            $sheet->getStyle("A{$table['start']}:{$lastColumn}{$table['end']}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $firstBody = $table['start'] + $table['header'];
            foreach ($leftColumns as $column) {
                if ($firstBody <= $table['end']) {
                    $sheet->getStyle("{$column}{$firstBody}:{$column}{$table['end']}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
            }
        }
    }
}
