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

    private function pekanLabel(int $p, array $pekanDates): string
    {
        $dates = $pekanDates[$p] ?? [];

        if (empty($dates)) {
            return "PEKAN {$p} (Libur)";
        }

        return "PEKAN {$p} (".implode(' & ', array_column($dates, 'label')).')';
    }
}
