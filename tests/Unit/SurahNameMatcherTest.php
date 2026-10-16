<?php

namespace Tests\Unit;

use App\Support\SurahNameMatcher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Nama surah tulisan Prosem -> nomor surah (ejaan Indonesia vs data surah).
 */
class SurahNameMatcherTest extends TestCase
{
    private function matcher(): SurahNameMatcher
    {
        $names = [1 => 'Al-Fatihah', 78 => 'An-Naba', 79 => "An-Nazi'at", 80 => 'Abasa', 83 => 'Al-Mutaffifin', 84 => 'Al-Inshiqaq', 86 => 'At-Tariq', 87 => "Al-A'la", 88 => 'Al-Ghashiyah',
            91 => 'Ash-Shams', 92 => 'Al-Lail', 93 => 'Ad-Duha', 94 => 'Ash-Sharh', 95 => 'At-Tin', 97 => 'Al-Qadr', 101 => "Al-Qari'ah", 102 => 'At-Takatsur', 103 => "Al-'Asr",
            105 => 'Al-Fil', 106 => 'Quraish', 108 => 'Al-Kautsar', 112 => 'Al-Ikhlas', 114 => 'An-Nas'];

        return new SurahNameMatcher(collect($names)->map(fn ($name, $number) => (object) ['number' => $number, 'name_latin' => $name])->values());
    }

    #[Test]
    public function it_matches_indonesian_spellings_and_free_ranges(): void
    {
        $m = $this->matcher();
        $this->assertSame(92, $m->match('Al Lail'));
        $this->assertSame(87, $m->match("Al 'Ala"));
        $this->assertSame(83, $m->match('Al Muthofifin'));
        $this->assertSame(93, $m->match('Ad Dhuha'));
        $this->assertSame(106, $m->match('Al qurois'));
        $this->assertSame([114, 112], $m->matchRange('An Nas-Al Ikhlas'));
        $this->assertSame([105, 103], $m->matchRange('Al-Fil- Al Asr'));
        $this->assertSame([94, 93], $m->matchRange('As -Sarh-Ad Dhuha'));
        $this->assertSame([97, 87], $m->matchRange("Al- Qodr-Al 'Ala"));
        $this->assertSame([1, 1], $m->matchRange('Al Fatihah'));
        $this->assertNull($m->matchRange('Ujian'));
    }
}
