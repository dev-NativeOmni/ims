<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris Program Semester metode Ummi (lihat migrasi create_ummi_prosem_rows_table &
 * App\Services\UmmiProsemService).
 */
class UmmiProsemRow extends Model
{
    protected $fillable = [
        'academic_year', 'semester', 'class_room_id', 'teacher_id', 'position', 'jilid',
        'peraga_start', 'peraga_end', 'buku_start', 'buku_end',
        'hafalan_start_surah_id', 'hafalan_end_surah_id', 'drill_start_surah_id', 'drill_end_surah_id',
        'weeks', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'class_room_id' => 'integer',
            'teacher_id' => 'integer',
            'position' => 'integer',
            'peraga_start' => 'integer',
            'peraga_end' => 'integer',
            'buku_start' => 'integer',
            'buku_end' => 'integer',
            'weeks' => 'array',
        ];
    }

    /** Jumlah tatap muka baris ini = jumlah sebaran per pekan. */
    public function getTmAttribute(): int
    {
        return (int) collect($this->weeks ?? [])->flatten()->sum();
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    public function hafalanStart(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'hafalan_start_surah_id');
    }

    public function hafalanEnd(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'hafalan_end_surah_id');
    }

    public function drillStart(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'drill_start_surah_id');
    }

    public function drillEnd(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'drill_end_surah_id');
    }
}
