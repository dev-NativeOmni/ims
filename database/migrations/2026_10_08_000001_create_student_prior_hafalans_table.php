<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hafalan murid dari sebelum aplikasi dipakai (mis. tahun lalu): rentang surah:ayat yang sudah hafal.
 * Bukan setoran -- tidak muncul di riwayat/grafik & tidak menambah capaian triwulan mana pun, tapi
 * dihitung "sudah hafal" untuk target, capaian ayat baru & progress (HafalanProgressService::records()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_prior_hafalans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('surah_id')->constrained();
            $table->unsignedSmallInteger('ayah_start');
            $table->unsignedSmallInteger('ayah_end');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'surah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_prior_hafalans');
    }
};
