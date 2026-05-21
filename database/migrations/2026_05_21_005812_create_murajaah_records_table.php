<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('murajaah_records', function (Blueprint $table) {
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

            $table->decimal('fluency_score', 5, 2)->nullable();
            $table->decimal('tajwid_score', 5, 2)->nullable();
            $table->decimal('makhraj_score', 5, 2)->nullable();
            $table->decimal('overall_score', 5, 2)->nullable();

            $table->enum('status', [
                'passed',
                'repeat',
                'needs_improvement',
            ])->default('needs_improvement');

            $table->text('notes')->nullable();
            $table->date('reviewed_at');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['student_id', 'reviewed_at']);
            $table->index(['teacher_id', 'reviewed_at']);
            $table->index(['surah_id', 'ayah_start', 'ayah_end']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('murajaah_records');
    }
};