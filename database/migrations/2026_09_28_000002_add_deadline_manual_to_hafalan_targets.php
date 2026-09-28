<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deadline manual: target yang deadline-nya diatur sendiri oleh guru tidak lagi disesuaikan
 * otomatis ke hari aktif terakhir bulan (TargetDeadlineService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->boolean('deadline_manual')->default(false)->after('target_date');
        });
    }

    public function down(): void
    {
        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->dropColumn('deadline_manual');
        });
    }
};
