<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'class_room_id',
        'teacher_id',
        'name',
        'student_number',
        'gender',
        'birth_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(ParentProfile::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    public function hafalanRecords(): HasMany
    {
        return $this->hasMany(HafalanRecord::class);
    }

    public function murajaahRecords(): HasMany
    {
        return $this->hasMany(MurajaahRecord::class);
    }

    public function hafalanTargets(): HasMany
    {
        return $this->hasMany(HafalanTarget::class);
    }

    public function adabRecords(): HasMany
    {
        return $this->hasMany(AdabRecord::class);
    }
}