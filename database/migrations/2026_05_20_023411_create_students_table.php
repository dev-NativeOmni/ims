<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('class_room_id')
                ->nullable()
                ->constrained('class_rooms')
                ->nullOnDelete();

            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teacher_profiles')
                ->nullOnDelete();

            $table->string('name', 150);
            $table->string('student_number', 100)->nullable()->unique();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['class_room_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};