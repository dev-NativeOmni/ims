<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ayahs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('surah_id')
                ->constrained('surahs')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedTinyInteger('juz')->nullable();
            $table->longText('text_ar')->nullable();
            $table->longText('translation_id')->nullable();
            $table->timestamps();

            $table->unique(['surah_id', 'ayah_number']);
            $table->index('juz');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayahs');
    }
};
