<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'meeting_frequency',
        'status',
    ];

    public function classRooms(): HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }
}