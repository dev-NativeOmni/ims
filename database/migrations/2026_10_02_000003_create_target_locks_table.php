<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci target hafalan per kelas per bulan (App\Models\TargetLock): target bulan yang dikunci tidak
 * bisa diubah/dihapus; status tuntas tetap diperbarui otomatis dari setoran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained('class_rooms')->cascadeOnDelete();
            $table->date('month'); // tanggal 1 bulan yang dikunci
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at');
            $table->timestamps();

            $table->unique(['class_room_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_locks');
    }
};
