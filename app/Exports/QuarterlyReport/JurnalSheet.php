<?php

namespace App\Exports\QuarterlyReport;

use App\Exports\QuarterlyReport\Concerns\CompactTableLayout;
use App\Exports\QuarterlyReport\Concerns\GradeBanding;
use App\Exports\QuarterlyReport\Concerns\SignatureBlock;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Jurnal": jurnal tatap muka per bulan > tingkat kelas > kelas/halaqoh,
 * sama seperti sheet "JURNAL" di template sekolah. Kolom "Paraf" per pertemuan berisi
 * gambar tanda tangan guru pengampu halaqoh itu (bila sudah diunggah), bukan sekadar centang.
 */
class JurnalSheet implements FromArray, ShouldAutoSize, WithColumnWidths, WithEvents, WithStrictNullComparison, WithStyles, WithTitle
{
    use CompactTableLayout, GradeBanding, SignatureBlock;

    /** @var int[] */
    private array $monthRows = [];

    /** @var array<int, string> */
    private array $gradeRows = [];

    /** @var int[] */
    private array $classRows = [];

    /** @var int[] */
    private array $headerRows = [];

    /** @var string[] */
    private array $mergeRanges = [];

    /** @var array<int, array{cell: string, path: string}> */
    private array $parafDrawings = [];

    public function __construct(
        private readonly array $halaqahData,
        private readonly array $signatureContext = [],
    ) {}

    /** Lebar kolom Paraf (karakter) & tinggi baris isi (poin) supaya gambar paraf terlihat jelas. */
    private const PARAF_WIDTH = 18;

    private const BODY_ROW_HEIGHT = 34;

    /** @var int[] */
    private array $bodyRows = [];

    /** Kolom A (No) ringkas; Paraf lebar supaya gambar paraf jelas. Kolom lain auto-size. */
    public function columnWidths(): array
    {
        return ['A' => 5, 'E' => self::PARAF_WIDTH];
    }

    public function title(): string
    {
        return 'Jurnal';
    }

    public function array(): array
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

                    $rows[] = ['No', 'Hari / Tanggal', 'Materi', 'Jumlah Murid Hadir', 'Paraf'];
                    $this->headerRows[] = ++$row;
                    $headerRow = $row;

                    // Paraf pertemuan yang terlaksana diganti gambar ttd guru pengampu
                    // (bila sudah diunggah); kalau belum ada, tetap tampilkan '✓'/'-'.
                    $teacherSignature = $this->signatureContext['teacher_signatures'][$halaqah['musyrif_signature'] ?? ''] ?? null;

                    foreach ($halaqah['monthly'][$mCode]['jurnal'] as $jIdx => $entry) {
                        $showParafSignature = $entry['paraf'] === '✓' && $teacherSignature;
                        $rows[] = [
                            $jIdx + 1,
                            $entry['tanggal'],
                            $entry['materi'],
                            $entry['jumlah_murid'] ?? '-',
                            $showParafSignature ? '' : $entry['paraf'],
                        ];
                        $row++;
                        $this->bodyRows[] = $row;
                        if ($showParafSignature) {
                            $this->parafDrawings[] = ['cell' => "E{$row}", 'path' => $teacherSignature];
                        }
                    }
                    $this->addTable($headerRow, $row, 1);

                    $rows[] = [''];
                    $row++;
                    $this->appendSignatureBlock($rows, $row, $halaqah, ['B', 'C'], ['D', 'E'], $mCode);
                }
            }
        }

        return $rows;
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
                $this->borderTables($sheet);
                $this->centerTables($sheet, ['B']);
                foreach ($this->bodyRows as $r) {
                    $sheet->getRowDimension($r)->setRowHeight(self::BODY_ROW_HEIGHT);
                }
                $this->applySignatureBlocks($sheet);
                $this->applyParafDrawings($sheet);
            },
        ];
    }

    private function applyParafDrawings(Worksheet $sheet): void
    {
        foreach ($this->parafDrawings as $index => $drawing) {
            $image = new Drawing;
            $image->setName('Paraf '.($index + 1));
            $image->setPath($drawing['path']);
            // Setinggi baris isi (34pt ~ 45px) dikurangi sedikit ruang, ditaruh di tengah sel Paraf.
            $image->setHeight(38);
            $image->setCoordinates($drawing['cell']);
            $columnPx = self::PARAF_WIDTH * 7 + 5;
            $image->setOffsetX((int) max(2, ($columnPx - $image->getWidth()) / 2));
            $image->setOffsetY(4);
            $image->setWorksheet($sheet);
        }
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
