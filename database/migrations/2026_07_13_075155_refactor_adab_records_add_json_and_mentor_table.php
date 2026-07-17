<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add flexible JSON answers column to adab_records and make q1-q15 nullable
        Schema::table('adab_records', function (Blueprint $table) {
            // answers stores dynamic question responses as JSON:
            // {"cat_0": [true, false, true, ...], "cat_1": [...], ...}
            $table->json('answers')->nullable()->after('notes');

            // student_score: pure self-assessment score (0–100)
            $table->decimal('student_score', 5, 2)->nullable()->after('answers');

            // Make q1-q15 nullable
            for ($i = 1; $i <= 15; $i++) {
                $table->boolean("q{$i}")->nullable()->change();
            }
        });

        // 2. Migrate existing q1-q15 data to answers JSON
        $records = DB::table('adab_records')->get();
        foreach ($records as $record) {
            $cat0 = [];
            $cat1 = [];
            $cat2 = [];
            for ($i = 1; $i <= 5; $i++) {
                $cat0[] = (bool) $record->{"q{$i}"};
            }
            for ($i = 6; $i <= 10; $i++) {
                $cat1[] = (bool) $record->{"q{$i}"};
            }
            for ($i = 11; $i <= 15; $i++) {
                $cat2[] = (bool) $record->{"q{$i}"};
            }

            $answers = ['cat_0' => $cat0, 'cat_1' => $cat1, 'cat_2' => $cat2];

            // Recalculate student score: avg of all 15 answers * 100
            $allAnswers = array_merge($cat0, $cat1, $cat2);
            $studentScore = count($allAnswers) > 0
                ? round((array_sum($allAnswers) / count($allAnswers)) * 100, 2)
                : 0;

            DB::table('adab_records')
                ->where('id', $record->id)
                ->update([
                    'answers' => json_encode($answers),
                    'student_score' => $studentScore,
                ]);
        }

        // 3. Create adab_mentor_assessments table (periodic mentor scoring)
        Schema::create('adab_mentor_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');   // e.g. 2026
            $table->unsignedTinyInteger('month');   // 1–12 (use 0 for semester-based)
            $table->string('period_label')->nullable(); // e.g. "Juli 2026" or "Semester 1"
            $table->unsignedTinyInteger('mentor_score'); // 0–100
            $table->text('notes')->nullable();
            $table->timestamps();

            // One mentor score per student per month
            $table->unique(['student_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adab_mentor_assessments');

        Schema::table('adab_records', function (Blueprint $table) {
            $table->dropColumn(['answers', 'student_score']);

            for ($i = 1; $i <= 15; $i++) {
                $table->boolean("q{$i}")->nullable(false)->change();
            }
        });
    }
};
