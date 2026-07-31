<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. adab_records composite index for student attendance queries
        if (Schema::hasTable('adab_records')) {
            Schema::table('adab_records', function (Blueprint $table) {
                $table->index(['student_id', 'assessment_date'], 'idx_adab_student_date');
            });
        }

        // 2. student_points composite index for points filtering
        if (Schema::hasTable('student_points')) {
            Schema::table('student_points', function (Blueprint $table) {
                $table->index(['student_id', 'date'], 'idx_points_student_date');
            });
        }

        // 3. tahfizh_exams index for exam filtering
        if (Schema::hasTable('tahfizh_exams')) {
            Schema::table('tahfizh_exams', function (Blueprint $table) {
                $table->index(['student_id', 'exam_date'], 'idx_exams_student_date');
            });
        }

        // 4. students index for classroom & status filtering
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index(['class_room_id', 'status'], 'idx_students_class_status');
            });
        }

        // 5. adab_mentor_assessments index for mentor score lookup
        if (Schema::hasTable('adab_mentor_assessments')) {
            Schema::table('adab_mentor_assessments', function (Blueprint $table) {
                $table->index(['student_id', 'year', 'month'], 'idx_mentor_student_period');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('adab_records')) {
            Schema::table('adab_records', function (Blueprint $table) {
                $table->dropIndex('idx_adab_student_date');
            });
        }

        if (Schema::hasTable('student_points')) {
            Schema::table('student_points', function (Blueprint $table) {
                $table->dropIndex('idx_points_student_date');
            });
        }

        if (Schema::hasTable('tahfizh_exams')) {
            Schema::table('tahfizh_exams', function (Blueprint $table) {
                $table->dropIndex('idx_exams_student_date');
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex('idx_students_class_status');
            });
        }

        if (Schema::hasTable('adab_mentor_assessments')) {
            Schema::table('adab_mentor_assessments', function (Blueprint $table) {
                $table->dropIndex('idx_mentor_student_period');
            });
        }
    }
};
