<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Ejaan "Tahfidz" diseragamkan menjadi "Tahfizh" juga pada pengaturan yang sudah tersimpan
 * (mis. judul kop rapor "LAPORAN TAHFIDZ, ADAB DAN TANSE").
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')
            ->where(fn ($q) => $q->where('value', 'like', '%Tahfidz%')->orWhere('value', 'like', '%TAHFIDZ%'))
            ->get(['id', 'key', 'value'])
            ->each(function ($setting) {
                DB::table('settings')->where('id', $setting->id)->update([
                    'value' => str_replace(['Tahfidz', 'TAHFIDZ', 'tahfidz'], ['Tahfizh', 'TAHFIZH', 'tahfizh'], $setting->value),
                ]);
                Cache::forget("setting:{$setting->key}");
            });
    }

    public function down(): void
    {
        // Tidak dikembalikan: ejaan lama tidak dipakai lagi.
    }
};
