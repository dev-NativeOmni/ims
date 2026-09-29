<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nama latin Surah 45 & 74 masih pakai transliterasi Inggris ("Al-Jathiyah",
 * "Al-Muddaththir"), disamakan ke ejaan Indonesia yang biasa dipakai guru
 * ("Al-Jatsiyah", "Al-Muddatsir") -- pola sama seperti perbaikan Al-Kautsar.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('surahs')->where('number', 45)->where('name_latin', 'Al-Jathiyah')->update(['name_latin' => 'Al-Jatsiyah']);
        DB::table('surahs')->where('number', 74)->where('name_latin', 'Al-Muddaththir')->update(['name_latin' => 'Al-Muddatsir']);
    }

    public function down(): void
    {
        DB::table('surahs')->where('number', 45)->where('name_latin', 'Al-Jatsiyah')->update(['name_latin' => 'Al-Jathiyah']);
        DB::table('surahs')->where('number', 74)->where('name_latin', 'Al-Muddatsir')->update(['name_latin' => 'Al-Muddaththir']);
    }
};
