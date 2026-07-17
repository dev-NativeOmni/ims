<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ayah extends Model
{
    use HasFactory;

    protected $fillable = [
        'surah_id',
        'ayah_number',
        'juz',
        'text_ar',
        'translation_id',
    ];

    protected function casts(): array
    {
        return [
            'surah_id' => 'integer',
            'ayah_number' => 'integer',
            'juz' => 'integer',
        ];
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }
}
