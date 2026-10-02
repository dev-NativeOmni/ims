<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Istilah predikat & deskripsi Adab sesuai permintaan sekolah (2 Okt 2026): A Sangat Baik / Mumtaz,
 * B Baik / Jayyid, C Cukup / Maqbul, D Kurang / Dho'if, E Sangat Kurang / Dho'if Jiddan.
 * Pengaturan yang sudah tersimpan diganti bagian istilah & deskripsinya saja (bobot & batas nilai tetap);
 * belum tersimpan = otomatis memakai nilai bawaan baru (sama) di Setting::ADAB_SCORING_DEFAULTS.
 */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'adab_scoring')->first();
        if (! $row) {
            return;
        }

        $saved = json_decode((string) $row->value, true) ?: [];
        // Ditulis langsung (bukan merujuk konstanta) supaya migration ini tetap sama walau bawaan di kode berubah.
        $saved['grades'] = [
            'A' => ['term' => 'Sangat Baik', 'arabic' => 'Mumtaz'],
            'B' => ['term' => 'Baik', 'arabic' => 'Jayyid'],
            'C' => ['term' => 'Cukup', 'arabic' => 'Maqbul'],
            'D' => ['term' => 'Kurang', 'arabic' => "Dho'if"],
            'E' => ['term' => 'Sangat Kurang', 'arabic' => "Dho'if Jiddan"],
        ];
        $saved['descriptions'] = [
            'A' => 'Menunjukkan sikap yang sangat sopan, santun, dan menghormati guru serta teman.',
            'B' => 'Menunjukkan kesopanan kepada guru dan teman.',
            'C' => 'Cukup menunjukkan sikap sopan kepada guru dan teman, namun masih perlu ditingkatkan.',
            'D' => 'Kurang menunjukkan sikap sopan kepada guru dan teman serta perlu mendapat bimbingan.',
            'E' => 'Belum menunjukkan sikap sopan kepada guru dan teman serta membutuhkan bimbingan lebih lanjut.',
        ];

        DB::table('settings')->where('key', 'adab_scoring')->update(['value' => json_encode($saved)]);
        Cache::forget('setting:adab_scoring');
    }

    public function down(): void
    {
        // Tidak dikembalikan: teks lama tidak dipakai lagi (ubah lewat Pengaturan Adab bila perlu).
    }
};
