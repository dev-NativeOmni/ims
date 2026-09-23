<?php

namespace App\Exports;

use App\Exports\QuarterlyReport\GrafikAkhirBulanSheet;
use App\Exports\QuarterlyReport\JurnalSheet;
use App\Exports\QuarterlyReport\PresensiSheet;
use App\Exports\QuarterlyReport\SetoranSheet;
use App\Exports\QuarterlyReport\TermIndexSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Ekspor Laporan Triwulan ke satu file .xlsx, satu sheet per tab yang tampil di
 * layar (Term/Indeks, Presensi, Jurnal, Setoran, Grafik Akhir Bulan) -- dibangun
 * dari data yang sama persis dengan yang dipakai untuk merender halaman (lihat
 * QuarterlyReportController::buildReportData()), supaya isinya selalu sinkron.
 */
class QuarterlyReportExport implements WithMultipleSheets
{
    public function __construct(private readonly array $data) {}

    public function sheets(): array
    {
        return [
            new TermIndexSheet($this->data['halaqahData']),
            new PresensiSheet($this->data['halaqahData'], $this->data['isTahfizhProgram']),
            new JurnalSheet($this->data['halaqahData']),
            new SetoranSheet($this->data['halaqahData'], $this->data['isTahfizhProgram']),
            new GrafikAkhirBulanSheet($this->data['halaqahData']),
        ];
    }
}
