<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Program Semester (Prosem) metode Ummi Kelas 10: satu baris = satu unit materi (Jilid, halaman peraga &
 * buku, hafalan/ziyadah, drill/muraja'ah) dengan sebaran tatap muka per pekan. Dipakai untuk target per
 * pertemuan, target Ummi bulanan otomatis & layar Alur Pembelajaran (docs/prosem-ummi.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ummi_prosem_rows', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 9);
            $table->unsignedTinyInteger('semester');
            $table->unsignedSmallInteger('position');
            $table->string('jilid', 20)->nullable();
            $table->unsignedSmallInteger('peraga_start')->nullable();
            $table->unsignedSmallInteger('peraga_end')->nullable();
            $table->unsignedSmallInteger('buku_start')->nullable();
            $table->unsignedSmallInteger('buku_end')->nullable();
            $table->foreignId('hafalan_start_surah_id')->nullable()->constrained('surahs')->nullOnDelete();
            $table->foreignId('hafalan_end_surah_id')->nullable()->constrained('surahs')->nullOnDelete();
            $table->foreignId('drill_start_surah_id')->nullable()->constrained('surahs')->nullOnDelete();
            $table->foreignId('drill_end_surah_id')->nullable()->constrained('surahs')->nullOnDelete();
            // Sebaran tatap muka: {"7": {"4": 4}} = bulan Juli pekan ke-4 sebanyak 4 TM.
            $table->json('weeks')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['academic_year', 'semester', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ummi_prosem_rows');
    }
};
