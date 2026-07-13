<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdabRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'evaluator_id',
        'mentor_id',
        'assessment_date',
        'q1', 'q2', 'q3', 'q4', 'q5',
        'q6', 'q7', 'q8', 'q9', 'q10',
        'q11', 'q12', 'q13', 'q14', 'q15',
        'q16', 'q17', 'q18', 'q19', 'q20',
        'total_score',
        'mentor_score',
        'notes',
    ];

    protected $casts = [
        'assessment_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }
}
