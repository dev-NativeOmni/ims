<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to students table
        Schema::table('students', function (Blueprint $table) {
            // Level: tahsin, reguler, akselerasi, ummi
            $table->string('tahfizh_level', 50)->nullable()->default('reguler')->after('status');
        });

        // 2. Add fields to student_reports table
        Schema::table('student_reports', function (Blueprint $table) {
            $table->string('tahfizh_target_term', 255)->nullable()->after('teacher_notes');
        });

        // 3. Create ummi_records table
        Schema::create('ummi_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->unsignedInteger('tatap_muka');
            $table->date('tanggal');
            $table->foreignId('hafalan_surah_id')->nullable()->constrained('surahs')->nullOnDelete();
            $table->string('hafalan_ayah', 100)->nullable();
            $table->string('ummi_jilid', 150)->nullable();
            $table->string('ummi_halaman', 100)->nullable();
            $table->string('materi', 255)->nullable();
            $table->string('nilai', 50)->nullable();
            $table->string('disimak_guru', 50)->nullable();
            $table->string('disimak_ortu', 50)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ummi_records');

        Schema::table('student_reports', function (Blueprint $table) {
            $table->dropColumn('tahfizh_target_term');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('tahfizh_level');
        });
    }
};
