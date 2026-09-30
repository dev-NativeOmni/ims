<?php

namespace App\Exports\QuarterlyReport;

use App\Exports\QuarterlyReport\Concerns\CompactTableLayout;
use App\Exports\QuarterlyReport\Concerns\GradeBanding;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Grafik Akhir Bulan": ketuntasan capaian baris per murid per bulan,
 * dikelompokkan per tingkat kelas, dengan dua diagram per kelas per bulan --
 * batang+garis (Capaian vs Target per murid) dan donat Ketuntasan -- sama
 * seperti "GRAFIK CAPAIAN BULAN" & "KETUNTASAN BULAN" di template sekolah.
 */
class GrafikAkhirBulanSheet implements FromArray, ShouldAutoSize, WithCharts, WithColumnWidths, WithEvents, WithStrictNullComparison, WithStyles, WithTitle
{
    use CompactTableLayout, GradeBanding;

    /** @var int[] */
    private array $monthRows = [];

    /** @var array<int, string> */
    private array $gradeRows = [];

    /** @var int[] */
    private array $classRows = [];

    /** @var int[] */
    private array $headerRows = [];

    /** @var array<int, array{title: string, catRange: string, valRange: string, studentCatRange: string, capaianRange: string, targetRange: string}> */
    private array $chartRanges = [];

    /** @var string[] */
    private array $mergeRanges = [];

    /** Kolom (1-indexed) terakhir yang dipakai tabel/data donat -- chart diletakkan di kanannya. */
    private int $lastDataColumn = 8;

    public function __construct(private readonly array $halaqahData) {}

    /** Kolom A (No) dibuat ringkas; baris judul (BULAN/KELAS/Kelas) di-merge selebar sheet. */
    public function columnWidths(): array
    {
        return ['A' => 5];
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
            },
        ];
    }

    public function title(): string
    {
        return 'Grafik Akhir Bulan';
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

                    $month = $halaqah['monthly'][$mCode];
                    $records = $month['tahfizh_records'] ?: $month['reguler_records'];
                    $hasUmmi = (bool) ($halaqah['has_ummi'] ?? false);
                    $tuntasCount = collect($records)->where('is_tuntas', true)->count();
                    $total = count($records);
                    $tidakCount = $total - $tuntasCount;
                    $tuntasPercent = $total > 0 ? round(($tuntasCount / $total) * 100) : 0;
                    $tidakPercent = $total > 0 ? 100 - $tuntasPercent : 0;

                    if ($hasUmmi) {
                        $this->appendUmmiTable($rows, $row, $halaqah, $month, $records, [$tuntasCount, $tidakCount, $tuntasPercent, $tidakPercent]);

                        continue;
                    }

                    // Kolom G/H (di luar kolom data utama A-E) menampung data mentah donat
                    // ketuntasan, dibaca langsung oleh chart di charts() di bawah.
                    $headerRow = ['No', 'Nama Murid', 'Capaian Baris', 'Target Baris', 'Keterangan', '', "TUNTAS ({$tuntasPercent}%)", $tuntasCount];
                    $this->headerRows[] = ++$row;
                    $dataTopRow = $row + 1;
                    $rows[] = $headerRow;

                    $donutTopRow = $row;

                    foreach ($records as $idx => $record) {
                        $line = [
                            $idx + 1,
                            $record['name'],
                            $record['total_lines'],
                            $record['target_lines'],
                            $record['is_tuntas'] ? '✅ Tuntas' : '❌ Tidak Tuntas',
                        ];
                        if ($idx === 0) {
                            $line[] = '';
                            $line[] = "BELUM TUNTAS ({$tidakPercent}%)";
                            $line[] = $tidakCount;
                        }
                        $rows[] = $line;
                        $row++;
                    }
                    $dataBottomRow = $row;
                    $this->addTable($dataTopRow - 1, $dataBottomRow, 1, lastColumn: 'E');

                    if ($total > 0) {
                        $this->chartRanges[] = [
                            'title' => "{$halaqah['class_room_name']} — {$month['label']}",
                            'catRange' => "G{$donutTopRow}:G".($donutTopRow + 1),
                            'valRange' => "H{$donutTopRow}:H".($donutTopRow + 1),
                            'donutValues' => [$tuntasCount, $tidakCount],
                            'studentCatRange' => "B{$dataTopRow}:B{$dataBottomRow}",
                            'capaianRange' => "C{$dataTopRow}:C{$dataBottomRow}",
                            'targetRange' => "D{$dataTopRow}:D{$dataBottomRow}",
                            'has_baris_chart' => true,
                        ];
                    }

                    $rows[] = [''];
                    $row++;
                }
            }
        }

        return $rows;
    }

    /**
     * Tabel halaqoh Ummi (Kelas 10), sama dengan web: Target & Capaian masing-masing
     * Jilid | Halaman | Surah | Ayat. Ummi tidak punya Capaian/Target Baris, jadi hanya diagram
     * donat ketuntasan yang dibuat; datanya di kolom N/O (kanan tabel).
     *
     * @param  array{0: int, 1: int, 2: int|float, 3: int|float}  $tuntas  jumlah & persen tuntas/belum
     */
    private function appendUmmiTable(array &$rows, int &$row, array $halaqah, array $month, array $records, array $tuntas): void
    {
        [$tuntasCount, $tidakCount, $tuntasPercent, $tidakPercent] = $tuntas;

        $top = ++$row;
        $this->headerRows[] = $top;
        $this->headerRows[] = $top + 1;
        $rows[] = ['No', 'Nama Murid', 'Level', 'Target', '', '', '', 'Capaian', '', '', '', 'Ketuntasan', '', "TUNTAS ({$tuntasPercent}%)", $tuntasCount];
        $rows[] = ['', '', '', 'Jilid', 'Halaman', 'Surah', 'Ayat', 'Jilid', 'Halaman', 'Surah', 'Ayat', '', '', "BELUM TUNTAS ({$tidakPercent}%)", $tidakCount];
        $row++;

        foreach (['A', 'B', 'C', 'L'] as $c) {
            $this->mergeRanges[] = "{$c}{$top}:{$c}".($top + 1);
        }
        $this->mergeRanges[] = "D{$top}:G{$top}";
        $this->mergeRanges[] = "H{$top}:K{$top}";

        foreach ($records as $idx => $record) {
            $u = $record['ummi'] ?? [];
            $rows[] = [
                $idx + 1,
                $record['name'],
                $record['level'] ?? '-',
                $u['target_jilid'] ?? '-',
                $u['target_halaman'] ?? '-',
                $u['target_surah'] ?? '-',
                $u['target_ayat'] ?? '-',
                $u['capaian_jilid'] ?? '-',
                $u['capaian_halaman'] ?? '-',
                $u['capaian_surah'] ?? '-',
                $u['capaian_ayat'] ?? '-',
                $record['is_tuntas'] ? '✅ Tuntas' : '❌ Tidak Tuntas',
            ];
            $row++;
        }
        // Tabel A-L saja; data donat (N/O) tidak ikut dirata-tengah sebagai tabel.
        $this->addTable($top, $row, 2, lastColumn: 'L');
        $this->lastDataColumn = max($this->lastDataColumn, 15);

        if ($records !== []) {
            $this->chartRanges[] = [
                'title' => "{$halaqah['class_room_name']} — {$month['label']}",
                'catRange' => "N{$top}:N".($top + 1),
                'valRange' => "O{$top}:O".($top + 1),
                'donutValues' => [$tuntasCount, $tidakCount],
                'has_baris_chart' => false,
            ];
        }

        $rows[] = [''];
        $row++;
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

    /**
     * Dua diagram per kelas per bulan (batang+garis capaian/target, dan donat
     * ketuntasan), ditumpuk vertikal di kolom J+ supaya tidak pernah bertabrakan
     * satu sama lain berapa pun jumlah murid di tiap bagian.
     *
     * @return Chart[]
     */
    public function charts(): array
    {
        $charts = [];
        $sheetTitle = $this->title();
        $offset = 0;

        foreach ($this->chartRanges as $i => $range) {
            $topRow = 2 + ($offset * 16);

            if ($range['has_baris_chart'] ?? true) {
                $charts[] = $this->buildCapaianChart($sheetTitle, $range, $topRow);
            }
            $charts[] = $this->buildDonutChart($sheetTitle, $range, $topRow);

            $offset++;
        }

        return $charts;
    }

    /**
     * Warna disamakan dengan tema web app: biru langit untuk batang Capaian
     * (lihat resources/views/reports/periodic.blade.php, chart "capaianChart"),
     * oranye/amber untuk garis Target, dan teal/rose untuk donat Ketuntasan.
     */
    private const COLOR_CAPAIAN_BAR = '0EA5E9';

    private const COLOR_TARGET_LINE = 'F97316';

    private const COLOR_TUNTAS = '0D9488';

    private const COLOR_TIDAK_TUNTAS = 'F43F5E';

    private function buildCapaianChart(string $sheetTitle, array $range, int $topRow): Chart
    {
        $categories = [new DataSeriesValues('String', "'{$sheetTitle}'!{$range['studentCatRange']}", null, 20)];
        $capaianValues = new DataSeriesValues('Number', "'{$sheetTitle}'!{$range['capaianRange']}", null, 20, [], null, self::COLOR_CAPAIAN_BAR);
        $targetValues = new DataSeriesValues('Number', "'{$sheetTitle}'!{$range['targetRange']}", null, 20, [], null, self::COLOR_TARGET_LINE);

        $barSeries = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            [0],
            [new DataSeriesValues('String', null, null, 1, ['Capaian Baris'])],
            $categories,
            [$capaianValues]
        );

        $lineSeries = new DataSeries(
            DataSeries::TYPE_LINECHART,
            null,
            [0],
            [new DataSeriesValues('String', null, null, 1, ['Target'])],
            $categories,
            [$targetValues]
        );
        $lineSeries->setSmoothLine(false);

        $plotArea = new PlotArea(null, [$barSeries, $lineSeries]);
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
        $title = new Title("Grafik Capaian — {$range['title']}");

        $chart = new Chart('capaian_'.md5($range['title']), $title, $legend, $plotArea);
        $start = $this->lastDataColumn + 2; // default J (kolom data sampai H)
        $chart->setTopLeftPosition(Coordinate::stringFromColumnIndex($start).$topRow);
        $chart->setBottomRightPosition(Coordinate::stringFromColumnIndex($start + 7).($topRow + 14));

        return $chart;
    }

    private function buildDonutChart(string $sheetTitle, array $range, int $topRow): Chart
    {
        $categories = [new DataSeriesValues('String', "'{$sheetTitle}'!{$range['catRange']}", null, 2)];
        // Nilai literal (bukan cuma referensi sel) diperlukan supaya writer benar-benar
        // menulis warna per-irisan (c:dPt) -- ia mengulang array nilai ini untuk itu.
        $values = [new DataSeriesValues('Number', "'{$sheetTitle}'!{$range['valRange']}", null, 2, $range['donutValues'], null, [self::COLOR_TUNTAS, self::COLOR_TIDAK_TUNTAS])];

        $series = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            [0],
            [],
            $categories,
            $values
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
        $title = new Title("Ketuntasan — {$range['title']}");

        $chart = new Chart('ketuntasan_'.md5($range['title']), $title, $legend, $plotArea);
        // Di kanan grafik batang (default R); tanpa grafik batang (semua halaqoh Ummi) langsung di kanan tabel.
        $hasBarChart = collect($this->chartRanges)->contains(fn ($r) => $r['has_baris_chart'] ?? true);
        $start = $this->lastDataColumn + ($hasBarChart ? 10 : 2);
        $chart->setTopLeftPosition(Coordinate::stringFromColumnIndex($start).$topRow);
        $chart->setBottomRightPosition(Coordinate::stringFromColumnIndex($start + 5).($topRow + 14));

        return $chart;
    }
}
