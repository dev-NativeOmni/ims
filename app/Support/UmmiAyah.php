<?php

namespace App\Support;

use App\Models\Surah;

/**
 * Ayat hafalan di catatan Ummi disimpan sebagai teks bebas ("1-5", "6", "1-5, 7"). Kelas ini
 * memeriksa & merapikan ayat yang melebihi jumlah ayat surahnya (mis. Al-Bayyinah "1-10", padahal
 * hanya 8 ayat) -- ayat seperti itu tidak punya posisi sehingga grafik Ummi melewatinya.
 *
 * Dipakai validasi semua jalur input Ummi, grafik Ummi, dan perintah tad:perbaiki-ayat-ummi.
 */
class UmmiAyah
{
    /** Angka ayat terbesar dalam teks, atau null bila tidak ada angka. */
    public static function maxNumber(?string $ayah): ?int
    {
        return preg_match_all('/\d+/', (string) $ayah, $m) ? max(array_map('intval', $m[0])) : null;
    }

    /** Teks ayat dengan setiap angka di atas $totalAyah diganti $totalAyah ("1-10" -> "1-8"). */
    public static function clamp(string $ayah, int $totalAyah): string
    {
        return preg_replace_callback('/\d+/', fn ($m) => (string) min((int) $m[0], $totalAyah), $ayah);
    }

    /**
     * Pesan kesalahan pertama untuk pasangan [surah_id, ayat], atau null bila semua ayat dalam batas surah.
     *
     * @param  iterable<array{0: int|string|null, 1: string|null}>  $pairs
     */
    public static function firstError(iterable $pairs): ?string
    {
        $pairs = collect($pairs)->filter(fn ($p) => filled($p[0]) && filled($p[1]));
        if ($pairs->isEmpty()) {
            return null;
        }

        $surahs = Surah::query()->whereIn('id', $pairs->pluck(0)->unique())->get(['id', 'name_latin', 'total_ayah'])->keyBy('id');
        foreach ($pairs as [$surahId, $ayah]) {
            $surah = $surahs->get((int) $surahId);
            $max = self::maxNumber($ayah);
            if ($surah && $max !== null && $max > (int) $surah->total_ayah) {
                return "Ayat hafalan {$surah->name_latin} \"{$ayah}\" melebihi jumlah ayat surah ({$surah->total_ayah} ayat).";
            }
        }

        return null;
    }
}
