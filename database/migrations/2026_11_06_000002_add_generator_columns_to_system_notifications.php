<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom yang dipakai SystemNotification (unique_hash di setiap create) dan notifikasi otomatis harian
 * (InternalNotificationService: severity, source_type, source_id). Migration 2026_05_25 yang seharusnya
 * membuatnya dilewati karena tabel sudah ada, sehingga setiap pembuatan notifikasi gagal.
 * Setiap kolom hanya ditambahkan bila belum ada (aman untuk database yang sudah memilikinya).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('system_notifications', 'unique_hash')) {
                $table->string('unique_hash', 64)->nullable()->unique();
            }
            if (! Schema::hasColumn('system_notifications', 'severity')) {
                $table->string('severity', 20)->nullable();
            }
            if (! Schema::hasColumn('system_notifications', 'source_type')) {
                $table->string('source_type')->nullable();
            }
            if (! Schema::hasColumn('system_notifications', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Tidak dihapus: kolom ini bisa saja sudah ada sebelum migration ini.
    }
};
