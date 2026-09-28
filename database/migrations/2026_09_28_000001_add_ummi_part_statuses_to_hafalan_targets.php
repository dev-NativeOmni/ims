<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Target Ummi dinilai terpisah: status Buku (Jilid & Halaman Buku) dan status Hafalan Surah
 * (surah & ayat). Nilai: active | completed | missed; null bila bagian itu tidak ditargetkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->string('book_status', 20)->nullable()->after('status');
            $table->string('surah_status', 20)->nullable()->after('book_status');
        });
    }

    public function down(): void
    {
        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->dropColumn(['book_status', 'surah_status']);
        });
    }
};
