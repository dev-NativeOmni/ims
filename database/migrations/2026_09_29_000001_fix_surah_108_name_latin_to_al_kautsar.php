<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nama latin Surah 108 salah transliterasi ("Al-Kawthar"), seharusnya "Al-Kautsar"
 * mengikuti ejaan bahasa Indonesia yang dipakai guru sehari-hari.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('surahs')->where('number', 108)->where('name_latin', 'Al-Kawthar')->update(['name_latin' => 'Al-Kautsar']);
    }

    public function down(): void
    {
        DB::table('surahs')->where('number', 108)->where('name_latin', 'Al-Kautsar')->update(['name_latin' => 'Al-Kawthar']);
    }
};
