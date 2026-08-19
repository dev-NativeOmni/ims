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
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Unique identifier e.g. first_hafalan, juz_30');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->default('star')->comment('Heroicon name e.g. star, trophy, book-open');
            $table->enum('type', [
                'count_hafalan',
                'passed_hafalan',
                'percent_quran',
                'count_murajaah',
                'completed_targets',
                'clean_target',
                'score_quality',
                'completed_juz',
            ])->comment('Criteria type for badge evaluation');
            $table->decimal('target_value', 10, 2)->default(0)->comment('Threshold e.g. 5 setorans, 80 score');
            $table->unsignedTinyInteger('target_juz')->nullable()->comment('Specific juz number for completed_juz type');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};
