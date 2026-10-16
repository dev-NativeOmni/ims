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

        return $this->index()[$key] ?? null;
    }

    public static function skeleton(string $name): string
    {
        $name = strtolower(trim($name));
        $tokens = preg_split('/[\s\-]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) > 1 && in_array(preg_replace('/[^a-z]/', '', $tokens[0]), self::ARTICLES, true)) {
            array_shift($tokens);
        }
        $word = preg_replace('/[^a-z]/', '', implode('', $tokens));
        $word = strtr($word, ['sy' => 's', 'sh' => 's', 'ts' => 's', 'th' => 't', 'dz' => 'z', 'dh' => 'z', 'gh' => 'g', 'kh' => 'k', 'q' => 'k']);

        return (string) preg_replace('/[aeiou]/', '', $word);
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
            $index[self::skeleton((string) $surah->name_latin)] ??= (int) $surah->number;
        }

        return $this->index = $index;
    }
}
