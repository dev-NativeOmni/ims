<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identitas murid menurut Dapodik (diisi lewat Impor Dapodik, DapodikImportService):
 * - NIS & NISN tampil di web dan rapor cetak (Student::nisNisn());
 * - rombel hanya untuk rapor cetak (Student::reportClassName()); web tetap kelas pembelajaran;
 * - tempat lahir (birth_place) data murid biasa, ikut diperbarui impor bersama tanggal lahir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('dapodik_nis', 30)->nullable()->after('student_number');
            $table->string('dapodik_nisn', 20)->nullable()->after('dapodik_nis');
            $table->string('dapodik_rombel', 50)->nullable()->after('dapodik_nisn');
            $table->timestamp('dapodik_synced_at')->nullable()->after('dapodik_rombel');
            $table->string('birth_place', 100)->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['dapodik_nis', 'dapodik_nisn', 'dapodik_rombel', 'dapodik_synced_at', 'birth_place']);
        });
    }
};
