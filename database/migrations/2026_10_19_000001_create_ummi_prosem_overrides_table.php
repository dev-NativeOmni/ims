<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Target TM murid yang disamakan musyrif dengan mayoritas halaqoh (tombol "Samakan" di Alur Pembelajaran):
 * target murid = TM ini bila jurnalnya masih di belakang (UmmiProsemService::studentProgress).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ummi_prosem_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 9);
            $table->unsignedTinyInteger('semester');
            $table->unsignedSmallInteger('tm_no');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ummi_prosem_overrides');
    }
};
