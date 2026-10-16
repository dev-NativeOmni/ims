<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prosem Ummi per kelas & halaqoh (musyrif): tiap halaqoh punya Prosem sendiri (mis. X E1 Ust Fuad mulai
 * Jilid 1, Ustadzah Isti Jilid 2). Kosong = berlaku umum (kelas tanpa musyrif tertentu / semua kelas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ummi_prosem_rows', function (Blueprint $table) {
            $table->foreignId('class_room_id')->nullable()->after('semester')->constrained('class_rooms')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->after('class_room_id')->constrained('teacher_profiles')->cascadeOnDelete();
            $table->index(['academic_year', 'semester', 'class_room_id', 'teacher_id'], 'ummi_prosem_scope_index');
        });
    }

    public function down(): void
    {
        Schema::table('ummi_prosem_rows', function (Blueprint $table) {
            $table->dropIndex('ummi_prosem_scope_index');
            $table->dropConstrainedForeignId('teacher_id');
            $table->dropConstrainedForeignId('class_room_id');
        });
    }
};
