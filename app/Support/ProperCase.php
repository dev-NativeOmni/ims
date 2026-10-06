<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Penulisan nama orang/tempat yang wajar: "ABRORI AL MUAMMAR" -> "Abrori Al Muammar",
 * "KAB. SUKOHARJO" -> "Kab. Sukoharjo", "kota CIREBON" -> "Kota Cirebon". Dirapikan bila teks
 * huruf besar semua, kecil semua, atau memuat kata huruf besar semua; selain itu dibiarkan
 * (mis. "Nur'Aini", "McDonald"), supaya ejaan yang disengaja tidak rusak.
 */
class ProperCase
{
    public static function apply(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = Str::squish($text);
        $isAllUpper = $text === mb_strtoupper($text);
        $isAllLower = $text === mb_strtolower($text);
        $hasCapsWord = (bool) preg_match('/(?<!\p{L})\p{Lu}{2,}(?!\p{L})/u', $text);

        return $isAllUpper || $isAllLower || $hasCapsWord
            ? mb_convert_case(mb_strtolower($text), MB_CASE_TITLE)
            : $text;
    }
}
