<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rapor per periode (term 1-4: Tengah Semester I, Semester I, Tengah Semester II, Semester II),
 * bukan lagi per semester, plus simpanan isi rapor yang dibekukan saat periode dikunci per kelas.
 * Catatan lama per semester disalin ke kedua periode di semester itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_reports', function (Blueprint $table) {
            $table->unsignedTinyInteger('term')->nullable()->after('semester');
            $table->json('snapshot')->nullable()->after('status');
            $table->timestamp('locked_at')->nullable()->after('snapshot');
            $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
            $table->foreignId('locked_class_room_id')->nullable()->after('locked_by')->constrained('class_rooms')->nullOnDelete();
        });

        // Catatan lama = periode pertama semesternya. Unique diganti ke term dulu (student_id tetap
        // terindeks untuk foreign key) sebelum salinan untuk periode kedua dibuat.
        DB::table('student_reports')->where('semester', 2)->update(['term' => 3]);
        DB::table('student_reports')->where('semester', '<>', 2)->update(['term' => 1]);

        Schema::table('student_reports', function (Blueprint $table) {
            $table->unique(['student_id', 'academic_year', 'term']);
            $table->index(['academic_year', 'term', 'locked_class_room_id']);
        });
        Schema::table('student_reports', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'academic_year', 'semester']);
        });

        DB::table('student_reports')->whereIn('term', [1, 3])->orderBy('id')->each(function ($row) {
            DB::table('student_reports')->insert([
                'student_id' => $row->student_id,
                'academic_year' => $row->academic_year,
                'semester' => $row->semester,
                'term' => $row->term + 1,
                'teacher_notes' => $row->teacher_notes,
                'tahfizh_target_term' => $row->tahfizh_target_term,
                'status' => $row->status,
                'created_by' => $row->created_by,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        DB::table('student_reports')->whereIn('term', [2, 4])->delete();

        Schema::table('student_reports', function (Blueprint $table) {
            $table->unique(['student_id', 'academic_year', 'semester']);
        });
        Schema::table('student_reports', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'academic_year', 'term']);
            $table->dropIndex(['academic_year', 'term', 'locked_class_room_id']);
            $table->dropConstrainedForeignId('locked_by');
            $table->dropConstrainedForeignId('locked_class_room_id');
            $table->dropColumn(['term', 'snapshot', 'locked_at']);
        });
    }
};
