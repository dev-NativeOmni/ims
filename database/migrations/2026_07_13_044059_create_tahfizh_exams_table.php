<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfizh_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teacher_profiles')->onDelete('cascade');
            $table->integer('juz')->nullable();
            $table->foreignId('surah_id')->nullable()->constrained('surahs')->onDelete('set null');
            $table->integer('ayah_start')->nullable();
            $table->integer('ayah_end')->nullable();
            $table->integer('q1');
            $table->integer('q2');
            $table->integer('q3');
            $table->integer('q4');
            $table->integer('q5');
            $table->decimal('total_score', 5, 2);
            $table->text('notes')->nullable();
            $table->date('exam_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfizh_exams');
    }
};
