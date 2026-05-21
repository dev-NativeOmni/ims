<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surahs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('number')->unique();
            $table->string('name_ar', 100)->nullable();
            $table->string('name_latin', 100);
            $table->unsignedSmallInteger('total_ayah');
            $table->unsignedTinyInteger('juz_start')->nullable();
            $table->unsignedTinyInteger('juz_end')->nullable();
            $table->timestamps();

            $table->index('name_latin');
            $table->index(['juz_start', 'juz_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surahs');
    }
};