<?php

namespace App\Exports\QuarterlyReport\Concerns;

/**
 * Label kolom "Pekan N" berisi hari & tanggal pertemuan aktif sungguhan
 * (jadwal kelas x kalender akademik), bukan sekadar nomor pekan generik.
 */
trait PekanLabeling
{
    /**
     * Nomor pekan yang punya pertemuan aktif. Pekan libur tidak dijadikan kolom -- sama dengan
     * tampilan web (hanya pertemuan aktif).
     *
     * @return int[]
     */
    private function activePekans(array $pekanDates): array
    {
        return array_values(array_filter(range(1, 5), fn (int $p) => ! empty($pekanDates[$p] ?? [])));
    }

    /** $twoLines: "PEKAN 3\n(Selasa, 21 Jul)" untuk header kolom sempit (sel perlu wrap text). */
    private function pekanLabel(int $p, array $pekanDates, bool $twoLines = false): string
    {
        $dates = $pekanDates[$p] ?? [];
        $separator = $twoLines ? "\n" : ' ';

        if (empty($dates)) {
            return "PEKAN {$p}{$separator}(Libur)";
        }

        return "PEKAN {$p}{$separator}(".implode(' & ', array_column($dates, 'label')).')';
    }
}
