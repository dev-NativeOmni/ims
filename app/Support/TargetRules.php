<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Aturan target hafalan otomatis yang bisa diatur di Pengaturan Target Hafalan:
 * baris per pertemuan per level, juz wajib di bagian belakang, dan batas paling
 * akhir murid boleh pindah ke depan (Juz 1). Satu-satunya sumber nilai ini.
 */
class TargetRules
{
    public const LEVELS = ['tahsin' => 'Tahsin', 'reguler' => 'Reguler', 'akselerasi' => 'Akselerasi'];

    public const DEFAULT_LEVEL_LINES = ['tahsin' => 3, 'reguler' => 5, 'akselerasi' => 7];

    /**
     * Target paten per triwulan (baris) menurut panduan: Reguler 195, Akselerasi 240. Level tanpa
     * angka paten (Tahsin) tetap memakai baris per pertemuan x pertemuan aktif.
     */
    public const DEFAULT_TERM_LINES = ['tahsin' => null, 'reguler' => 195, 'akselerasi' => 240];

    /** Juz 30 sampai juz ini wajib sebelum boleh pindah ke depan. */
    public const DEFAULT_MANDATORY_UNTIL = 29;

    /** Batas paling akhir pindah ke depan. */
    public const DEFAULT_LATEST_SWITCH = 27;

    /**
     * @return array<string, int>
     */
    public static function levelLines(): array
    {
        $saved = json_decode((string) Setting::get('target_level_lines'), true) ?: [];

        return collect(self::DEFAULT_LEVEL_LINES)
            ->map(fn ($default, $level) => max(1, (int) ($saved[$level] ?? $default)))
            ->all();
    }

    /**
     * Target paten per triwulan tiap level; null = pakai baris per pertemuan.
     *
     * @return array<string, int|null>
     */
    public static function termLines(): array
    {
        $saved = json_decode((string) Setting::get('target_term_lines'), true);
        $saved = is_array($saved) ? $saved : [];

        return collect(self::DEFAULT_TERM_LINES)
            ->map(function ($default, $level) use ($saved) {
                $value = array_key_exists($level, $saved) ? $saved[$level] : $default;

                return $value === null || $value === '' ? null : max(1, (int) $value);
            })
            ->all();
    }

    /**
     * Target paten per triwulan untuk level murid, atau null bila level ini memakai
     * baris per pertemuan (Tahsin) / target dibuat guru (Ummi).
     */
    public static function termLinesForLevel(?string $level): ?int
    {
        if ($level === null || $level === 'ummi') {
            return null;
        }

        $termLines = self::termLines();

        // Level tanpa angka paten (Tahsin = null) sengaja tidak jatuh ke angka Reguler.
        return array_key_exists($level, $termLines) ? $termLines[$level] : $termLines['reguler'];
    }

    /**
     * Baris per pertemuan untuk level murid; null untuk Ummi (target dibuat guru).
     */
    public static function linesForLevel(?string $level): ?int
    {
        if ($level === 'ummi') {
            return null;
        }

        $lines = self::levelLines();

        return $lines[$level] ?? $lines['reguler'];
    }

    public static function mandatoryUntil(): int
    {
        return (int) Setting::get('target_mandatory_until_juz', self::DEFAULT_MANDATORY_UNTIL);
    }

    public static function latestSwitch(): int
    {
        return (int) Setting::get('target_latest_switch_juz', self::DEFAULT_LATEST_SWITCH);
    }

    /**
     * Juz setelah mana murid boleh pindah ke depan, mis. [29, 28, 27].
     *
     * @return array<int, int>
     */
    public static function switchOptions(): array
    {
        return range(self::mandatoryUntil(), self::latestSwitch());
    }

    /**
     * @param  array<string, int>  $levelLines
     * @param  array<string, int|string|null>|null  $termLines  kosong = pakai baris per pertemuan
     */
    public static function save(array $levelLines, int $mandatoryUntil, int $latestSwitch, ?array $termLines = null): void
    {
        if ($termLines !== null) {
            Setting::set('target_term_lines', json_encode(collect(self::DEFAULT_TERM_LINES)
                ->map(fn ($default, $level) => filled($termLines[$level] ?? null) ? max(1, (int) $termLines[$level]) : null)
                ->all()));
        }

        Setting::set('target_level_lines', json_encode(collect(self::DEFAULT_LEVEL_LINES)
            ->map(fn ($default, $level) => max(1, (int) ($levelLines[$level] ?? $default)))
            ->all()));
        Setting::set('target_mandatory_until_juz', (string) $mandatoryUntil);
        Setting::set('target_latest_switch_juz', (string) $latestSwitch);
    }
}
