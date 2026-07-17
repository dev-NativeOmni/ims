<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hafalan_targets', function (Blueprint $table) {
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

            $table->date('target_date');

            $table->enum('status', [
                'active',
                'planned',
                'in_progress',
                'completed',
                'missed',
                'cancelled',
            ])->default('active');

            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['student_id', 'target_date']);
            $table->index(['teacher_id', 'target_date']);
            $table->index(['surah_id', 'ayah_start', 'ayah_end']);
            $table->index(['status', 'target_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hafalan_targets');
    }
};
