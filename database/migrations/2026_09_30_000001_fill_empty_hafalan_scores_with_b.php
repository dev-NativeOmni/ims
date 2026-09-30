<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nilai hafalan yang masih kosong diisi B: setoran hafalan -> 85 (skala input A=95, B=85, C=75,
 * D=65, E=55), sesi Ummi -> "B". Ke depan nilai kosong otomatis B lewat model
 * (HafalanRecordSurah::DEFAULT_SCORE, UmmiRecord::DEFAULT_NILAI).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('hafalan_record_surahs')->whereNull('score')->update(['score' => 85]);

        DB::table('ummi_records')
            ->where(fn ($q) => $q->whereNull('nilai')->orWhere('nilai', ''))
            ->update(['nilai' => 'B']);
    }

    public function down(): void
    {
        // Tidak bisa dibedakan mana yang dulu kosong -- pulihkan dari backup database bila perlu.
    }
};
