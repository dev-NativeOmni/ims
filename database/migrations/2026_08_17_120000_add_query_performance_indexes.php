<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. hafalan_records composite index for student & status & date queries
        if (Schema::hasTable('hafalan_records')) {
            Schema::table('hafalan_records', function (Blueprint $table) {
                $table->index(['student_id', 'status', 'submitted_at'], 'idx_hafalan_student_status_date');
                $table->index(['submitted_at', 'status'], 'idx_hafalan_date_status');
            });
        }

        // 2. murajaah_records composite index for student & status & date queries
        if (Schema::hasTable('murajaah_records')) {
            Schema::table('murajaah_records', function (Blueprint $table) {
                $table->index(['student_id', 'status', 'reviewed_at'], 'idx_murajaah_student_status_date');
            });
        }

        // 3. ummi_records composite index for student & date queries
        if (Schema::hasTable('ummi_records')) {
            Schema::table('ummi_records', function (Blueprint $table) {
                $table->index(['student_id', 'tanggal'], 'idx_ummi_student_date');
                $table->index(['tanggal', 'student_id'], 'idx_ummi_date_student');
            });
        }

        // 4. hafalan_targets composite index for status & date queries
        if (Schema::hasTable('hafalan_targets')) {
            Schema::table('hafalan_targets', function (Blueprint $table) {
                $table->index(['student_id', 'status', 'target_date'], 'idx_targets_student_status_date');
                $table->index(['teacher_id', 'status', 'target_date'], 'idx_targets_teacher_status_date');
            });
        }

        // 5. attendances index for student & date & status queries
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->index(['student_id', 'tanggal', 'status'], 'idx_attendance_student_date_status');
                $table->index(['tanggal', 'class_room_id'], 'idx_attendance_date_class');
            });
        }

        // 6. student_points index for student & type & date queries
        if (Schema::hasTable('student_points')) {
            Schema::table('student_points', function (Blueprint $table) {
                $table->index(['student_id', 'type', 'date'], 'idx_points_student_type_date');
            });
        }

        // 7. students index for teacher & status queries
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index(['teacher_id', 'status'], 'idx_students_teacher_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hafalan_records')) {
            Schema::table('hafalan_records', function (Blueprint $table) {
                $table->dropIndex('idx_hafalan_student_status_date');
                $table->dropIndex('idx_hafalan_date_status');
            });
        }

        if (Schema::hasTable('murajaah_records')) {
            Schema::table('murajaah_records', function (Blueprint $table) {
                $table->dropIndex('idx_murajaah_student_status_date');
            });
        }

        if (Schema::hasTable('ummi_records')) {
            Schema::table('ummi_records', function (Blueprint $table) {
                $table->dropIndex('idx_ummi_student_date');
                $table->dropIndex('idx_ummi_date_student');
            });
        }

        if (Schema::hasTable('hafalan_targets')) {
            Schema::table('hafalan_targets', function (Blueprint $table) {
                $table->dropIndex('idx_targets_student_status_date');
                $table->dropIndex('idx_targets_teacher_status_date');
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_student_date_status');
                $table->dropIndex('idx_attendance_date_class');
            });
        }

        if (Schema::hasTable('student_points')) {
            Schema::table('student_points', function (Blueprint $table) {
                $table->dropIndex('idx_points_student_type_date');
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex('idx_students_teacher_status');
            });
        }
    }
};
