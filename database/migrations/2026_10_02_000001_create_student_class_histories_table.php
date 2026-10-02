<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat kelas santri (lihat docs/riwayat-kelas.md): satu baris = santri X di kelas Y dari
 * start_date sampai end_date (null = masih di kelas itu). Dicatat otomatis setiap kelas santri
 * berubah (App\Models\StudentClassHistory::record), dipakai laporan per kelas untuk periode lalu.
 *
 * Isi awal: kelas santri saat ini, berlaku sejak awal tahun ajaran pertama aplikasi (1 Juli 2026,
 * AcademicYear::FIRST) -- belum ada perpindahan kelas yang tercatat sebelum tabel ini dibuat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_class_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_room_id')->constrained('class_rooms')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['class_room_id', 'start_date']);
            $table->index(['student_id', 'start_date']);
        });

        $now = now();
        DB::table('students')->whereNotNull('class_room_id')->orderBy('id')->select(['id', 'class_room_id'])
            ->chunk(500, function ($students) use ($now) {
                DB::table('student_class_histories')->insert($students->map(fn ($student) => [
                    'student_id' => $student->id,
                    'class_room_id' => $student->class_room_id,
                    'start_date' => '2026-07-01',
                    'end_date' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_class_histories');
    }
};
