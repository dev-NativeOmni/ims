<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rentang hafalan murid dari sebelum aplikasi dipakai (lihat migrasi create_student_prior_hafalans_table).
 */
class StudentPriorHafalan extends Model
{
    protected $fillable = ['student_id', 'surah_id', 'ayah_start', 'ayah_end', 'created_by'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
