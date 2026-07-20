<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'pendamping_adab_id',
        'name',
        'level',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function pendampingAdab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pendamping_adab_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
