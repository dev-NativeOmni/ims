<?php

namespace App\Exports\QuarterlyReport;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Grafik Akhir Bulan": ketuntasan capaian baris per murid per bulan
 * (Capaian Baris vs Target Baris), dikelompokkan dengan baris judul bagian sama
 * seperti tab "Grafik Akhir Bulan" di layar, plus diagram donat Tuntas/Belum
 * Tuntas per bulan (sama seperti donat "Ketuntasan" di Rapor Periodik).
 */
class GrafikAkhirBulanSheet implements FromArray, ShouldAutoSize, WithCharts, WithStyles, WithTitle
{
    /** @var int[] */
    private array $sectionRows = [];

    /** @var int[] */
    private array $headerRows = [];

    /** @var array<int, array{title: string, catRange: string, valRange: string}> */
    private array $donutRanges = [];

    public function __construct(private readonly array $halaqahData) {}

    public function title(): string
    {
        return 'Grafik Akhir Bulan';
    }

    public function array(): array
    {
        $rows = [];
        $row = 0;

        foreach ($this->halaqahData as $halaqah) {
            $className = $halaqah['class_room_name'] ?? '-';

            foreach ($halaqah['monthly'] as $month) {
                $records = $month['tahfizh_records'] ?: $month['reguler_records'];
                $tuntasCount = collect($records)->where('is_tuntas', true)->count();
                $total = count($records);
                $tidakCount = $total - $tuntasCount;
                $tuntasPercent = $total > 0 ? round(($tuntasCount / $total) * 100) : 0;
                $tidakPercent = $total > 0 ? 100 - $tuntasPercent : 0;

                // Kolom G/H (di luar kolom data utama A-E) menampung data mentah donat
                // ketuntasan bulan ini, dibaca langsung oleh chart di charts() di bawah.
                $bannerRow = array_pad(["{$className} — {$halaqah['musyrif']} — Bulan {$month['label']} ({$tuntasCount}/{$total} Tuntas, {$tuntasPercent}%)"], 6, '');
                $bannerRow[] = "TUNTAS ({$tuntasPercent}%)";
                $bannerRow[] = $tuntasCount;
                $rows[] = $bannerRow;
                $this->sectionRows[] = ++$row;
                $donutTopRow = $row;

                $headerRow = array_pad(['No', 'Nama Murid', 'Capaian Baris', 'Target Baris', 'Keterangan'], 6, '');
                $headerRow[] = "BELUM TUNTAS ({$tidakPercent}%)";
                $headerRow[] = $tidakCount;
                $rows[] = $headerRow;
                $this->headerRows[] = ++$row;

                $this->donutRanges[] = [
                    'title' => "{$className} — {$month['label']}",
                    'catRange' => "G{$donutTopRow}:G".($donutTopRow + 1),
                    'valRange' => "H{$donutTopRow}:H".($donutTopRow + 1),
                ];

                foreach ($records as $idx => $record) {
                    $rows[] = [
                        $idx + 1,
                        $record['name'],
                        $record['total_lines'],
                        $record['target_lines'],
                        $record['is_tuntas'] ? '✅ Tuntas' : '❌ Tidak Tuntas',
                    ];
                    $row++;
                }

                $rows[] = [''];
                $row++;
            }
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

    /**
     * Satu diagram donat per bulan per halaqoh, ditumpuk vertikal di kolom J+ supaya
     * tidak pernah bertabrakan satu sama lain berapa pun jumlah murid di tiap bagian.
     *
     * @return Chart[]
     */
    public function charts(): array
    {
        $charts = [];
        $sheetTitle = $this->title();

        foreach ($this->donutRanges as $i => $range) {
            $categories = [new DataSeriesValues('String', "'{$sheetTitle}'!{$range['catRange']}", null, 2)];
            $values = [new DataSeriesValues('Number', "'{$sheetTitle}'!{$range['valRange']}", null, 2)];

            $series = new DataSeries(
                DataSeries::TYPE_DOUGHNUTCHART,
                null,
                [0],
                [],
                $categories,
                $values
            );

            $plotArea = new PlotArea(null, [$series]);
            $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
            $title = new Title("Ketuntasan {$range['title']}");

            $chart = new Chart("ketuntasan_{$i}", $title, $legend, $plotArea);

            $topRow = 2 + ($i * 16);
            $chart->setTopLeftPosition('J'.$topRow);
            $chart->setBottomRightPosition('O'.($topRow + 14));

            $charts[] = $chart;
        }

        return $charts;
    }
}
