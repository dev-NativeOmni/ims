<?php

namespace App\Support;

use App\Models\Surah;
use Illuminate\Support\Collection;

/**
 * Cocokkan nama surah tulisan bebas (mis. dari Prosem: "Al Ashr", "Quroisy", "Asy Syarh") dengan data
 * surah ("Al-'Asr", "Quraish", "Ash-Sharh"): kata sandang dibuang, ejaan disamakan (sy/sh/ts -> s,
 * th -> t, q -> k, gh -> g, dz/dh -> z), lalu dibandingkan kerangka konsonannya. Juz 30 & Al-Fatihah
 * diutamakan (Ummi Kelas 10).
 */
class SurahNameMatcher
{
    private const ARTICLES = ['al', 'an', 'at', 'as', 'asy', 'ash', 'ad', 'adz', 'az', 'ar', 'ath', 'ats', 'ad-', 'adh'];

    /** @var array<string, int>|null kerangka => nomor surah */
    private ?array $index = null;

    public function __construct(private ?Collection $surahs = null) {}

    public function match(?string $name): ?int
    {
        $key = self::skeleton((string) $name);
        if ($key === '') {
            return null;
        }

        // Kerangka persis dulu ("Al Lail" = ll, bukan Al-A'la = l); huruf kembar disamakan hanya sebagai cadangan.
        return $this->index()[$key] ?? $this->index()['~'.self::squeeze($key)] ?? null;
    }

    public static function skeleton(string $name): string
    {
        $name = strtolower(trim($name));
        $tokens = preg_split('/[\s\-]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) > 1 && in_array(preg_replace('/[^a-z]/', '', $tokens[0]), self::ARTICLES, true)) {
            array_shift($tokens);
        }
        $word = preg_replace('/[^a-z]/', '', implode('', $tokens));
        $word = strtr($word, ['sy' => 's', 'sh' => 's', 'ts' => 's', 'th' => 't', 'dz' => 'z', 'dh' => 'd', 'gh' => 'g', 'kh' => 'k', 'q' => 'k']);

        return (string) preg_replace('/[aeiou]/', '', $word);
    }

    /** Huruf kembar disamakan ("Muthofifin" mtffn = "Mutaffifin" mtfffn). */
    public static function squeeze(string $skeleton): string
    {
        return (string) preg_replace('/(.)\1+/', '$1', $skeleton);
    }

    /**
     * Rentang surah tulisan bebas ("An Nas-Al Ikhlas", "Al-Fil- Al Asr", "As -Sarh-Al Fajr"): dicoba setiap
     * tanda "-"/"–" sebagai pemisah sampai kedua sisi dikenali; bila tidak ada, seluruh teks satu surah.
     *
     * @return array{0: int, 1: int}|null [nomor awal, nomor akhir]
     */
    public function matchRange(?string $text): ?array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }
        preg_match_all('/[-–]/u', $text, $dashes, PREG_OFFSET_CAPTURE);
        foreach ($dashes[0] as [$dash, $offset]) {
            $from = $this->match(substr($text, 0, $offset));
            $to = $this->match(substr($text, $offset + strlen($dash)));
            if ($from && $to) {
                return [$from, $to];
            }
        }
        $single = $this->match($text);

        return $single ? [$single, $single] : null;
    }

    /** @return array<string, int> */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }
        $surahs = ($this->surahs ?? Surah::query()->get(['number', 'name_latin']))
            ->sortBy(fn ($s) => ((int) $s->number === 1 || (int) $s->number >= 78) ? 0 : 1);
        $index = [];
        foreach ($surahs as $surah) {
            $skeleton = self::skeleton((string) $surah->name_latin);
            $index[$skeleton] ??= (int) $surah->number;
            $index['~'.self::squeeze($skeleton)] ??= (int) $surah->number;
        }

        return $this->index = $index;
    }
}
