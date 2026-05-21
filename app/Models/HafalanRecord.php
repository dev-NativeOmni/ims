<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HafalanRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'surah_id',
        'ayah_start',
        'ayah_end',
        'submission_type',
        'score',
        'status',
        'notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'teacher_id' => 'integer',
            'surah_id' => 'integer',
            'ayah_start' => 'integer',
            'ayah_end' => 'integer',
            'score' => 'decimal:2',
            'submitted_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }

    public function getAyahRangeAttribute(): string
    {
        return $this->ayah_start . ' - ' . $this->ayah_end;
    }

    public function getSubmissionTypeLabelAttribute(): string
    {
        return match ($this->submission_type) {
            'new' => 'Baru',
            'continuation' => 'Lanjutan',
            'revision' => 'Perbaikan',
            default => '-',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'passed' => 'Lulus',
            'repeat' => 'Ulang',
            'needs_improvement' => 'Perlu Perbaikan',
            default => '-',
        };
    }
}