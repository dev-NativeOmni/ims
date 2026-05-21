<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hafalan_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('teacher_profiles')
                ->restrictOnDelete();

            $table->foreignId('surah_id')
                ->constrained('surahs')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('ayah_start');
            $table->unsignedSmallInteger('ayah_end');

            $table->enum('submission_type', [
                'new',
                'continuation',
                'revision',
            ])->default('new');

            $table->decimal('score', 5, 2)->nullable();

            $table->enum('status', [
                'passed',
                'repeat',
                'needs_improvement',
            ])->default('needs_improvement');

            $table->text('notes')->nullable();
            $table->date('submitted_at');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['student_id', 'submitted_at']);
            $table->index(['teacher_id', 'submitted_at']);
            $table->index(['surah_id', 'ayah_start', 'ayah_end']);
            $table->index(['status', 'submission_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hafalan_records');
    }
};