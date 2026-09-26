<?php

namespace App\Support;

/**
 * Target & capaian ditulis cukup ayat/halaman akhirnya: "34-38" -> "38", "1-5, 7" -> "7".
 * Rentang tetap dipakai untuk riwayat per setoran & murajaah.
 */
final class AyahLabel
{
    public static function end(mixed $value, string $empty = '-'): string
    {
        if ($value === null || $value === '') {
            return $empty;
        }
        if (is_int($value)) {
            return (string) $value;
        }

        return preg_match_all('/\d+/', (string) $value, $numbers) ? (string) max(array_map('intval', $numbers[0])) : (string) $value;
    }
}
