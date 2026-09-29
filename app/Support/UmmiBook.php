<?php

namespace App\Support;

use App\Services\UmmiProgressService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Buku Ummi Dewasa (Kelas 10): Jilid 1-3, Gharib, Tajwid -- tidak ada Jilid 4 dst.
 * Halaman diinput sebagai awal & akhir lalu disimpan ke ummi_halaman sebagai "12-15" (atau "12").
 */
final class UmmiBook
{
    public const BOOKS = ['Jilid 1', 'Jilid 2', 'Jilid 3', 'Gharib', 'Tajwid'];

    /**
     * Pilihan dropdown. Nilai lama di luar daftar (mis. "Jilid 4") tetap ikut ditampilkan supaya
     * tidak hilang diam-diam saat form disimpan -- guru tinggal mengoreksinya.
     *
     * @return array<string, string> value => label
     */
    public static function options(?string $current = null): array
    {
        $options = array_combine(self::BOOKS, self::BOOKS);
        if (filled($current) && ! in_array($current, self::BOOKS, true)) {
            $options[$current] = $current.' (tidak valid, mohon dikoreksi)';
        }

        return $options;
    }

    /** "12" / "12-15" / "Halaman 12" -> [12, 15]; kosong -> [null, null]. */
    public static function splitHalaman(?string $halaman): array
    {
        if (! preg_match_all('/\d+/', (string) $halaman, $numbers)) {
            return [null, null];
        }
        $numbers = array_map('intval', $numbers[0]);

        return [min($numbers), max($numbers)];
    }

    /** [12, 15] -> "12-15", [12, 12] / [12, null] -> "12", kosong -> null. */
    public static function joinHalaman(mixed $awal, mixed $akhir): ?string
    {
        $awal = filled($awal) ? (int) $awal : null;
        $akhir = filled($akhir) ? (int) $akhir : null;
        $awal ??= $akhir;
        $akhir ??= $awal;

        if ($awal === null) {
            return null;
        }

        return $awal === $akhir ? (string) $awal : "{$awal}-{$akhir}";
    }

    /**
     * Nilai ummi_halaman yang disimpan: dari halaman awal & akhir; kalau yang terkirim masih field
     * lama "ummi_halaman" (draf/klien lama), dinormalkan juga jadi "12-15".
     */
    public static function halamanFromInput(array $data): ?string
    {
        if (filled($data['ummi_halaman_awal'] ?? null) || filled($data['ummi_halaman_akhir'] ?? null)) {
            return self::joinHalaman($data['ummi_halaman_awal'] ?? null, $data['ummi_halaman_akhir'] ?? null);
        }

        return self::joinHalaman(...self::splitHalaman($data['ummi_halaman'] ?? null));
    }

    /**
     * Aturan validasi Jilid + Halaman awal/akhir. $prefix untuk input array, mis. "records.*.".
     * $current = jilid tersimpan (boleh dipertahankan walau di luar daftar, lihat options()).
     */
    public static function rules(string $prefix = '', ?string $current = null): array
    {
        $allowed = filled($current) ? [...self::BOOKS, $current] : self::BOOKS;

        return [
            $prefix.'ummi_jilid' => ['nullable', 'string', Rule::in($allowed)],
            $prefix.'ummi_halaman_awal' => ['nullable', 'integer', 'min:1', 'max:999'],
            $prefix.'ummi_halaman_akhir' => ['nullable', 'integer', 'min:1', 'max:999'],
            $prefix.'ummi_halaman' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Validasi lanjutan: halaman akhir >= awal, dan Jilid 1-3 hanya 40 halaman
     * (lihat UmmiProgressService::PAGES_PER_JILID). Pakai di $validator->after().
     */
    public static function checkPages(Validator $validator, array $item, string $prefix = ''): void
    {
        $awal = $item['ummi_halaman_awal'] ?? null;
        $akhir = $item['ummi_halaman_akhir'] ?? null;
        if (is_numeric($awal) && is_numeric($akhir) && (int) $akhir < (int) $awal) {
            $validator->errors()->add($prefix.'ummi_halaman_akhir', 'Halaman akhir tidak boleh lebih kecil dari halaman awal.');
        }

        if (! str_starts_with((string) ($item['ummi_jilid'] ?? ''), 'Jilid ')) {
            return;
        }
        foreach (['ummi_halaman_awal', 'ummi_halaman_akhir'] as $field) {
            if (is_numeric($item[$field] ?? null) && (int) $item[$field] > UmmiProgressService::PAGES_PER_JILID) {
                $validator->errors()->add($prefix.$field, 'Halaman '.$item['ummi_jilid'].' maksimal '.UmmiProgressService::PAGES_PER_JILID.'.');
            }
        }
    }
}
