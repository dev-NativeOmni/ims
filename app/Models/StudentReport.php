<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year',
        'semester',
        'term', // periode rapor 1-4, lihat StudentReportController::REPORT_PERIODS
        'teacher_notes',
        'tahfizh_target_term',
        'status', // 'draft', 'published', 'locked'
        'created_by',
        'snapshot', // isi rapor cetak yang dibekukan saat periode dikunci
        'locked_at',
        'locked_by',
        'locked_class_room_id',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'locked_at' => 'datetime',
        ];
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSemesterLabelAttribute(): string
    {
        return $this->semester == 1 ? 'Ganjil' : 'Genap';
    }
}
